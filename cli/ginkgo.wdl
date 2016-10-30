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
        dir_genome=/ginkgo/genomes/${genome}/$opt_genome_type/
        dir_input=/ginkgo/data/
        mkdir -p $dir_genome $dir_input

        # Setup ref genome
        cp ${genome_bins} ${genome_bounds} ${genome_genes} ${genome_gc} ${genome_badbins} $dir_genome/
        # Setup data
        tar -xf ${input_file} -C $dir_input


        echo "Done."
    >>>

    #output {
    #    File out = "${input_file}/archive.tar"
    #}

    runtime {
        docker: "robertaboukhalil/ginkgo:latest"
    }
}

