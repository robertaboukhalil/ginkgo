#include <iostream>
#include <fstream>
#include <string>
#include <string.h>
#include <algorithm>
#include <map>

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

  if(argc < 3)
  {
    cout << "Provide input and output files" << endl;
    return 1;
  }

  cout << "[Creating: GC_" << argv[1] << "]" << endl;

  FILE * infile;
  FILE * lengthfile = fopen(argv[3], "r");
  ifstream bin_file(argv[1], ios::in);
  ofstream outfile(argv[2]);

  //Initialize other variables
  char *seq = new char[1000000000];
  string prev_chr;
  string new_chr;
  string dump;
  double cnt = 0;
  double Ns = 0;
  int start = 0;
  int prev = 0;
  int pos = 0;

  //Ignore header
  bin_file >> dump >> dump;
  
  //Calculate GC content in each interval
  while (bin_file >> new_chr >> pos)
  {

    if (new_chr != prev_chr) {

      //Read in new fasta file
      cout << "  Starting: " << new_chr << endl;
      infile = fopen((new_chr + ".fa").c_str(), "rt");

      //Read in fasta file and find total char count
      fseek(infile, 0, SEEK_END);
      long size = ftell(infile);
      fseek(infile, 0, SEEK_SET);
      fread(seq, sizeof(char), size, infile);
      fclose(infile);

      //Find start of sequence
      int start;
      for (start=0; start<=size; start++)
      {
        if (seq[start] == '\n')
          break;
      }

      //Strip all newline characters
      size = std::remove(seq, seq + size, '\n') - seq;

      //Reset starting position to zero
      prev=0;
    }

    //Find GC content
    for(int i=prev; i<(pos-1); i++) {
      if (seq[i] == 'N' || seq[i] == 'n')
        Ns++;
      if (seq[i] == 'G' || seq[i] == 'g' || seq[i] == 'c' || seq[i] == 'C')
        cnt++;
    }
  
    outfile << (cnt)/(pos-prev-Ns) << endl;
   
    prev_chr = new_chr;
    prev = pos;
    cnt = 0;
    Ns = 0;

  }

  exit:

  return 0;
}

