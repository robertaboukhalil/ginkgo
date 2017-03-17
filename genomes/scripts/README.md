# Creating bins for new reference genomes

1. First run `make` to compile the scripts. Make sure bowtie and bwa are in your path

2. Fix the paths and qsub commands in buildGenome, binBOWTIE, binBWA, processGenome, mapBWA, mapBOWTIE

3. Create a directory (hg19) with the individual chromosome files for your organism (chr1.fa, chr2.fa, ... chrX.fa). You should NOT include a single
   multifasta file (hg19.fa)

4. Create a file of the known genes in the genome. This should be bed file called `genes` with the fields: chr, start, end, identifier, name, strand(+/-)

   For example, the file for hg19 should look like this:
   ```
   $ head genes
   chr1 11873   14409   NR_046018   DDX11L1 +
   chr1 14361   29370   NR_024540   WASH7P  -
   chr1 17368   17436   NR_106918   MIR6859-1   -
   chr1 17368   17436   NR_107062   MIR6859-2   -
   chr1 34610   36081   NR_026818   FAM138A -
   chr1 34610   36081   NR_026820   FAM138F -
   ```

5. Run buildGenome from the directory with the chromosome fasta files

   ```
   /path/to/buildGenome ASSEMBLY_PREFIX
   ```

   This will sample a read from every position in the genome, map it back with bowtie and bwa to determine unique mappable positions and then derive bins with a certain number of uniquely mappable bases.  It will also create fixed length bins of the desired bin sizes. To make this efficient, it uses grid engine (qsub) to queue work on a cluster. You will need to edit this information for different queueing systems

6. If all goes well the bin boundaries will be recorded in the files fixed_${BINSIZE}_${READLEN}_${ALIGNER} and variable_${BINSIZE}_${READLEN}_${ALIGNER}. The script will
also create some additional files to compute the GC content of the bins (GC_*), the bounds of the bins (meaning how many bins per chromosome until you get to the next
chromosome), a summary of the genes in each bin (genes_*). The directory will also contain a bunch of intermediate files. The included script (clean.sh) can be used to
delete the unneeded files.


Note, we have the bins for several reference genomes computed here:
http://labshare.cshl.edu/shares/schatzlab/www-data/ginkgo/genomes/

