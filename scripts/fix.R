#!/usr/bin/env Rscript

args<-commandArgs(TRUE)

user_dir <- args[[1]]
status <- args[[2]]
dat <- args[[3]]
cm <- args[[4]]
dm <- args[[5]]
f <- args[[6]]

library('ctc')

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>4</step>", "<processingfile>Computing Dendogram</processingfile>", "<percentdone>0</percentdone>", "<tree>hist.newick</tree>", "</status>"), statusFile)
close(statusFile)


setwd(user_dir)
normal=read.table("SegNorm", header=TRUE, sep="\t", as.is=TRUE)
lab=colnames(normal)

l=dim(normal)[1]
w=dim(normal)[2]

#Calculate read distance matrix for clustering
mat=matrix(0,nrow=w,ncol=w)
for (i in 1:w){
  for (j in 1:w){
     mat[i,j]=dist(rbind(normal[,i], normal[,j]), method = dm)
  }
}

#Create cluster of samples
d = dist(mat, method = dm)
T_clust = hclust(d, method = cm)
T_clust$labels = lab
write(hc2Newick(T_clust), file=paste(user_dir, "/hist.newick", sep=""))


###
main_dir="/mnt/data/ginkgo/scripts"
command=paste("java -cp ", main_dir, "/forester_1025.jar org.forester.application.phyloxml_converter -f=nn ", user_dir, "/hist.newick ", user_dir, "/hist.xml", sep="");
unlink( paste(user_dir, "/hist.xml", sep="") );
system(command);
### 

#Plot read cluster
jpeg("clust.jpeg", width=2000, height=1400)
plot(T_clust)
dev.off()


if (f == 1) {

  statusFile<-file( paste(user_dir, "/", status, sep="") )
  writeLines(c("<?xml version='1.0'?>", "<status>", "<step>4</step>", "<processingfile>Computing Copy Number Dendogram</processingfile>", "<percentdone>50</percentdone>", "<tree>hist2.newick</tree>", "</status>"), statusFile)
  close(statusFile)

  final=read.table("SegCopy", header=TRUE, sep="\t", as.is=TRUE)

  #Calculate copy number distance matrix for clustering
  mat2=matrix(0,nrow=w,ncol=w)
    for (i in 1:w){
      for (j in 1:w){
        mat2[i,j]=dist(rbind(final[,i], final[,j]), method = dm)
      }
    }

  #Create cluster of samples
  d2 = dist(mat2, method = dm)
  T_clust2 = hclust(d, method = cm)
  T_clust2$labels = lab
  write(hc2Newick(T_clust), file=paste(user_dir, "/hist2.newick", sep=""))

	###
	main_dir="/mnt/data/ginkgo/scripts"
	command=paste("java -cp ", main_dir, "/forester_1025.jar org.forester.application.phyloxml_converter -f=nn ", user_dir, "/hist2.newick ", user_dir, "/hist2.xml", sep="");
	unlink( paste(user_dir, "/hist2.xml", sep="") );
	system(command);
	### 


  #Plot copy number cluster
  jpeg("clust2.jpeg", width=2000, height=1400)
  plot(T_clust)
  dev.off()

}

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>4</step>", "<processingfile>Finished</processingfile>", "<percentdone>100</percentdone>", "<tree>hist.newick</tree>", "</status>"), statusFile)
close(statusFile)

