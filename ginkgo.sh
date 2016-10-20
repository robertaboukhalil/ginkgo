#!/bin/bash

# ==============================================================================
# Ginkgo - Command line version
# ==============================================================================

# Default values
DIR_INPUT=""
GENOME=""
BINNING=""
#
CLUSTERING_DISTANCE="euclidean"
CLUSTERING_LINKAGE="ward"
SEGMENTATION=0
FILE_FACS=""
COLOR=3
MASK_BADBINS=0
MASK_SEXCHRS=0
MASK_PSRS=0

# Parse user input
while [[ $# -gt 1 ]];
do
    parameter="$1"

    case $parameter in
        # Required parameters
        --input         ) DIR_INPUT="$2"                                        ; shift; ;;
        --genome        ) GENOME="$2"                                           ; shift; ;;
        --binning       ) BINNING="$2"                                          ; shift; ;;
        # Optional parameters
        --clustdist     ) CLUSTERING_DISTANCE="$2"                              ; shift; ;;
        --clustlinkage  ) CLUSTERING_LINKAGE="$2"                               ; shift; ;;
        --segmentation  ) SEGMENTATION="$2"                                     ; shift; ;;
        --color         ) COLOR="$2"                                            ; shift; ;;
        --facs          ) FILE_FACS="$2"                                        ; shift; ;;
        --maskbadbins   ) MASK_BADBINS=1                                        ;        ;;
        --masksexchrs   ) MASK_SEXCHRS=1                                        ;        ;;
        --maskpsrs      ) MASK_PSRS=1                                           ;        ;;
        *               ) echo "Warning: ignoring parameter $parameter"         ;        ;;
    esac
    shift
done

echo $DIR_INPUT
exit;





--input /my/bed/files/
$1, $dir

--genome hg19
$chosen_genome

--binmethod variable_500000_101_bowtie
$binMeth

--segmentation 0,1,2 [Independent, Global, Custom]
$segMeth

--segmentation-custom
$ref

--maskbadbins
$rmbadbins

--maskpar //pseudoautosomal regions
$rmpseudoautosomal

--masksex
$sex=1 => include; 0 = dont include

--facs
# f=0 if not FACS file is provided. f=1 if FACS file is provided.
# facs=location of facs file

--clusterdist euclidean/maximum/manhattan/canberra/binary/minkowsky
$distMeth

--clusterlinkage ward/single/complete/average/NJ
$clustMeth

--color 1,2,3 [light blue / orange, magenta / gold, dark blue / red]
$color

# -- Running options
# init=1 -> Clean the directory and start from scratch the whole analysis
# process=1 -> Run mapped data through primary pipeline
# fix=1 -> Recreate clusters/heat maps (not required if process=1)



