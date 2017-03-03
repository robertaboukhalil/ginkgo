#include <iostream>
#include <fstream>
#include <string>
#include <string.h>
#include <algorithm>


using namespace std;

int main(int argc, char *argv[]){

  if(argc < 4)
  {
    cout << "Provide input file, output file, and simulated sequence length" << endl;
    return 1;
  }

  FILE * infile = fopen(argv[1],"rt");
  ofstream outfile(argv[2]);

  //Read in fasta file and find total char count
  infile = fopen(argv[1],"rt");
  fseek(infile, 0, SEEK_END);
  long size = ftell(infile);
  fseek(infile, 0, SEEK_SET);

  //Allocate array for sequence
  char *seq = new char[size + 1];
  fread(seq, sizeof(char), size, infile);
  fclose(infile);

  //Find start of sequence
  int start;
  for (start=0; start<=size; start++) {
    if (seq[start] == '\n')
      break;
  }

  //strip all newline characters
  size = std::remove(seq, seq + size, '\n') - seq;

  //Output sequences
  for (int i=start; i<=size-atoi(argv[3]); i++)
  {
    outfile << ">" << argv[1] << "_" << i-start+1 << endl;
    for(int j=0; j<atoi(argv[3]); j++)
    {
      outfile << seq[i+j];
    }
      outfile << endl;
  }

  return 0;
}
