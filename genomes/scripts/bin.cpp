#include <iostream>
#include <fstream>
#include <map>
#include <vector>
#include <string>
#include <string.h>
#include <stdlib.h>
#include <time.h>

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

  FILE * infile;
  FILE * outfile;
  FILE * centfile;
  //FILE * lengthfile;
  outfile = fopen(argv[1], "w");
  centfile = fopen(argv[2], "r");
  //lengthfile = fopen(argv[3], "r");

  cout << "[Creating: " << argv[1] << "]" << endl;

  if(argc < 6)
  {
    cout << "Format: ./variable outputFile centromereFile LengthsFile binSize readLength readType inFile" << endl;
    return 1;
  }

  pair<int, int> cent;
  map<char201, pair<int, int> > cent_map;
  //map<char201, int> length_map;
  //vector<char201> chrom_list;
  string file;
  char chrom[201];
  int bin_size = atoi(argv[3]);
  int read_length = atoi(argv[4]);
  int start;
  int end;

  //Read all chrom centromere boundaries into a map (key = chrom name)
  while (fscanf(centfile, "%201s%i%i", &chrom, &start, &end) != EOF) {
    cent.first = start;
    cent.second = end;
    cent_map[chrom] = cent;
  }

  //Read all chrom lengths into a map (key = chrom name)
  /*while (fscanf(lengthfile, "%201s%i", &chrom, &end) != EOF) {
    length_map[chrom] = end;
    chrom_list.push_back(chrom);
  }*/

  //Create chromosome intervals
  infile = fopen(argv[6], "r");

  int cnt = 0;
  int loc;
  int prev_loc = 0;
  int pre;
  int pre_count;
  int post;
  int post_count;
  bool flag = false;

  //Count total number of reads mapping on either side of chromosome
  while (fscanf(infile, "%201s%i", &chrom, &loc) != EOF) {

    cnt++;

    if ((loc >= (cent_map[chrom]).first) && flag == false) {
      pre_count = cnt-1;
      pre = prev_loc;
      post = loc;
      
      cout << pre << "\t" << post << endl;
      cout << (cent_map[chrom]).first << "\t" << (cent_map[chrom]).second << endl;
      flag = true;
    }
    prev_loc = loc;
  }

  if (flag == false) {
    pre_count = cnt;
    pre = prev_loc;
    post = loc;

    cout << pre << "\t" << post << endl;
    cout << (cent_map[chrom]).first << "\t" << (cent_map[chrom]).second << endl;
    flag = true;
  }

  post_count = cnt - pre_count;

  int pre_bin_tot = pre_count/bin_size;
  int post_bin_tot = post_count/bin_size;
  int pre_reads_per_bin;
  int post_reads_per_bin;
  int pre_spillover;
  int post_spillover;
  flag = false;
  int step = 1;

  //Control for chromosomes with centromere start chrom start 
  if ((cent_map[chrom]).first > 0) {
    if (pre_bin_tot == 0) {
      pre_bin_tot = 1;
    }
    pre_reads_per_bin = pre_count / pre_bin_tot;
    pre_spillover = pre_count % pre_bin_tot;
  }
  else {
    pre_reads_per_bin = 0;
    pre_spillover = 0;
  }

  //Control for chromosomes with centromere end at chrom end
  if (post_bin_tot == 0) {
    post_reads_per_bin = 0;
    post_spillover = 0;
  }
  else {
    post_reads_per_bin = post_count / post_bin_tot;
    post_spillover = post_count % post_bin_tot;
  }


  cout << pre_bin_tot << "\t" << pre_count << "\t" << pre_reads_per_bin  <<  endl;
  cout << post_bin_tot << "\t" << post_count << "\t" << post_reads_per_bin << endl;
  cout << pre_spillover << endl;
  cout << post_spillover << endl;
  cout << endl;

  rewind(infile);

  //Bin reads into intervals of equal mappability
  //Distribute excess reads to initial intervals
  while (fscanf(infile, "%201s%i", &chrom, &loc) != EOF) {

    //Set flag if the centromere is crossed
    if ((loc >= (cent_map[chrom]).first) && (flag == false)) {
      flag = true;
      step = 1;
    }

    if (flag == false) {
      if (step < (pre_reads_per_bin + (pre_spillover >= 0))) {
        step++;
      }
      else {
        fprintf(outfile, "%s\t%i\n", chrom, loc);
        pre_spillover--;
        step = 1;
      }
    }

    if (flag == true) {
      if (post_bin_tot == 0) {
        break;
      }

      if (step < (post_reads_per_bin + (post_spillover >= 0))) {
        step++;
      }
      else {
        fprintf(outfile, "%s\t%i\n", chrom, loc);
        post_spillover--;
        step = 1;
      }
    }
    
  }
  
  fprintf(outfile, "%s\t%i\n", chrom, atoi(argv[5]));
  return 0;

}

