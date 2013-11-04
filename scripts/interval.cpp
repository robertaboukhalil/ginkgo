#include <iostream>
#include <fstream>
#include <string>
#include <string.h>
#include <stdlib.h>

using namespace std;

int main(int argc, char *argv[]){

  FILE * geneFile;
  FILE * intervals;
  FILE * outfile;

  geneFile = fopen(argv[1], "r");
  intervals = fopen(argv[2], "r");
  outfile = fopen(argv[3], "w");

  if (argc < 3)
  {
    cout << "Format: ./interval geneFile intervalsFile outFile" << endl;
    return 1;
  }

  if (geneFile == NULL)
  {
    cout << "Unable to open files: " << argv[1] << " and/or " << argv[2] << endl;
    return 1;
  }

  if (intervals == NULL) {
    exit(0);
  }

  //Determine if file contains genes or genomic intervals
  char * line = NULL;
  size_t buffer = 1000;
  ssize_t interval;
  int tabs = 0;

  interval = getline(&line, &buffer, intervals);
  for (int i=0; i<interval; i++)
  {
    if (line[i] == '\t')
      tabs++;
  }
  tabs++;

  if (tabs == 1) {
    exit(0);
  }

  rewind(intervals);

  int lower = 0;
  int upper = 0;
  char chrom[201];
  bool flag;

  char chr[201];
  int loc;
  int loc2;
  char gene[201];
  int score;
  char strand[2];


  while (fscanf(intervals, "%201s%i%i", &chrom, &lower, &upper) != EOF)
  {
    rewind(geneFile);
    flag = false;

    while (fscanf(geneFile, "%201s%i%i%201s%i%2s", &chr, &loc, &loc2, &gene, &score, &strand) != EOF)
    {

      if ( (strcmp(chr, chrom) == 0) && (loc <= upper) && (loc2 >= lower) )
      {
        fprintf(outfile, "%s:%i-%i\t%s\t%i\t%i\t%s\t%i\t%s\n", chrom, lower, upper, chr, loc, loc2, gene, score, strand);
      flag = true;
      }
      
      if ( (flag == true) && (loc > upper) ) {
        break;
      }
    }

  }

  return 0;
}

