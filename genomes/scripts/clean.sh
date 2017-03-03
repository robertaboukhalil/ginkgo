#!/bin/bash

while read line; do
  cd /seq/schatz/tgarvin/genomes/${line}
  rm processGenome.* binB* mapB* finishGenome.* buildGenome.* *frags *done
done < list

