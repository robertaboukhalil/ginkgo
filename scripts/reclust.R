#!/usr/bin/env Rscript

args<-commandArgs(TRUE)

user_dir <- args[[1]]
status <- args[[2]]
dat <- args[[3]]
cm <- args[[4]]
dm <- args[[5]]
f <- as.numeric(args[[6]])
facs <- args[[7]]

library('ctc')
library(gplots)
library(plyr)

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>4</step>", "<processingfile>Initiliazing Variables</processingfile>", "<percentdone>0</percentdone>", "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

setwd(user_dir)
fixed=read.table("SegFixed", header=TRUE, sep="\t", as.is=TRUE)
breaks=read.table("SegBreaks", header=TRUE, sep="\t", as.is=TRUE)
ploidy=read.table("results.txt", header=TRUE, sep="\t", as.is=TRUE)

lab=colnames(fixed)

if (f == 0) {
final=round(sweep(fixed, 2, ploidy[,4], '*'))
} else {
final=round(sweep(fixed, 2, ploidy[,6], '*'))
}

l=dim(fixed)[1]
w=dim(fixed)[2]

############################################################
#################  Recompute Cluster (RC) ##################
############################################################

#Calculate read distance matrix for clustering
mat=matrix(0,nrow=w,ncol=w)
for (i in 1:w){
  statusFile<-file( paste(user_dir, "/", status, sep="") )
  writeLines(c("<?xml version='1.0'?>", "<status>", "<step>4</step>", "<processingfile>Recomputing Cluster (Read Count)</processingfile>", paste("<percentdone>", round(.47*i), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
  close(statusFile)

  for (j in 1:w){
     mat[i,j]=dist(rbind(fixed[,i], fixed[,j]), method = dm)
  }
}

#Create cluster of samples
d = dist(mat, method = dm)
clust = hclust(d, method = cm)
clust$labels = lab
write(hc2Newick(clust), file=paste(user_dir, "/cluster.newick", sep=""))

###
main_dir="/mnt/data/ginkgo/scripts"
command=paste("java -cp ", main_dir, "/forester_1025.jar org.forester.application.phyloxml_converter -f=nn ", user_dir, "/clust.newick ", user_dir, "/clust.xml", sep="");
unlink( paste(user_dir, "/clust.xml", sep="") );
system(command);
### 

#Plot read cluster
jpeg("clust.jpeg", width=2000, height=1400)
plot(clust)
dev.off()


############################################################
#################  Recompute Cluster (CN) ##################
############################################################

#Calculate copy number distance matrix for clustering
mat2=matrix(0,nrow=w,ncol=w)
  for (i in 1:w){
  statusFile<-file( paste(user_dir, "/", status, sep="") )
  writeLines(c("<?xml version='1.0'?>", "<status>", "<step>4</step>", "<processingfile>Recomputing Cluster (Copy Number)</processingfile>", paste("<percentdone>", round(.47*i)+48, "</percentdone>", sep=""), "</status>"), statusFile)
  close(statusFile)
    for (j in 1:w){
      mat2[i,j]=dist(rbind(final[,i], final[,j]), method = dm)
    }
  }


#Create cluster of samples
d2 = dist(mat2, method = dm)
clust2 = hclust(d2, method = cm)
clust2$labels = lab
write(hc2Newick(clust2), file=paste(user_dir, "/clust2.newick", sep=""))

###
main_dir="/mnt/data/ginkgo/scripts"
command=paste("java -cp ", main_dir, "/forester_1025.jar org.forester.application.phyloxml_converter -f=nn ", user_dir, "/clust2.newick ", user_dir, "/clust2.xml", sep="");
unlink( paste(user_dir, "/clust2.xml", sep="") );
system(command);
### 

#Plot copy number cluster
jpeg("clust2.jpeg", width=2000, height=1400)
plot(clust2)
dev.off()


############################################################
###################  Recreate Heat Maps ####################
############################################################

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>4</step>", "<processingfile>Recreating Heat Maps</processingfile>", "<percentdone>96</percentdone>", "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

rawBPs=as.matrix(breaks[unique(sort((which(breaks==1)%%l))),])
fixedBPs=as.matrix(fixed[unique(sort((which(breaks==1)%%l))),])
finalBPs=as.matrix(final[unique(sort((which(breaks==1)%%l))),])

colnames(rawBPs) <- lab
colnames(fixedBPs) <- lab
colnames(finalBPs) <- lab

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>4</step>", "<processingfile>Recreating Heat Maps</processingfile>", "<percentdone>97</percentdone>", "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

jpeg("heatRaw.jpeg", width=2000, height=1400)
heatmap.2(t(rawBPs), Colv=FALSE, Rowv=as.dendrogram(clust), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=bluered(3), cex.axis=2, cex.lab=1.5)
dev.off()

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>4</step>", "<processingfile>Recreating Heat Maps</processingfile>", "<percentdone>98</percentdone>", "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

step=quantile(fixedBPs, c(.98))[[1]]
jpeg("heatFixed.jpeg", width=2000, height=1400)
heatmap.2(t(fixedBPs), Colv=FALSE, Rowv=as.dendrogram(clust), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=bluered(15), breaks=seq(0,step,step/15), cex.axis=2, cex.lab=1.5)
dev.off()

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>4</step>", "<processingfile>Recreating Heat Maps</processingfile>", "<percentdone>99</percentdone>", "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

step=min(20, quantile(finalBPs, c(.98))[[1]])
jpeg("heatCN.jpeg", width=2000, height=1400)
heatmap.2(t(finalBPs), Colv=FALSE, Rowv=as.dendrogram(clust2), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=heat.colors(15), breaks=seq(0,step,step/15), cex.axis=2, cex.lab=1.5)
dev.off()

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>4</step>", "<processingfile>Finished</processingfile>", "<percentdone>100</percentdone>", "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

