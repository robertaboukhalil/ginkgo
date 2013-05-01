#include <iostream>
#include <fstream>
#include <utility>
#include <string>
#include <string.h>
#include <stdlib.h>
#include <vector>

using namespace std;

int main(int argc, char *argv[]){
  	
  ifstream bin_file(argv[1], ios::in);
  ifstream reads_file(argv[3], ios::in);
  ofstream outfile(argv[5], ios::out);
  
  if(argc < 6)
  {
    cout << "USAGE: bin_file #bins reads_file outfile" << endl;
    return 1;
  }
  
  if(!bin_file.good() || !reads_file.good())
  {
    cout << "Unable to open input files: " << argv[1] << " or " << argv[3] << endl;
    return 1;
  }
  
  string chrom, chr, dump;
  int bound, pos;
  int step = 0;
  int count = 0;
  int len = atoi(argv[2]) - 1;
  int *bins = new int[len];
  
  bin_file >> dump >> dump >> chrom >> bound; 
  while (!reads_file.eof())  
  {
    reads_file >> chr >> pos;

    while (chrom != chr || pos > bound)
    {
      bin_file >> chrom >> bound;
      bins[step++] = count;
      count = 0;	
    }
    
    if ((chrom == chr) && (pos < bound))
      count++;
  }
  
  outfile << argv[4] << endl;
  for(int i=0; i < len; i++)
    outfile << bins[i] << endl;

  return 0;
}
