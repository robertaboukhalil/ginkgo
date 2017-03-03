#include <iostream>
#include <fstream>
#include <map>
#include <vector>
#include <string>
#include <string.h>
#include <stdlib.h>
#include <time.h>

using namespace std;

int main(int argc, char *argv[]){

  FILE * binfile;
  FILE * genefile;
  FILE * outfile;

  binfile = fopen(argv[1], "r");
  genefile = fopen(argv[2], "r"); 
  outfile = fopen(argv[3], "w");

  int cnt=0;

  //initialize variables for gene file
  char chrom[201];
  char name[201];
  char name2[201];
  char strand[201];
  int start;
  int end;

  //initialize variables for bin file
  char prev_chrom[201];
  char cur_chrom[201];
  char dump[201];
  int prev_end = 0;
  int cur_end;

  //Iterate through each gene and find bin location
  while (fscanf(genefile, "%201s%i%i%201s%201s%201s", &chrom, &start, &end, &name, &name2, &strand) != EOF) {

    //clear binfile header
    cnt = 0;
    rewind(binfile);
    fscanf(binfile, "%201s%201s", &dump, &dump);

    while (fscanf(binfile, "%201s%i", &cur_chrom, &cur_end) != EOF) {

      //When you read in a new chromosome reset the starting position to 0
      if (strcmp(prev_chrom, cur_chrom) != 0) {
        prev_end=0;
      }

      //increment bin counter
      cnt++;

      if ( (strcmp(chrom, cur_chrom) == 0) && (start > prev_end) && (start < cur_end) ) {
        fprintf(outfile, "%s\t%s\t%i\n", name, name2, cnt);
        break;
      }
 
      strcpy(prev_chrom, cur_chrom);
      prev_end=cur_end;
    }

  }

  return 0;
}

