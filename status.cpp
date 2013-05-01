#include <iostream>
#include <fstream>
#include <string>
#include <string.h>
#include <stdlib.h>

using namespace std;

int main(int argc, char *argv[]){

  ofstream outfile(argv[1], ios::out);  
  int step = atoi(argv[2]);
  string proc = argv[3];
  int cnt = atoi(argv[4]);
  int total = atoi(argv[5]);

  outfile << "<?xml version='1.0'?>" << endl;
  outfile << "<status>" << endl;
  outfile << "<step>" << step << "</step>" << endl;
  outfile << "<processingfile>" << proc << "</processingfile>" << endl;
  outfile << "<percentdone>" << (100*cnt)/total << "</percentdone>" << endl;
  outfile << "<tree>0</tree>" << endl;
  outfile << "</status>" << endl;

 return 0;
}
