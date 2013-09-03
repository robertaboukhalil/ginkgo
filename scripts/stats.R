#!/usr/bin/env Rscript

args<-commandArgs(TRUE)

user_dir <- args[[1]]
status <- args[[2]]
dat <- args[[3]]

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>1</step>", "<processingfile>Initializing</processingfile>", "<percentdone>0</percentdone>", "<tree>hist.newick</tree>", "</status>"), statusFile)
close(statusFile)

setwd(user_dir)
T=read.table(dat, header=TRUE, sep="\t")

l=dim(T)[1] #Number of bins
w=dim(T)[2]-2 #Number of samples
lab=colnames(T[3:dim(T)[2]]) #Sample labels

allStats=matrix(0, w, 10)
allStats[1,1]="Sample"
allStats[1,2]="Total Reads"
allStats[1,3]="Mean"
allStats[1,4]="Std"
allStats[1,5]="Min"
allStats[1,6]="25th"
allStats[1,7]="Median"
allStats[1,8]="75th"
allStats[1,9]="Max"
allStats[1,10]="Flag"


#PROCESS ALL SAMPLES
for(k in 1:w){

  statusFile<-file( paste(user_dir, "/", status, sep="") )
  writeLines(c("<?xml version='1.0'?>", "<status>", "<step>1</step>", paste("<processingfile>", lab[k], "</processingfile>", sep=""), paste("<percentdone>", (k*100)%/%w - 1, "</percentdone>", sep=""), "<tree>hist.xml</tree>", "</status>"), statusFile)
  close(statusFile)

  MEAN=round( mean( sort(T[,(k+2)])[round(l*.01) : (l-round(l*.01))] ), digits=1)
  STD=round( sd( sort(T[,(k+2)])[round(l*.01) : (l-round(l*.01))] ), digits=1)

  allStats[k,1]=lab[k]
  allStats[k,2]=sum(T[,(k+2)])
  allStats[k,3]=MEAN
  allStats[k,4]=STD
  allStats[k,5]=min(T[,(k+2)])
  allStats[k,6]=quantile(T[,(k+2)], c(.25))[[1]]
  allStats[k,7]=median(T[,(k+2)])
  allStats[k,8]=quantile(T[,(k+2)], c(.75))[[1]]
  allStats[k,9]=max(T[,(k+2)])

  if ( (sum(T[,(k+2)])/l < 10) || (STD > MEAN) ) {
    allStats[k,10]=1
  } else {
    allStats[k,10]=0
  }

}

#Store processed sample information
write.table(allStats, file=paste(user_dir, "/SegStats", sep=""), row.names=FALSE, col.names=c("Sample", "Total Reads", "Mean", "Std", "Min", "25th", "Median", "75th", "Max", "Flag"), sep="\t")

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>1</step>", paste("<processingfile>Finished</processingfile>", sep=""), paste("<percentdone>100</percentdone>", sep=""), "<tree>hist.xml</tree>", "</status>"), statusFile)
close(statusFile)
