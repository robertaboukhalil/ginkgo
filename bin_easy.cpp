#include <iostream>
#include <fstream>
#include <utility>
#include <string>
#include <string.h>
#include <vector>
#include <map>
#include <stdlib.h>

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
  
  pair<int, int> bound;
  map<string, pair<int,int> > bin_map;
  string chrom;
  string prev_chr = "chr1";
  string new_chr;
  string dump;
  int len = atoi(argv[2]) - 1;
  int *loc = new int[len];
  int *bins = new int[len];
  int low_bound = 0;
  int high_bound = 0;

  //Creates a map where:
  //Key = Chromosome
  //Value = Pair of integers containing bin boundaries for that chromosome
  bin_file >> dump >> dump;
  while (!bin_file.eof())
  {
    bin_file >> new_chr >> loc[high_bound];
    if (new_chr != prev_chr)
    {
      bound.first = low_bound;
      bound.second = high_bound-1;
      bin_map[prev_chr]= bound;
      low_bound = high_bound;
    }
    prev_chr = new_chr;
    high_bound++;
  }
  bound.first = low_bound;
  bound.second = high_bound-2;
  bin_map[new_chr] = bound;

  int pos, low, mid, high;
  int cnt = 0;
 
  //Uses binary search within chrom boundaries calculated above
  //to map reads into bins
  while (!reads_file.eof())
  {
    reads_file >> chrom >> pos;
    low = (bin_map[chrom]).first;
    high = (bin_map[chrom]).second;
    while (low <= high)
    {
      mid = (low + high) / 2;
      if (pos < loc[mid])
	high = mid - 1;
      else if (pos > loc[mid])
        low = mid + 1;
      else
        low = high +1;
    }
      
    if (pos > loc[mid])
      mid++;
    
    bins[mid]++; 
  }

  outfile << argv[4] << endl;
  for(int i=0; i < len; i++)
    outfile << bins[i] << endl;

  return 0;
}
