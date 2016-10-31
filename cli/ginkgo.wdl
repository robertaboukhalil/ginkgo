workflow ginkgo
{
    File        input_file
    String      genome
    String      binning
    Boolean     maskpsrs
    Boolean     maskbadbins
    Boolean     masksexchrs
    File        genome_bins
    File        genome_bounds
    File        genome_genes
    File        genome_gc
    File        genome_badbins
    File        ref
    File        facs
    String      clustdist
    String      clustlinkage
    Int         segmentation
    Int         color

    call ginkgo_run
    {
        input:
            input_file      = input_file,
            genome          = genome,
            binning         = binning,
            maskpsrs        = maskpsrs,
            maskbadbins     = maskbadbins,
            masksexchrs     = masksexchrs,
            genome_bins     = genome_bins,
            genome_bounds   = genome_bounds,
            genome_genes    = genome_genes,
            genome_gc       = genome_gc,
            genome_badbins  = genome_badbins,
            ref             = ref,
            facs            = facs,
            clustdist       = clustdist,
            clustlinkage    = clustlinkage,
            segmentation    = segmentation,
            color           = color
    }
}

task ginkgo_run
{
    File        input_file
    String      genome
    String      binning
    Boolean     maskpsrs
    Boolean     maskbadbins
    Boolean     masksexchrs
    File        genome_bins
    File        genome_bounds
    File        genome_genes
    File        genome_gc
    File        genome_badbins
    File        ref
    File        facs
    String      clustdist
    String      clustlinkage
    Int         segmentation
    Int         color

    command <<<
        echo "Launching Ginkgo..."

        # 
        opt_genome_type=$( [[ "${maskpsrs}" == "1" ]] && echo "pseudoautosomal" || echo "original" )
        dir_genome=/mnt/data/ginkgo/genomes/${genome}/$opt_genome_type
        dir_input=/mnt/data/ginkgo/data
        mkdir -p $dir_genome $dir_input

        # Setup ref genome
        cp ${genome_bins} ${genome_bounds} ${genome_genes} ${genome_gc} ${genome_badbins} $dir_genome/
        # Setup data
        tar -xf ${input_file} -C $dir_input

        # Ref + FACS files management
        #touch ${ref}_mapped
        [ -s ${facs} ] && facs=${facs} || facs="ploidyDummy.txt"

        # Replace true/false with 0/1
        [[ "${maskbadbins}" == "true" ]] && maskbadbins="1" || maskbadbins="0"
        [[ "${masksexchrs}" == "true" ]] && masksexchrs="1" || masksexchrs="0"
        [[ "${maskpsrs}"    == "true" ]] && maskpsrs="1"    || maskpsrs="0"


        # ======================================================================
        # == Ginkgo
        # ======================================================================

        DIR_ROOT=/mnt/data/ginkgo
        DIR_GENOMES=$DIR_ROOT/genomes
        DIR_SCRIPTS=$DIR_ROOT/scripts

        # ----------------------------------------------------------------------
        # -- Setup variables
        # ----------------------------------------------------------------------

        statFile=status.xml

        # By default, use all cells
        DIR_CELLS_LIST=$dir_input/list
        ls $dir_input/*.{bed,bed.gz} 2>/dev/null | cat > $DIR_CELLS_LIST

        # Genomes directory
        DIR_GENOME=$DIR_GENOMES/${genome}
        DIR_GENOME=$DIR_GENOME/$( [[ "${maskpsrs}" == "1" ]] && echo "pseudoautosomal" || echo "original" )
        NB_BINS=$(wc -l < $DIR_GENOME/${binning})

        echo "#bins = "$NB_BINS

        # FACS file
        FACS=$([[ -e "$facs" ]] && echo 1 || echo 0)
        FILE_FACS=$facs
        if [ "$FACS" == 0 ];
        then
            FILE_FACS=$dir_input/ploidyDummy.txt
            touch $FILE_FACS
        else
            # 
            # In case upload file with \r instead of \n (Mac, Windows)
            tr '\r' '\n' < $FILE_FACS > $dir_input/tmp
            mv $dir_input/tmp $FILE_FACS
            # 
            sed "s/.bed//g" $FILE_FACS | sort -k1,1 | awk '{print $1"\t"$2}' > $dir_input/tmp 
            mv $dir_input/tmp $FILE_FACS
        fi


        # ----------------------------------------------------------------------
        # -- Map Reads & Prepare Samples For Processing
        # ----------------------------------------------------------------------

        # Map user bed files to appropriate bins
        while read file;
        do
            echo -n "# Processing $file... "

            # Bin reads
            out_file=$file
            out_file+="_mapped"
            $DIR_SCRIPTS/binUnsorted $DIR_GENOME/${binning} $NB_BINS $file $file $out_file
        done < $DIR_CELLS_LIST

        # Concatenate binned reads to central file  
        echo "# Concatenating binned reads... "
        paste $dir_input/*_mapped > $dir_input/data
        rm -f $dir_input/*{_mapped,_binned}


        # ----------------------------------------------------------------------
        # -- Map User Provided Reference/Segmentation Sample
        # ----------------------------------------------------------------------

        if [ "${segmentation}" == "2" ];
        then
            $DIR_SCRIPTS/binUnsorted $DIR_GENOME/${binning} $NB_BINS ${ref} Reference ${ref}_mapped
        else
            SEGMENTATION_REF=refDummy.bed
            touch $dir_input/${ref}_mapped
        fi


        # ----------------------------------------------------------------------
        # -- Run Mapped Data Through Primary Pipeline
        # ----------------------------------------------------------------------

        $DIR_SCRIPTS/process.R $DIR_GENOME $dir_input $statFile data ${segmentation} ${binning} ${clustlinkage} ${clustdist} ${color} ${ref}_mapped $FACS $FILE_FACS $( [[ $masksexchrs == 1 ]] && echo 0 || echo 1 ) $maskbadbins


        # ----------------------------------------------------------------------
        # -- Create CNV profiles
        # ----------------------------------------------------------------------

        nbCols=$(awk '{ print NF; exit; }' $dir_input/SegCopy)
        for (( i=1; i<=$nbCols; i++ ));
        do
          currCell=$(cut -f$i $dir_input/SegCopy | head -n 1 | tr -d '"')
          if [ "$currCell" == "" ]; then
            continue;
          fi
          cut -f$i $dir_input/SegCopy | tail -n+2 | awk '{if(NR==1) print "1,"$1; else print NR","prev"\n"NR","$1;prev=$1; }' > $dir_input/$currCell.cnv
        done


        # ----------------------------------------------------------------------
        # -- Call CNVs
        # ----------------------------------------------------------------------

        $DIR_SCRIPTS/CNVcaller $dir_input/SegCopy $dir_input/CNV1 $dir_input/CNV2


        # ----------------------------------------------------------------------
        # -- Create tar file of output
        # ----------------------------------------------------------------------

        tar -cvf archive.tar --exclude '*.bed' --exclude '*.bed.gz' $dir_input/


        echo "Done."
    >>>

    output {
        File out = "archive.tar"
    }

    runtime {
        docker: "robertaboukhalil/ginkgo:latest"
    }
}