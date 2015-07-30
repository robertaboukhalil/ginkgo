#include <iostream>
#include <fstream>
#include <utility>
#include <string>
#include <string.h>
#include <vector>
#include <map>
#include <stdlib.h>

using namespace std;

struct char201 {

  char val[201];

  char201() {
    memset(val, '\0', 201);
  }

  char201(const char201& oth) {
    memmove(val, oth.val, 201);
  }

  char201(const char * str) {
    memset(val, '\0', 201);
    if (str) {
      strncpy(val, str, 200);
    }
  }

  bool operator<(const char201& oth) const {
    return strcmp(val , oth.val) < 0; 
  }

};

int main(int argc, char *argv[]){
    
  FILE * CN_file;
  FILE * outfile1;
  FILE * outfile2;

  CN_file = fopen(argv[1], "r");
  outfile1 = fopen(argv[2], "w");
  outfile2 = fopen(argv[3], "w");
  

  //Handle exceptions
  if(argc < 4)
  {
    cout << "USAGE: ./temp CN_file outfile1 outfile2" << endl;
    return 1;
  }
  
  if(CN_file == NULL)
  {
    cout << "Unable to open input file: " << argv[1] << endl;
    return 1;
  }

  
  //Count the number of samples in the file
  char * line = NULL;
  size_t buffer = 10000;
  ssize_t read;
  int n = -2;

  read = getline(&line, &buffer, CN_file);
  for (int i=0; i<read; i++) {
    if (line[i] == '\t')
      n++;
  }

  //Initialize variables and arrays
  char201 * sample = new char201[n];
  int * start_var = new int[n];
  int * start_CN = new int[n];
  int * prev_var = new int[n];
  int * prev_CN = new int[n];

  for (int i=0; i<n; i++) {
    start_CN[i] = 0;
    prev_CN[i] = 0;
  }

  char dump[201];
  char chr[201];
  char prev_chr[201];
  int var = 0;
  int start;
  int end;
  int prev_end;
  int CN;

  fscanf(CN_file, "%201s", &prev_chr); //save name of first chrom
  rewind(CN_file);


  //Process sample names
  fscanf(CN_file, "%201s%201s%201s", &dump, &dump, &dump);
  for (int i=0; i <n; i++) {
    fscanf(CN_file, "%201s", &sample[i]);
  }

  /////////////////////
  ///PROCESS SAMPLES///
  /////////////////////

  while (fscanf(CN_file, "%201s%i%i", &chr, &start, &end) != EOF) {

    for (int i=0; i<n; i++) {
      fscanf(CN_file, "%i", &CN);

      //Determine whether CNV is a deletion or amplification
      if (CN < 2)
	var = -1;
      else if (CN > 2)
        var = 1;
      else
	var = 0;



      //Handle change to new chromosomes
      if (strcmp(prev_chr, chr) != 0) {

	if (prev_CN[i] != 2) {
	//Ignores rare case when there are zero breakpoints on a chromosome for a given sample
	  fprintf(outfile1, "%s\t%i\t%i\t%s\t%i\n", prev_chr, start_CN[i], prev_end, sample[i].val, prev_CN[i]);
	  fprintf(outfile2, "%s\t%i\t%i\t%s\t%i\n", prev_chr, start_var[i], prev_end, sample[i].val, prev_var[i]);
	}
	//reset breakpoint info
	if (CN != 2) {
	  start_CN[i] = 1;
	  start_var[i] = 1;
	  prev_CN[i] = CN;
	  prev_var[i] = var;
	}
	else {
	  start_CN[i] = 0;
	  start_var[i] = 0;
	}
      }

      //Handle variants within chromosomes
      if (strcmp(prev_chr, chr) == 0) {

	//Mark the start location for the first variant on each chrom and continue loop
	if (start_CN[i] == 0) {
	  prev_CN[i] = CN;
	  prev_var[i] = var;
	  start_CN[i] = start;
	  start_var[i] = start;
	  continue;
	}

	//////////////////////////////////////
	//OUTPUT INTEGER COPY NUMBER EVENTS//
	////////////////////////////////////

	//Mark the start location for each variant transitioning from CN 2
	if ( (CN != 2) && (prev_CN[i] == 2) ) {
	  start_CN[i] = start;
	}

	//Output variant boundaries when transitioning back to CN 2
	if ( (CN == 2) && (prev_CN[i] != 2) ) {
	  fprintf(outfile1, "%s\t%i\t%i\t%s\t%i\n", chr, start_CN[i], prev_end, sample[i].val, prev_CN[i]);
	}

	//Output variant boundaries and reset boundaries for all other cases
	if ( (CN != 2) && (prev_CN[i] != 2)  && (CN != prev_CN[i]) ) {
	  fprintf(outfile1, "%s\t%i\t%i\t%s\t%i\n", chr, start_CN[i], prev_end, sample[i].val, prev_CN[i]);
	  start_CN[i] = start;
	}


	/////////////////////////////////////
	//OUTPUT BINARY COPY NUMBER EVENTS//
	///////////////////////////////////

	//Mark the start location for each variant transitioning from CN 2
	if ( (var != 0) && (prev_var[i] == 0) ) {
	  start_var[i] = start;
	}

	//Output variant boundaries when transitioning back to CN 2
	if ( (var == 0) && (prev_var[i] != 0) ) {
	  fprintf(outfile2, "%s\t%i\t%i\t%s\t%i\n", chr, start_var[i], prev_end, sample[i].val, prev_var[i]);
	}

	//Output variant boundaries and reset boundaries for all other cases
	if ( (var != 0) && (prev_var[i] != 0)  && (var != prev_var[i]) ) {
	  fprintf(outfile2, "%s\t%i\t%i\t%s\t%i\n", chr, start_var[i], prev_end, sample[i].val, prev_var[i]);
	  start_var[i] = start;
	}



      } //end of processing for a given sample
      prev_CN[i] = CN;
      prev_var[i] = var;
    } //end of processing for a given bin
    prev_end = end;
    strcpy(prev_chr, chr);
  } //end of processing file

  for (int i=0; i<n; i++) {
    if (prev_CN[i] != 2) {
      fprintf(outfile1, "%s\t%i\t%i\t%s\t%i\n", prev_chr, start_CN[i], prev_end, sample[i].val, prev_CN[i]);
      fprintf(outfile2, "%s\t%i\t%i\t%s\t%i\n", prev_chr, start_var[i], prev_end, sample[i].val, prev_var[i]);
    }
  }

  return 0;

}
