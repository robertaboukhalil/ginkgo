#include <iostream>
#include <fstream>
#include <string>
#include <string.h>
#include <stdlib.h>

using namespace std;

int main(int argc, char *argv[]){

  FILE * binFile;
  FILE * geneFile;
  FILE * binList;
  FILE * outfile;

  binFile = fopen(argv[1], "r");
  geneFile = fopen(argv[2], "r");
  binList = fopen(argv[3], "r");
  outfile = fopen(argv[4], "w");

  if(argc < 4)
  {
    cout << "Format: ./bin2Genes binFile geneFile binList outFile" << endl;
    return 1;
  }

  if(binFile == NULL || geneFile == NULL || binList == NULL)
  {
    cout << "Unable to open files: " << argv[1] << " and/or " << argv[2] << " and/or " << endl;
    return 1;
  }

  int lower_loc = 0;
  int upper_loc = 0;
  char lower_chr[201];
  char upper_chr[201];
  
  int bin;
  int loc;
  int loc2;
  int score;

  char dump[201];
  char gene[201];
  char chr[201];
  char strand[2];

  while (fscanf(binList, "%i", &bin) != EOF)
  {

    rewind(binFile);
    rewind(geneFile);

    //ignore file header
    fscanf(binFile, "%201s%201s", &dump, &dump);
  
    //Find chr/loc boundaries of bin
    for (int i=0; i < bin; i++)
    {
      if (i == (bin-1))
      {
        strcpy(lower_chr, upper_chr);
        lower_loc = upper_loc;
      }
      fscanf(binFile, "%201s%i", &upper_chr, &upper_loc);
    }
 
    //If bin spans two chromsomes set lower bound to start of chromosome
    if (strcmp(lower_chr, upper_chr) != 0)
   {
      strcpy(lower_chr, upper_chr);
      lower_loc = 0;
    }

    //Output all discovered genes in bed format
    fprintf(outfile, "%s%i%s%s%s%i%s%i%s\n", "Genes present in bin #", bin, " (", lower_chr, ":", lower_loc, "-", upper_loc, "):");

    while (fscanf(geneFile, "%201s%i%i%201s%i%2s", &chr, &loc, &loc2, &gene, &score, &strand) != EOF)
    {
      if ( (strcmp(chr, lower_chr) == 0) && (loc >= lower_loc) && (loc < upper_loc) ) {
        fprintf(outfile, "%s\t%i\t%i\t%s\t%i\t%s\n", chr, loc, loc2, gene, score, strand);
      }
    }
    fprintf(outfile, "\n");
  }
  
  return 0;
}
