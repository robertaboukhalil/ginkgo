#!/bin/bash

# ==============================================================================
# Ginkgo - Command line version
# ==============================================================================

DIR_ROOT="/ginkgo"
DIR_GENOMES=${DIR_ROOT}/genomes
DIR_SCRIPTS=${DIR_ROOT}/scripts

# ------------------------------------------------------------------------------
# Parse user input
# ------------------------------------------------------------------------------

# Sample usage
usage=" ---> Sample usage: ./ginkgo.sh --input dir/to/bed/files/ --genome hg19 --binning variable_500000_101_bowtie [--clustdist euclidean] [--clustlinkage ward] [--facs facs.txt] [--maskbadbins] [--maskpsrs] [--masksexchrs]"

# Required parameters
unset DIR_INPUT
unset GENOME
unset BINNING

# Optional parameters
CLUSTERING_DISTANCE="euclidean"
CLUSTERING_LINKAGE="ward"
SEGMENTATION=0
FILE_FACS=""
COLOR=3
MASK_BADBINS=0
MASK_SEXCHRS=0
MASK_PSRS=0
DIR_CELLS_LIST=""

# Parse user input
while [[ $# -gt 0 ]]; #1
do
    case "${1}" in
        # Required parameters
        --input         ) DIR_INPUT="$2"                                        ; shift; ;;
        --genome        ) GENOME="$2"                                           ; shift; ;;
        --binning       ) BINNING="$2"                                          ; shift; ;;
        # Optional parameters
        --clustdist     ) CLUSTERING_DISTANCE="$2"                              ; shift; ;;
        --clustlinkage  ) CLUSTERING_LINKAGE="$2"                               ; shift; ;;
        --segmentation  ) SEGMENTATION="$2"                                     ; shift; ;;
        --ref           ) SEGMENTATION_REF="$2"                                 ; shift; ;;
        --color         ) COLOR="$2"                                            ; shift; ;;
        --facs          ) FILE_FACS="$2"                                        ; shift; ;;
        --cells         ) DIR_CELLS_LIST="$2"                                   ; shift; ;;
        --maskbadbins   ) MASK_BADBINS=1                                        ;        ;;
        --masksexchrs   ) MASK_SEXCHRS=1                                        ;        ;;
        --maskpsrs      ) MASK_PSRS=1                                           ;        ;;
        *               ) echo "Warning: ignoring parameter ${1}"               ;        ;;
    esac
    shift
done

# Validate required parameters
DIR_INPUT=${DIR_INPUT?$usage}
GENOME=${GENOME?$usage}
BINNING=${BINNING?$usage}


# ------------------------------------------------------------------------------
# -- Setup variables
# ------------------------------------------------------------------------------

statFile=status.xml

# Make sure input folder is valid
[[ ! -d "${DIR_INPUT}" ]] && echo "Error: folder ${DIR_INPUT} doesn't exist" && exit

# By default, use all cells
if [ -z "${DIR_CELLS_LIST}" ];
then
    DIR_CELLS_LIST=${DIR_INPUT}/"list"
    ls ${DIR_INPUT}/*.{bed,bed.gz} 2>/dev/null | cat > "${DIR_CELLS_LIST}"
fi

# Genomes directory
DIR_GENOME=${DIR_GENOMES}/${GENOME}
DIR_GENOME=${DIR_GENOME}/$( [[ "${MASK_PSRS}" == "1" ]] && echo "pseudoautosomal" || echo "original" )
NB_BINS=$(wc -l < ${DIR_GENOME}/${BINNING})

# FACS file
FACS=$([[ -e "${FILE_FACS}" ]] && echo 1 || echo 0)
if [ "${FACS}" == 0 ];
then
    FILE_FACS=${DIR_INPUT}/"ploidyDummy.txt"
    touch ${FILE_FACS}
else
    # 
    uuid=$(uuidgen)
    # In case upload file with \r instead of \n (Mac, Windows)
    tr '\r' '\n' < ${FILE_FACS} > ${DIR_INPUT}/tmp-${uuid}
    mv ${DIR_INPUT}/tmp-${uuid} ${FILE_FACS}
    # 
    sed "s/.bed//g" ${FILE_FACS} | sort -k1,1 | awk '{print $1"\t"$2}' > ${DIR_INPUT}/tmp-${uuid} 
    mv ${DIR_INPUT}/tmp-${uuid} ${FILE_FACS}
fi


# ------------------------------------------------------------------------------
# -- Map Reads & Prepare Samples For Processing
# ------------------------------------------------------------------------------

# Clean directory
rm -f ${DIR_INPUT}/{data,CNV*,Seg*,results.txt,*{_mapped,.jpeg,.newick,.xml,.cnv}}

# Map user bed files to appropriate bins
while read file;
do
    echo -n "# Processing ${file}... "

    # Make sure exists
    if [[ ! "${file}" =~ \.bed$ ]] && [[ ! "${file}" =~ \.bed.gz$ ]]; then
        echo "error: file <${file}> doesn't exist"
        exit;
    fi
    echo ""

    # Add "z" to cat to support gzipped files
    [[ "${file}" =~ \.gz$ ]] && Z="z" || Z=""

    # If bed file doesn't encode chromosomes using 'chr', add it
    firstLineChr=$(${Z}grep --max 1 "chr" ${file} | cut -f1)
    if [ "${firstLineChr}" == "" ];
    then
        echo "# -> no 'chr' detected; rewritting bed files."
        awk '{print "chr"$0}' <( ${Z}cat ${file} ) | ( [[ ${Z} == "z" ]] && gzip || cat ) > ${file}_tmp
        mv ${file} ${file}_invalidchr
        mv ${file}_tmp ${file}
    fi

    # Bin reads
    ${DIR_SCRIPTS}/binUnsorted ${DIR_GENOME}/${BINNING} ${NB_BINS} <(${Z}cat ${file}) ${file//.bed} ${file}_mapped
done < ${DIR_CELLS_LIST}

# Concatenate binned reads to central file  
echo "# Concatenating binned reads... "
paste ${DIR_INPUT}/*_mapped > ${DIR_INPUT}/data
rm -f ${DIR_INPUT}/*{_mapped,_binned}


# ------------------------------------------------------------------------------
# -- Map User Provided Reference/Segmentation Sample
# ------------------------------------------------------------------------------

if [ "${SEGMENTATION}" == "2" ];
then
    ${DIR_SCRIPTS}/binUnsorted ${DIR_GENOME}/${BINNING} ${NB_BINS} ${SEGMENTATION_REF} Reference ${SEGMENTATION_REF}_mapped
else
    SEGMENTATION_REF=refDummy.bed
    touch ${DIR_INPUT}/${SEGMENTATION_REF}_mapped
fi


# ------------------------------------------------------------------------------
# -- Run Mapped Data Through Primary Pipeline
# ------------------------------------------------------------------------------

${DIR_SCRIPTS}/process.R ${DIR_GENOME} ${DIR_INPUT} ${statFile} data ${SEGMENTATION} ${BINNING} ${CLUSTERING_LINKAGE} ${CLUSTERING_DISTANCE} ${COLOR} ${SEGMENTATION_REF}_mapped ${FACS} ${FILE_FACS} $( [[ $MASK_SEXCHRS == 1 ]] && echo 0 || echo 1 ) ${MASK_BADBINS}


# ------------------------------------------------------------------------------
# -- Create CNV profiles
# ------------------------------------------------------------------------------

nbCols=$(awk '{ print NF; exit; }' ${DIR_INPUT}/SegCopy)
for (( i=1; i<=$nbCols; i++ ));
do
  currCell=$(cut -f$i ${DIR_INPUT}/SegCopy | head -n 1 | tr -d '"')
  if [ "$currCell" == "" ]; then
    continue;
  fi
  cut -f$i ${DIR_INPUT}/SegCopy | tail -n+2 | awk '{if(NR==1) print "1,"$1; else print NR","prev"\n"NR","$1;prev=$1; }' > ${DIR_INPUT}/$currCell.cnv
done


# ------------------------------------------------------------------------------
# -- Call CNVs
# ------------------------------------------------------------------------------

${DIR_SCRIPTS}/CNVcaller ${DIR_INPUT}/SegCopy ${DIR_INPUT}/CNV1 ${DIR_INPUT}/CNV2
