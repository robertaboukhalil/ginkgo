#!/bin/bash

##################VARIABLES##################
#ASSEMBLY (name of assembly used - i.e. hg19)
#############################################
export SGE_JSV_TIMEOUT=120

 : <<'COMMENT'

#Make list of all fasta files
echo "Step (1/7): Building Chrom List"
ls | grep ^chr | grep ".fa" |  awk -F ".fa" '{print $1}' | sort -V | uniq > list

#Concatenate all fasta files
echo -e "\nStep (2/7): Preparing Genome"
cat `ls | grep chr | sort -V | tr '\n' ' '` > ${ASSEMBLY}.fa

#Build bowtie reference files
echo -e "\nStep (3/7): Building Index Files"
/opt/uge/bin/lx-amd64/qsub -cwd -l m_mem_free=8G -v ASSEMBLY=$ASSEMBLY /seq/schatz/mschatz/ginkgo/genomes/scripts/indexBWA
/opt/uge/bin/lx-amd64/qsub -cwd -l m_mem_free=8G -v ASSEMBLY=$ASSEMBLY /seq/schatz/mschatz/ginkgo/genomes/scripts/indexBOWTIE

COMMENT

#Extract reads at all positions across chromosome
echo -e "\nStep (4/7): Simulating Reads"
while read line; do
  for len in 48 76 101 150; do
    /opt/uge/bin/lx-amd64/qsub -cwd -l m_mem_free=3G -v IN=$line -v LENGTH=$len /seq/schatz/mschatz/ginkgo/genomes/scripts/processGenome
  done
done < list

 : <<'COMMENT'

