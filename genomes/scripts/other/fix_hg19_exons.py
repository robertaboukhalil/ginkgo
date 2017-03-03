#!/usr/bin/env python

import numpy as np
import sys
import re

fin = open("genes.hg19.out", 'r')
fout = open("genes.hg19.exons.temp", 'w')

line=fin.readline()

for line in fin:
  fields = line.strip().split()
  chr = fields[2]
  strand = fields[3]
  gene = fields[1]
  exon_start = fields[9].split(',')[:-1]
  exon_end = fields[10].split(',')[:-1]
  cnt = len(exon_start)
  
  for i in range(cnt):
    fout.write(chr + '\t' + exon_start[i] + '\t' + exon_end[i] + '\t' + gene + '\t' + fields[12] + '\t' + strand + '\n')
