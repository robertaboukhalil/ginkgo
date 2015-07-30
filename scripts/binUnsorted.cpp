#include <iostream>
#include <fstream>
#include <utility>
#include <string>
#include <string.h>
#include <vector>
#include <map>
#include <stdlib.h>

using namespace std;

struct char201
{
  char val[201];

  char201() {
    memset(val, '\0', 201);
  }

  char201(const char201& oth) {
    memmove(val, oth.val, 201);
  }

  char201(const char * str) {
    memset(val, '\0', 201);
    if (str)
    {
      strncpy(val, str, 200);
    }
  }


  bool operator<(const char201& oth) const {
    return strcmp(val , oth.val) < 0; 
  }

};

int main(int argc, char *argv[]){
   
  FILE * bin_file;
  FILE * reads_file;
  FILE * outfile;
  bin_file = fopen(argv[1], "r");
  reads_file = fopen(argv[3], "r");
  outfile = fopen(argv[5], "w");
  
  if(argc < 6)
  {
    cout << "USAGE: binFile #bins readsFile nameReadsFile outfile" << endl;
    return 1;
  }
  
  if(bin_file == NULL || reads_file == NULL)
  {
    cout << "Unable to open input files: " << argv[1] << " or " << argv[3] << endl;
    return 1;
  }
  
  pair<int, int> bound;
  map<char201, pair<int,int> > bin_map;
  char chrom[201];
  char prev_chr[201];
  char new_chr[201];
  char dump[201];
  int len = atoi(argv[2]) - 1;
  int *loc = new int[len];
  int *bins = new int[len];
  int low_bound = 0;
  int high_bound = 0;
  bool skip = true;

  //Creates a map where:
  //Key = Chromosome
  //Value = Pair of integers containing bin boundaries for that chromosome
  fscanf(bin_file, "%201s%201s", &dump, &dump);
  while (fscanf(bin_file, "%201s%i", &new_chr, &loc[high_bound]) != EOF)
  {
    if (strcmp(prev_chr, new_chr) != 0)
    {
      if (skip == false)
      {
        bound.first = low_bound;
        bound.second = high_bound-1;
        bin_map[prev_chr]= bound;
        low_bound = high_bound;
      }
    skip=false;
    }
    strcpy(prev_chr, new_chr);
    high_bound++;
  }
  bound.first = low_bound;
  bound.second = high_bound-2;
  bin_map[new_chr] = bound;


  char * line = NULL;
  size_t buffer = 1000;
  ssize_t read;
  int tabs = 0;
  
  read = getline(&line, &buffer, reads_file);
  for (int i=0; i<read; i++)
  {
    if (line[i] == '\t')
      tabs++;
  }
  tabs --;
  rewind(reads_file);
  
  int pos, low, mid, high;
  int cnt = 0;

  //Uses binary search within chrom boundaries calculated above
  //to map reads into bins
  while (fscanf(reads_file, "%201s%i", &chrom, &pos) != EOF)
  {
   
    for (int i=0; i<tabs; i++) {
      fscanf(reads_file, "%201s", &dump);
    }

    if (bin_map.count(chrom) == 0) {
      continue;
    }
   
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

  fprintf(outfile, "%s\n", argv[4]);
  for(int i=0; i < len; i++)
    fprintf(outfile, "%i\n", bins[i]);

  return 0;
}
