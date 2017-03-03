# Scripts for creating bins for a new genome file

1. First run `make` to compile the scripts. Make sure bowtie and bwa are in your path

2. Fix the paths in buildGenome, binBOWTIE, binBWA, processGenome

3. Create a directory (hg19) with the individual chromosome files for your organism (chr1.fa, chr2.fa, ... chrX.fa). You should NOT include a single
   multifasta file (hg19.fa)

4. Create a file of the known genes in the genome. This should be bed file called gene with the fields: chr, start, end, identifier, name, strand(+/-)

5. Run buildGenome from the directory with the chromosome fasta files

   ```
   /path/to/buildGenome ASSEMBLY_PREFIX
   ```

   This will sample a read from every position in the genome, map it back with bowtie and bwa to determine unique mappable positions.
   It will also create fixed length bins of the desired sizes. To make this efficient, it uses grid engine (qsub) to queue work on a
   cluster. You will need to edit this information for different queueing systems




