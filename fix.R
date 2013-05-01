#!/usr/bin/env Rscript

args<-commandArgs(TRUE)

main_dir <- args[[1]]
user_dir <- args[[2]]
status <- args[[3]]
dat <- args[[4]]
cm <- as.numeric(args[[5]])
dm <- as.numeric(args[[6]])

library('ctc')

setwd(user_dir)
T=read.table(dat, header=TRUE, sep="\t")
print(user_dir);
print(dat);
lab=colnames(T[1:dim(T)[2]])

clust_meth=c("single", "complete", "average", "ward")
dist_metric=c("euclidean", "maximum", "manhattan", "canberra", "binary", "minkowski")

l=dim(T)[1]
w=dim(T)[2]

mat=matrix(0,nrow=w,ncol=w)
for (i in 1:w){
  for (j in 1:w){    
     mat[i,j]=dist(rbind(T[,i], T[,j]))
  }
}

d = dist(mat, method = dist_metric[dm+1])
T_clust = hclust(d, method = clust_meth[cm+1])
T_clust$labels = lab

write(hc2Newick(T_clust), file=paste(user_dir, "/hist.newick", sep=""))
#write(hc2Newick(T_clust), file="hist.newick")

statusFile<-file( paste(user_dir, "/", status, sep="") )
#statusFile<-file(status)
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>4</step>", "<processingfile>Finished</processingfile>", "<percentdone>100</percentdone>", "<tree>hist.xml</tree>", "</status>"), statusFile)
close(statusFile)


###
command=paste("java -cp ", main_dir, "/forester_1025.jar org.forester.application.phyloxml_converter -f=nn ", user_dir, "/hist.newick ", user_dir, "/hist.xml", sep="");
#unlink( paste(user_dir, "/hist.xml", sep="") );
system(command);
###	  

