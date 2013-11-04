#!/usr/bin/env Rscript

args<-commandArgs(TRUE)

user_dir <- args[[1]]
status <- args[[2]]
dat <- args[[3]]

library(gplots)
library(plyr)


############################################################
##################  Initialize Variables  ##################
############################################################

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>2</step>", "<processingfile>Initializing</processingfile>", "<percentdone>0</percentdone>", "<tree>hist.newick</tree>", "</status>"), statusFile)
close(statusFile)

setwd(user_dir)
raw=read.table(dat, header=TRUE, sep="\t")

l=dim(raw)[1] #Number of bins
w=dim(raw)[2] #Number of samples
lab=colnames(raw) #Sample labels

allStats=matrix(0, w+1, 12)
allStats[1,1]="Sample"
allStats[1,2]="TotalReads"
allStats[1,3]="TotalBins"
allStats[1,4]="Mean"
allStats[1,5]="Variance"
allStats[1,6]="Disp"
allStats[1,7]="Min"
allStats[1,8]="25th"
allStats[1,9]="Median"
allStats[1,10]="75th"
allStats[1,11]="Max"
allStats[1,12]="Flag"


############################################################
##################  PROCESS ALL SAMPLES  ###################
############################################################

for(k in 1:w){

  statusFile<-file( paste(user_dir, "/", status, sep="") )
  writeLines(c("<?xml version='1.0'?>", "<status>", "<step>2</step>", paste("<processingfile>", lab[k], "</processingfile>", sep=""), paste("<percentdone>", (k*100)%/%w, "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
  close(statusFile)

  #Generate Lorenz curve to examine coverage uniformity
  nReads=sum(raw[,1])
  nBins=l
  uniq=unique(sort(raw[,1]))

  lorenz=matrix(0, nrow=length(uniq), ncol=2)
  a=tabulate(raw[,1], nbins=max(raw[,1]))
  b=a*(1:length(a))
  for (i in 2:length(uniq)) {
    lorenz[i,1]=sum(a[1:uniq[i]])/nBins
    lorenz[i,2]=sum(b[1:uniq[i]])/nReads
  }
  
  #Generate basic statistics
  allStats[(k+1),1]=lab[k]
  allStats[(k+1),2]=sum(raw[,k])
  allStats[(k+1),3]=l
  allStats[(k+1),4]=MEAN=round(mean(raw[,k]), digits=2)
  allStats[(k+1),5]=VAR=round(sd(raw[,k]), digits=2)
  allStats[(k+1),6]=round(as.numeric(allStats[(k+1),5])/as.numeric(allStats[(k+1),4]), digits=2)
  allStats[(k+1),7]=min(raw[,k])
  allStats[(k+1),8]=quantile(raw[,k], c(.25))[[1]]
  allStats[(k+1),9]=median(raw[,k])
  allStats[(k+1),10]=quantile(raw[,k], c(.75))[[1]]
  allStats[(k+1),11]=max(raw[,k])

  if ( (sum(raw[,k])/l < 10) || (VAR >= 1.5*MEAN) ) {
    allStats[(k+1),12]=2
  } else if ( (mean(raw[,k]) < 25) || (VAR >= MEAN) ) {
    allStats[(k+1),12]=1
  } else {
    allStats[(k+1),12]=0
  }


  ############################################################
  ################  Plot Summary Statistics ##################
  ############################################################

  jpeg(filename=paste(lab[k], "_stats.jpeg", sep=""), width=2500, height=1500)

    par(mar = c(7.0, 7.0, 7.0, 3.0))
    layout(matrix(c(1, 1, 2, 3), 2, 2, byrow=TRUE))

    #Plot Distribution of Read Coverage
    step=ceiling(l/50000)
    hold=tapply(raw[,k], cut(1:l-(l%%step), seq(0,l-(l%%step),step)), FUN=sum)
    top=max(sort(hold)[1:(length(hold)-round(length(hold)*.001))])/sum(hold)

    plot(hold/sum(hold), main=paste("Genome Wide Read Distribution for Sample ", lab[k], "\n(~", round(length(hold)/1000, digits=1), "k total bins)", sep=""), xlab="Bin", ylab="Fraction of reads", type="n", ylim=c(0,top), cex.main=3, cex.axis=2, cex.lab=2)
    tu <- par('usr')
    par(xpd=FALSE)
    rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
    points(hold/sum(hold), pch=20, col="gray50", cex=1.5)
    points(which(hold/sum(hold)>top), array(tu[4]-.015*diff(tu)[3], length(which(hold/sum(hold)>top))), pch=23, col="gray50", cex=1.5)


    #plot histogram of read count frequency
    temp=sort(raw[,k])[round(l*.01) : (l-round(l*.01))] 
    reads <- hist(temp, breaks=100, plot=FALSE)
    plot(reads, col='black', main=paste("Histogram of Read Count Frequency for Sample ", lab[k], "\n(both tails trimmed 1%)", sep=""), xlab="Read Count (reads/bin)", xaxt="n", cex.main=3, cex.axis=2, cex.lab=2)
    axis(side=1, at=seq(min(temp), round(diff(range(temp))/20)*22, round(diff(range(temp))/20)), cex.axis=2)
    tu <- par('usr')
    par(xpd=FALSE)
    clip(tu[1], mean(temp)-(diff(reads$mids)/2), tu[3], tu[4])
    plot(reads, col='gray50', add=TRUE)
    clip(mean(temp)+(diff(reads$mids)/2), tu[2], tu[3], tu[4])
    plot(reads, col='gray50', add=TRUE)
    clip(tu[1], mean(temp) - sd(temp), tu[3], tu[4])
    plot(reads, col='gray75', add=TRUE)
    clip(mean(temp) + sd(temp), tu[2], tu[3], tu[4])
    plot(reads, col='gray75', add=TRUE)
    clip(tu[1], mean(temp) - 2*sd(temp), tu[3], tu[4])
    plot(reads, col='gray90', add=TRUE)
    clip(mean(temp) + 2*sd(temp), tu[2], tu[3], tu[4])
    plot(reads, col='gray90', add=TRUE) 
    legend("topright", inset=.05, legend=c("mean", "< 1σ", "> 1σ", "> 2σ"), fill=c("black", "gray50", "gray75", "gray90"), cex=2.5)

    #plot lorenz curves
    plot(lorenz, xlim=c(0,1), main=paste("Lorenz Curve of Coverage Uniformity for Sample ", lab[k], sep=""), xlab="Cumulative Fraction of Genome", ylab="Cumulative Fraction of Total Reads", type="n", xaxt="n", yaxt="n", cex.main=3, cex.axis=2, cex.lab=2)
    tu <- par('usr')
    par(xpd=FALSE)
    rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
    abline(h=seq(0,1,.1), col="white", lwd=2)
    abline(v=seq(0,1,.1), col="white", lwd=2)
    axis(side=1, at=seq(0,1,.1), tcl=.5, cex.axis=2)
    axis(side=2, at=seq(0,1,.1), tcl=.5, cex.axis=2)
    axis(side=3, at=seq(0,1,.1), tcl=.5, cex.axis=2, labels=FALSE)
    axis(side=4, at=seq(0,1,.1), tcl=.5, cex.axis=2, labels=FALSE)
    lines(smooth.spline(lorenz), col="midnightblue", lwd=2.5)
    lines(c(0,sum(raw[,1]>0)/nBins), c(0,1), lwd=2.5)
    abline(v=sum(raw[,1]>0)/nBins, lty=2, col="midnightblue", lwd=2.5)
    tu <- par('usr')
    par(xpd=FALSE)
    legend("topleft", inset=.05, legend=c("Perfect Uniformity", "Sample Uniformity"), fill=c("black", "midnightblue"), cex=2.5)

  dev.off()

}


############################################################
##################  Save Processed Data  ###################
############################################################

write.table(allStats, file=paste(user_dir, "/SegStats", sep=""), row.names=FALSE, col.names=FALSE, sep="\t")

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>2</step>", paste("<processingfile>Finished</processingfile>", sep=""), paste("<percentdone>100</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

