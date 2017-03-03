#include <iostream>
#include <fstream>
#include <string>
#include <string.h>
#include <stdlib.h>
#include <vector>

using namespace std;

int main(int argc, char *argv[]){

  ifstream bin_file(argv[1], ios::out);
  ofstream outfile(argv[2], ios::out);
  
  if(argc < 3)
  {
    cout << "Provide input and output files" << endl;
    return 1;
  }

  if(!bin_file.good())
  {
    cout << "Unable to open input file: " << argv[1] << endl;
    return 1;
  }

  cout << "[Creating: " << argv[2] << "]" << endl;


  vector<pair<string, int> > bounds;
  pair<string, int> p;
  bool flag = true;
  string prev_chr;
  string new_chr;
  string dump;
  string chr;
  int cnt = 0;
  int loc;

  bin_file >> dump >> dump;  //Ignore bin_file header
  
  while (!bin_file.eof())
  {
    cnt++;
    bin_file >> new_chr >> loc;
  
    if (new_chr != prev_chr && flag == false)
    { 
      p.first = prev_chr;
      p.second = cnt;
      bounds.push_back(p);
      prev_chr = new_chr;
    }

    if (new_chr != prev_chr && flag == true)
    {
      flag=false;
      prev_chr=new_chr;
    }

  }

  vector<pair<string, int> >::iterator it;
  for (it = bounds.begin(); it != bounds.end(); ++it)
    outfile << it->first << "\t" << it->second << endl;

  return 0;
}
