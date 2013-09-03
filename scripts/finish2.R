#!/usr/bin/env Rscript

args<-commandArgs(TRUE)

main_dir <- args[[1]]
user_dir <- args[[2]]
status <- args[[3]]
dat <- args[[4]]
bm <- args[[5]]
query <- args[[6]]

library(gplots) #visual plotting of tables
library(plyr)

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>5</step>", "<processingfile>Initializing Variables</processingfile>", "<percentdone>0</percentdone>", "<tree>hist.newick</tree>", "</status>"), statusFile)
close(statusFile)

#Load bin/gene/boundary files
setwd(main_dir)
binList=read.table(bm, header=FALSE, sep="\t", as.is=TRUE)
geneList=read.table("genes", header=FALSE, sep="\t", as.is=TRUE)
b=read.table(paste("bounds_", bm, sep=""), header=FALSE, sep="\t")

#Load user data files
setwd(user_dir)
T=read.table(dat, header=TRUE, sep="\t")
genes=read.table(query, header=FALSE, sep="\t", as.is=TRUE)[[1]]
aneuploid=read.table("SegAneuploid", header=TRUE, sep="\t", as.is=TRUE)
diploid=read.table("SegDiploid", header=TRUE, sep="\t", as.is=TRUE)
raw=read.table("SegRaw", header=TRUE, sep="\t", as.is=TRUE)
breaks=read.table("SegBreaks", header=TRUE, sep="\t", as.is=TRUE)

l=dim(raw)[1] #Number of bins
w=dim(raw)[2] #Number of samples
lab=colnames(raw) #Sample labels
cnt=length(genes) #Number of genes queried
bin=t(array(0, cnt))

#Find bin corresponding to requested gene
for (i in 1:cnt) {
  chr=geneList[[1]][which(geneList[[4]]==genes[i])]
  loc=geneList[[2]][which(geneList[[4]]==genes[i])]
  u=subset(binList, binList[[1]]==chr)[2]<loc
  bin[i]=as.integer(rownames(u)[max(which(u))])
}

genes=sort(reorder(genes, rank(bin)))  #sort genes by genomic location
bin=sort(bin) #sort genomic locations

colors=c("tomato3", "blue", "springgreen4", "violetred3", "turquoise3", "sienna1", "yellowgreen", "slateblue3", "gold2", "wheat")

#Replot with indicated gene
for(k in 1:w){

  statusFile<-file( paste(user_dir, "/", status, sep="") )
  writeLines(c("<?xml version='1.0'?>", "<status>", "<step>5</step>", paste("<processingfile>", lab[k], "</processingfile>", sep=""), paste("<percentdone>", (k*100)%/%w - 1, "</percentdone>", sep=""), "<tree>hist.xml</tree>", "</status>"), statusFile)
  close(statusFile)

  print(paste("Starting", k))

  #Load frequency distribution of pair-wise differences between read counts
  fd=read.table(paste(lab[k], "_fit", sep=""), header=TRUE, sep="\t", as.is=TRUE)
  
  #Create Stats Table
  stats=matrix(0, 11, 2)
  stats[1,1]="Total Reads:"
  stats[2,1]="Total Bins:"
  stats[3,1]=""
  stats[4,1]="STATISTIC"
  stats[5,1]="Mean:"
  stats[6,1]="Std:"
  stats[7,1]="Min:"
  stats[8,1]="25th:"
  stats[9,1]="Median:"
  stats[10,1]="75th:"
  stats[11,1]="Max:"

  stats[1,2]=allStats[k,2]
  stats[2,2]=l
  stats[3,2]=""
  stats[4,2]="READS/BIN"
  stats[5,2]=allStats[k,3]
  stats[6,2]=allStats[k,4]
  stats[7,2]=allStats[k,5]
  stats[8,2]=allStats[k,6]
  stats[9,2]=allStats[k,7]
  stats[10,2]=allStats[k,8]
  stats[11,2]=allStats[k,9]

  #Create histogram of reads/bin
  r=round(mean(T[,(k+2)])+5*sd(T[,(k+2)]))
  e=hist(T[,(k+2)], breaks=100*max(T[,(k+2)])/r)
  dev.off()

  jpeg(filename=paste(lab[k], ".jpeg", sep=""), width=2000, height=1400)

    layout(matrix(c(1, 2, 3, 3, 4, 4, 5, 5, 6, 6, 7, 7), 3, 4, byrow=TRUE))

    #plot stats table
    textplot(stats, halign="center", valign="center", show.rownames=FALSE, show.colnames=FALSE)


    #Plot gene legend
    plot(0, type="n", axes=F, xlab="", ylab="")
    legend(.95, 0, genes, col=colors[1:cnt], pch=13, cex=3.5, xjust=.5, yjust=.5)
    
    #Plot pair-wise read difference histogram
    hist(T[,(k+2)], breaks=100*max(T[,(k+2)])/r, xlim=range(1:r), ylim=range(1:round_any(max(e$counts), 1000, ceiling)), main="Histogram of Read Count Frequency", xlab="Read Count (reads/bin)")

    #Plot segmented read counts
    plot(raw[,k], main="Reads/Bin (After Segmentation)", xlab="Bin", ylab="Read Count")
    abline(v=t(b[2]), col='snow4')
    if (cnt <= 3) {
      for (i in 1:cnt) {
        abline(v=bin[i], h=raw[,k][bin[i]], lwd=2, col=colors[i])
      }
    } else {
      for (i in 1:cnt) {
        points(bin[i], raw[,k][bin[i]], pch=13, cex=4, lwd=2, col=colors[i])
      }
    }

    #Plot frequency distribution of pair-wise differences between read counts (contains peaks)
    plot(1:dim(fd)[1], fd[[1]], main="Density Plot: Frequency Distribution of All Pair-Wise Differences Between Bin Counts", xlab="Pair-wise Difference (# of reads)", ylab="% Sampled Density")

    #Plot diploid copy number profile
    plot(diploid[,k], main="Copy Number Profile (Assuming Diploid)", xlab="Bin", ylab="Copy Number")
    abline(v=t(b[2]), col='snow4')
    if (cnt <= 3) {
      for (i in 1:cnt) {
        abline(v=bin[i], h=diploid[,k][bin[i]], lwd=2, col=colors[i])
      }
    } else {
      for (i in 1:cnt) {
        points(bin[i], diploid[,k][bin[i]], pch=13, cex=4, lwd=2, col=colors[i])
      }
    }
    #Plot aneuploid copy number profile
    plot(aneuploid[,k], main="Copy Number Profile (Assuming Aneuploid)", xlab="Bin", ylab="Copy Number")
    if (cnt <= 3) {
      for (i in 1:cnt) {
        abline(v=bin[i], h=aneuploid[,k][bin[i]], lwd=2, col=colors[i])
      }
    } else {
      for (i in 1:cnt) {
        points(bin[i], aneuploid[,k][bin[i]], pch=13, cex=4, lwd=2, col=colors[i])
      }
    }

  dev.off()
}

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>5</step>", paste("<processingfile>Finished</processingfile>", sep=""), paste("<percentdone>100</percentdone>", sep=""), "<tree>hist.xml</tree>", "</status>"), statusFile)
close(statusFile)

