#include <iostream>
#include <fstream>
#include <string>
#include <string.h>
#include <stdlib.h>
#include <stdio.h>

using namespace std;

int main(int argc, char *argv[]){

  FILE * infile;
  FILE * outfile;
  infile = fopen(argv[1], "r");
  
  if(argc < 2)
  {
    cout << "Format: ./fixFa input_file" << endl;
    return 1;
  }

  if(infile == NULL)
  {
    cout << "Unable to open file: " << argv[1] << endl;
    return 1;
  }

  char line[1001];
  char temp[1001];

  while (fscanf(infile, "%1001s", &line) != EOF)
  {

    if (line[0] == '>')
    {

      if (strchr(line, '_') == NULL)
      {
        for (int i=1; i<1001; ++i)
        {
	  temp[i-1]=line[i];
        }
        outfile = fopen(strcat(temp, ".fa"), "w");
      }
      else
      {
        outfile = fopen("dump", "w");
      }

    }
    
    fprintf(outfile, "%s\n", line);

  }

  remove("dump");
  remove("chrUm.fa");
  remove("chrM.fa");

  return 0;
}

