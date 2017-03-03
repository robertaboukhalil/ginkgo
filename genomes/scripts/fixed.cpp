#include <iostream>
#include <fstream>
#include <string>
#include <string.h>
#include <stdlib.h>

using namespace std;

int main(int argc, char *argv[]){

  FILE * infile;
  FILE * outfile;
  infile = fopen(argv[1], "r");
  outfile = fopen(argv[2], "w");

  cout << "\n[Creating: " << argv[2] << "]" << endl; 

  int bin_size = atoi(argv[3]);

  if(argc < 4)
  {
    cout << "Format: ./fixed chrLengthsFile outputFile binSize" << endl;
    return 1;
  }

  if(infile == NULL)
  {
    cout << "Unable to open file: " << argv[1] << endl;
    return 1;
  }

  char chr[201];
  int length;
  int step;

  fprintf(outfile, "CHR\tSTART\n");

  while (fscanf(infile, "%201s%i", &chr, &length) != EOF)
  {
    step = bin_size;
    
    while ((step-bin_size) < length)
    {
      fprintf(outfile, "%s\t%i\n", chr, step);
      step += bin_size;
    }
  }

  return 0;
}
