#!/bin/bash

echo "removing log files"
rm -f processGenome.* binB* mapB* *done *.fa.* bowtieBUILT bwaBUILT indexB*

if [ $# == 1 ] && [ "$1" == "all" ]
then
  echo "removing simulated reads"
  # rm -f *frags
  echo "removing bwa and bowtie indices"
  # rm -f *.fa.*
  echo "removing chromosome specific files"
  rm -f results_*_*_*
else
  echo "run 'clean.sh all' to remove simulated reads and index files"
fi





