#include <iostream>
#include <fstream>
#include <string>
#include <string.h>
#include <algorithm>


using namespace std;

int main(int argc, char *argv[]){

  if(argc < 3)
  {
    cout << "Provide input file, output file" << endl;
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

  int beg;
  bool found = false;

  //Find blocks of Ns 
  for (int pos=start; pos<=size; pos++)
  {
    if (found) {
      if (seq[pos] != 'N') {
        found = false;
        outfile << beg-start << "\t" << pos-start-1 << endl;
      }
    }

    else if (found == false) {
      if (seq[pos] == 'N') {
        beg = pos;
        found = true;
      }
    }

  }

  return 0;

}

