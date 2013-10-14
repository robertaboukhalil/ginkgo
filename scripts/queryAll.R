#!/usr/bin/env Rscript

args<-commandArgs(TRUE)

genome <- args[[1]]
user_dir <- args[[2]]
status <- args[[3]]
bm <- args[[4]]
query <- args[[5]]
f <- as.numeric(args[[6]])

library(gplots)
library(scales)
library(plyr)

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>5</step>", "<processingfile>Initializings</processingfile>", "<percentdone>0</percentdone>", "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

############################################################
########  Initialize Variables & Pre-Process Data ##########
############################################################

#Load bin/gene/boundary files
setwd(genome)
loc=read.table(bm, header=TRUE, sep="\t", as.is=TRUE)
genes=read.table("genes", header=FALSE, sep="\t", as.is=TRUE)
bounds=read.table(paste("bounds_", bm, sep=""), header=FALSE, sep="\t")

#Load user data files
setwd(user_dir)
normal=read.table("SegNorm", header=TRUE, sep="\t", as.is=TRUE)
fixed=read.table("SegFixed", header=TRUE, sep="\t", as.is=TRUE)
ploidy=read.table("results.txt", header=TRUE, sep="\t", as.is=TRUE)

if (f == 0) {
final=round(sweep(fixed, 2, ploidy[,4], '*'))
} else {
final=round(sweep(fixed, 2, ploidy[,6], '*'))
}

l=dim(normal)[1] #Number of bins
w=dim(normal)[2] #Number of samples
lab=colnames(normal) #Sample labels

type=try(dim(read.table(query, header=FALSE, sep="\t"))[2])
if(type == 3 ) {
  #Intervals queried by user
  q <- read.table(query, header=FALSE, sep="\t", as.is=TRUE)
  intervals=matrix(0, nrow=dim(q)[1], ncol=2)
  ints=array(0,dim(q)[1])
  for (j in 1:dim(q)[1]) {
    intervals[j,1]=min(which((loc[,1] == q[j,1]) & (as.numeric(loc[,2])>=q[j,2])))
    intervals[j,2]=min(which((loc[,1] == q[j,1]) & (as.numeric(loc[,2])>=q[j,3])))+1
  }
  for (j in 1:dim(q)[1]) {
    ints[j]=paste(q[j,1], ":", q[j,2], "-\n", q[j,3], "\n", sep="")
  }
}  else if (type == 1) {
  #Genes queried by user
  q <- read.table(query, header=FALSE, sep="\t", as.is=TRUE)[[1]]
  bin=t(array(0, length(q)))
  for (j in 1:length(q)) {
    chr=genes[[1]][which(genes[[4]]==q[j])]
    u=subset(loc, loc[[1]]==chr)[2] < genes[[2]][which(genes[[4]]==q[j])]
    bin[j]=as.integer(rownames(u)[max(which(u))])
  }
  q=reorder(q, rank(bin))
  bin=sort(bin)
} else {
  type = 2
}

colors=c("red", "gold2",  "turquoise3", "sienna1", "violetred3", "yellowgreen", "saddlebrown", "tomato3", "gold4", "springgreen4")


############################################################
##################  PROCESS ALL SAMPLES  ###################
############################################################

for(k in 1:w){

  statusFile<-file( paste(user_dir, "/", status, sep="") )
  writeLines(c("<?xml version='1.0'?>", "<status>", "<step>5</step>", paste("<processingfile>", lab[k], "</processingfile>", sep=""), paste("<percentdone>", (k*100)%/%w, "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
  close(statusFile)

  ############################################################
  ##############  Replot Copy Number Profile #################
  ############################################################

  jpeg(filename=paste(lab[k], "_result.jpeg", sep=""), width=2500, height=1000)

    par(mar = c(7.0, 7.0, 7.0, 3.0))

     if (type != 2) {

     layout(matrix(c(1,1,1,1,1,2), 1,6, byrow=TRUE))

      #Plot normalized read distribution
      if (f == 0) {
        plot(normal[,k]*ploidy[k,5], main=paste("Integer Copy Number Profile (Ploidy = ", ploidy[k,5], ")\n Using Sum of Squares Approach", sep=""), ylim=c(0, min(20, max(final[,k]))), type="n", xlab="Bin", ylab="Copy Number", cex.main=3, cex.axis=2, cex.lab=2)
      } else {
        plot(normal[,k]*ploidy[k,6], main=paste("Integer Copy Number Profile (Ploidy = ", ploidy[k,6], ")\n Using Provided Sample Ploidy", sep=""), type="n", ylim=c(0, min(20, max(final[,k]))), xlab="Bin", ylab="Copy Number", cex.main=3, cex.axis=2, cex.lab=2)
      }

      tu <- par('usr')
      par(xpd=FALSE)
      rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")

      if (f == 0) {
        points(normal[,k]*ploidy[k,5], pch=20, col=alpha('gray50', .5), cex=2)
      } else {
        points(normal[,k]*ploidy[k,6], pch=20, col=alpha('gray50', .5), cex=2)
      }

      abline(h=c(0:5, 10, 15), lty=2)

      #Plot overlaying copy number profile
      points(final[,k], pch=20, col="midnightblue", cex=2)
      if (length(which(final[,k] > 20)) >= 1) {
        points(which(final[,k] > 20), array(20,length(which(final[,k] > 20))), pch=23, col='midnightblue')
      }

      #Plot chrom boundaries
      abline(v=c(0,t(bounds[2]), l), lwd=1.5)

      if (type == 1) {

        #Plot gene locations
        for (j in 1:min(10, length(q))) {
          points(bin[j], final[,k][bin[j]], pch=13, cex=4, lwd=2, col=colors[j])
        }
        par(mar = c(2.0, 2.0, 2.0, 1.0))
        plot(0, type="n", axes=F, xlab="", ylab="")
        legend(.95, 0, q[1:min(c(10, length(q)))], col=colors[1:min(c(10, length(q)))], pch=13, cex=5, xjust=.45, yjust=.5, bg="gray85")
      }  else if (type == 3) {

        #Plot genomic intervals
        for (j in 1:min(10, dim(q)[1])) {
          points(seq(intervals[j,1], intervals[j,2]), final[seq(intervals[j,1], intervals[j,2]), k],  pch=20, cex=2, col=colors[j])
        }
        par(mar = c(2.0, 2.0, 2.0, 1.0))
        plot(0, type="n", axes=F, xlab="", ylab="")
        legend(.95, 0, ints, col=colors[1:min(c(10, dim(q)[1]))], pch=20, cex=3, xjust=.5, yjust=.5, bg="gray85")
      }

    }

    #IF THE USER PROVIDED NO INTERVALS/GENES:
    if (type == 2) {

      if (f == 0) {
        plot(normal[,k]*ploidy[k,5], main=paste("Integer Copy Number Profile (Ploidy = ", ploidy[k,5], ")\n Using Sum of Squares Approach", sep=""), ylim=c(0, min(20, max(final[,k]))), type="n", xlab="Bin", ylab="Copy Number", cex.main=3, cex.axis=2, cex.lab=2)
      } else {
        plot(normal[,k]*ploidy[k,6], main=paste("Integer Copy Number Profile (Ploidy = ", ploidy[k,6], ")\n Using Provided Sample Ploidy", sep=""), ylim=c(0, min(20, max(final[,k]))), type="n", xlab="Bin", ylab="Copy Number", cex.main=3, cex.axis=2, cex.lab=2)
      }

      tu <- par('usr')
      par(xpd=FALSE)
      rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")

      if (f == 0) {
        points(normal[,k]*ploidy[k,5], pch=20, col=alpha('gray50', .5), cex=2)
      } else {
        points(normal[,k]*ploidy[k,6], pch=20, col=alpha('gray50', .5), cex=2)
      }

      abline(h=c(0:5, 10, 15), lty=2)

      #Plot overlaying copy number profile
      points(final[,k], pch=20, col="midnightblue", cex=2)
      if (length(which(final[,k] > 20)) >= 1) {
        points(which(final[,k] > 20), array(20,length(which(final[,k] > 20))), pch=23, col='midnightblue')
    }

      #Plot chrom boundaries
      abline(v=c(0,t(bounds[2]), l), lwd=1.5)
    }

  dev.off()

}

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>5</step>", paste("<processingfile>Finished</processingfile>", sep=""), paste("<percentdone>100</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

