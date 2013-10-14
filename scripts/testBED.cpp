#include <iostream>
#include <fstream>
#include <string>
#include <string.h>
#include <stdlib.h>

using namespace std;

int main(int argc, char *argv[]){

  FILE * listFile;
  FILE * readFile;
  FILE * errorFile;
  
  listFile = fopen(argv[2], "r");
  
  char dir[1001];
  strcpy(dir, argv[1]);  
 
  char * line = NULL;
  char * file = NULL;
  size_t buffer = 0;
  int min, max, cnt;
  bool ERROR = false;
  bool flag;

  while (getline(&file, &buffer, listFile) != EOF)
  {
    char *path = new char[strlen(dir)+strlen(file)]; 
    char *sample = new char[strlen(file)-1];
    strncpy(sample, file, strlen(file)-1);
    strcpy(path, dir);
    strcat(path, "/");
    strcat(path, sample);

    flag = true;

    //Read through the file and determine min/max number of columns
    readFile = fopen(path, "r");
    while(getline(&line, &buffer, readFile) != EOF)
    {
      cnt = 0;

      for (int i=0; i<strlen(line); i++) {
        if (line[i] == '\t') {
          cnt++;
        }
      }

      //set min/max values using first line;
      if (flag) {
        min = cnt;
        max = cnt;
      }
      flag = false;

      //update min/max
      if (cnt > max) {
        max = cnt;
      }
      if (cnt < min) {
        min = cnt;
      }

    }

    if (min != max) {
    
      if (ERROR == false) {
        errorFile = fopen("errors.txt", "w");
        ERROR = true;
      }

      if (min == 0) {
        fprintf(errorFile, "%s has empty lines!\n", sample);
      }
      else {
        fprintf(errorFile, "%s has extra/missing tabs!\n", sample);
      }
    }

    delete [] sample;
    delete [] path;
  }

  if (ERROR == true)
    return 1;

  return 0;
}

