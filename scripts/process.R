#!/usr/bin/env Rscript

args<-commandArgs(TRUE)

genome <- args[[1]]
user_dir <- args[[2]]
status <- args[[3]]
dat <- args[[4]]
stat <- as.numeric(args[[5]])
bm <- args[[6]]
cm <- args[[7]]
dm <- args[[8]]
cp <- as.numeric(args[[9]])
ref <- args[[10]]
f <- as.numeric(args[[11]])
facs <- args[[12]]
sex <- as.numeric(args[[13]])
bb  <- as.numeric(args[[14]])

library('ctc')
library(DNAcopy) #segmentation
library(inline) #use of c++
library(gplots) #visual plotting of tables
library(scales)
library(plyr)

############################################################
########  Initialize Variables & Pre-Process Data ##########
############################################################

statusFile <- file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", "<processingfile>Initializing Variables</processingfile>", "<percentdone>0</percentdone>", "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

#Load genome specific files
setwd(genome)
GC <- read.table(paste("GC_", bm, sep=""), header=FALSE, sep="\t", as.is=TRUE)
loc <- read.table(bm, header=TRUE, sep="\t", as.is=TRUE)
bounds <- read.table(paste("bounds_", bm, sep=""), header=FALSE, sep="\t")

#load user data
setwd(user_dir)
raw <- read.table(dat, header=TRUE, sep="\t")
if (f == 1) { ploidy <- read.table(facs, header=FALSE, sep="\t", as.is=TRUE) }



if (bb) {
  print("Removing bad bins...")
  badbins <- read.table(paste(genome, "/badbins_", bm, sep=""), header=FALSE, sep="\t", as.is=TRUE)
  GC=data.frame(GC[-badbins[,1],1])
  loc=loc[-badbins[,1],]
  raw=data.frame(raw[-badbins[,1],])
}


#Initialize color palette
col1=col2=matrix(0,3,2)
col1[1,]=c('darkmagenta', 'goldenrod')
col1[2,]=c('darkorange', 'dodgerblue')
col1[3,]=c('blue4', 'brown2')
col2[,1]=col1[,2]
col2[,2]=col1[,1]

#Initialize data structures
l <- dim(raw)[1] #Number of bins
w <- dim(raw)[2] #Number of samples
breaks <- matrix(0,l,w)
fixed <- matrix(0,l,w)
final <- matrix(0,l,w)
stats <- matrix(0,w,10)
CNmult <- matrix(0,5,w)
CNerror <- matrix(0,5,w)
outerColsums <- matrix(0, 91, w)

#Normalize samples
normal <- sweep(raw+1, 2, colMeans(raw+1), '/')
normal2 <- normal
lab <- colnames(normal)

#Prepare statistics
rownames(stats) <- lab
colnames(stats) <- c("Reads", "Bins", "Mean", "Var", "Disp", "Min", "25th", "Median", "75th", "Max")

# #Correct GC biases
# matGC <- matrix(GC[,1], ncol=w, nrow=l ,byrow=FALSE)
# low <- lowess(matGC, log(as.matrix(normal)), f=0.05)
# app <- approx(low$x, low$y, matGC)
# normal <- exp(as.matrix(log(normal)) - app$y)

#Determine segmentation reference using:
##Dispersion index if stat=1
##Reference sample if stat=2
if (stat == 1) {
  F <- normal[,which.min(apply(normal, 2, sd)/apply(normal,2,mean))[1]]
} else if (stat == 2) {
  R <- read.table(ref, header=TRUE, sep="\t", as.is=TRUE)
  low <- lowess(GC[,1], log(R[,1]+0.001), f=0.05)
  app <- approx(low$x, low$y, GC[,1])
  F <- exp(log(R[,1]) - app$y)
}


############################################################
##################  PROCESS ALL SAMPLES  ###################
############################################################

#Open output stream
sink("results.txt")

if (f == 0) {
  out=paste("Sample", "SoSPredictedPloidy(Top5)", "ErrorInSoSApproach(Top5)", "CopyNumber(SoS)", sep="\t")
cat(out, "\n")
} else {
  out=paste("Sample", "SoSPredictedPloidy(Top5)", "ErrorInSoSApproach(Top5)", "CopyNumber(SoS)", "CopyNumber(Ploidy)", sep="\t")
cat(out, "\n")
}



for(k in 1:w){

  statusFile <- file( paste(user_dir, "/", status, sep="") )
  writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>", lab[k], "</processingfile>", sep=""), paste("<percentdone>", (k*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
  close(statusFile)

  #Generate basic statistics
  stats[k,1]=sum(raw[,k])
  stats[k,2]=l
  stats[k,3]=round(mean(raw[,k]), digits=2)
  stats[k,4]=round(sd(raw[,k]), digits=2)
  stats[k,5]=round(stats[k,4]/stats[k,3], digits=2)
  stats[k,6]=min(raw[,k])
  stats[k,7]=quantile(raw[,k], c(.25))[[1]]
  stats[k,8]=median(raw[,k])
  stats[k,9]=quantile(raw[,k], c(.75))[[1]]
  stats[k,10]=max(raw[,k])

  ############################################################
  #####################  Segment Sample  #####################
  ############################################################


  #######RA:
  # #Compute log ratio between kth sample and reference
  # if (stat == 0) {
  #   lr = -log2((normal[,k])/(mean(normal[,k])))
  # } else {
  #   lr = -log2((normal[,k])/(F))
  # }

  # Calculate normal for current cell (previous values of normal seem wrong)
  lowess.gc <- function(jtkx, jtky) {
    jtklow <- lowess(jtkx, log(jtky), f=0.05); 
    jtkz <- approx(jtklow$x, jtklow$y, jtkx)
    return(exp(log(jtky) - jtkz$y))
  }
  normal[,k] = lowess.gc( GC[,1], (raw[,k]+1)/mean(raw[,k]+1) )

  #Compute log ratio between kth sample and reference
  if (stat == 0) {
    lr = log2(normal[,k])
  } else {
    lr = log2((normal[,k])/(F))
  }
  #######/RA

  #Determine breakpoints and extract chrom/locations
  CNA.object <- CNA(genomdat = lr, chrom = loc[,1], maploc = as.numeric(loc[,2]), data.type = 'logratio')
  CNA.smoothed <- smooth.CNA(CNA.object)
  segs <- segment(CNA.smoothed, verbose=0, min.width=2)
  frag <- segs$output[,2:3]

  #Map breakpoints to kth sample
  len <- dim(frag)[1]
  bps <- array(0, len)
  for (j in 1:len){
    bps[j]=which((loc[,1]==frag[j,1]) & (as.numeric(loc[,2])==frag[j,2]))
  }
  bps <- sort(bps)
  bps[(len=len+1)] <- l

  #Track global breakpoint locations
  breaks[bps,k]=1

  #Modify bins to contain median read count/bin within each segment
  fixed[,k][1:bps[2]] <- median(normal[,k][1:bps[2]])
  for(i in 2:(len-1)){
    fixed[,k][bps[i]:(bps[i+1]-1)] = median(normal[,k][bps[i]:(bps[i+1]-1)])
  }
  fixed[,k] <- fixed[,k]/mean(fixed[,k])

  #######RA:
  # fixed[,k][1:bps[2]] <- mean(normal[,k][1:bps[2]])
  # for(i in 2:(len-1)){
  #   fixed[,k][bps[i]:(bps[i+1]-1)] = mean(normal[,k][bps[i]:(bps[i+1]-1)])
  # }
  # thisShort <- segs[[2]]
  # m <- matrix(data=0, nrow=nrow(normal), ncol=1)
  # prevEnd <- 0
  # for (i in 1:nrow(thisShort)) {
  #         thisStart <- prevEnd + 1
  #         thisEnd <- prevEnd + thisShort$num.mark[i]
  #         m[thisStart:thisEnd, 1] <- 2^thisShort$seg.mean[i]
  #         prevEnd = thisEnd
  # }
  # fixed[,k] <- m[, 1]
  # #above:#segs <- segment(CNA.smoothed, verbose=0, min.width=5, alpha=0.02,nperm=1000,undo.splits="sdundo",undo.SD=1.0)
  #######/RA



  ############################################################
  ###########  Determine Copy Number (SoS Method)  ###########
  ############################################################

  #Determine Copy Number     
  CNgrid <- seq(1.5, 6.0, by=0.05)
  outerRaw <- fixed[,k] %o% CNgrid
  outerRound <- round(outerRaw)
  outerDiff <- (outerRaw - outerRound) ^ 2
  outerColsums[,k] <- colSums(outerDiff, na.rm = FALSE, dims = 1)
  CNmult[,k] <- CNgrid[order(outerColsums[,k])[1:5]]
  CNerror[,k] <- round(sort(outerColsums[,k])[1:5], digits=2)
  print(CNmult[,k])

  if (f == 0) {
    final[,k] <- round(fixed[,k]*CNmult[1,k])
  } else {
    final[,k] <- round(fixed[,k]*ploidy[which(lab[k]==ploidy[,1]),2])
  }

  #Output results of CN calculations to file
  if (f == 0) {
    out=paste(lab[k], "\t", CNmult[1,k], ",", CNmult[2,k], ",", CNmult[3,k], ",", CNmult[4,k], ",", CNmult[5,k], "\t", CNerror[1,k], ",", CNerror[2,k], ",", CNerror[3,k], ",", CNerror[4,k], ",", CNerror[5,k], "\t", CNmult[1,k], sep="")
  cat(out, "\n")
  } else {
    out=paste(lab[k], "\t", CNmult[1,k], ",", CNmult[2,k], ",", CNmult[3,k], ",", CNmult[4,k], ",", CNmult[5,k], "\t", CNerror[1,k], ",", CNerror[2,k], ",", CNerror[3,k], ",", CNerror[4,k], ",", CNerror[5,k], "\t", CNmult[1,k], "\t", round(ploidy[which(lab[k]==ploidy[,1]),2], digits=2), sep="")
  cat(out, "\n")
  }

  ############################################################
  ################  Generate Plots & Figures #################
  ############################################################

  #Plot Distribution of Read Coverage
  jpeg(filename=paste(lab[k], "_dist.jpeg", sep=""), width=3000, height=750)
    par(mar = c(7.0, 7.0, 7.0, 3.0))

    top=round(quantile(raw[,k], c(.995))[[1]])

    plot(which(raw[,k]<top), raw[which(raw[,k]<top),k], main=paste("Genome Wide Read Distribution for Sample \"", lab[k], "\"", sep=""), xlab="Bin", ylab="Read count", type="n", ylim=c(0,top), cex.main=3, cex.axis=2, cex.lab=2)
    tu <- par('usr')
    par(xpd=FALSE)
    rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
    points(which(raw[,k]<top), raw[which(raw[,k]<top),k], pch=20, col=col1[cp,2], cex=1.5)
    points(which(raw[,k]>top), array(top, length(which(raw[,k]>top))), pch=23, cex=2, col=col1[cp,2])

  dev.off()

  #Plot histogram of bin counts
  jpeg(filename=paste(lab[k], "_counts.jpeg", sep=""), width=2500, height=1500)
    par(mar = c(7.0, 7.0, 7.0, 3.0))

    temp=sort(raw[,k])[round(l*.01) : (l-round(l*.01))] 
    reads <- hist(temp, breaks=100, plot=FALSE)
    plot(reads, col='black', main=paste("Frequency of Bin Counts for Sample ", lab[k], "\n(both tails trimmed 1%)", sep=""), xlab="Read Count (reads/bin)", xaxt="n", cex.main=3, cex.axis=2, cex.lab=2)
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

  dev.off()

  #Plot lorenz curves
  jpeg(filename=paste(lab[k], "_lorenz.jpeg", sep=""), width=2500, height=1500)
    par(mar = c(7.0, 7.0, 7.0, 3.0))

    nReads=sum(raw[,k])
    uniq=unique(sort(raw[,k]))
  
    lorenz=matrix(0, nrow=length(uniq), ncol=2)
    a=c(length(which(raw[,k]==0)), tabulate(raw[,k], nbins=max(raw[,k])))
    b=a*(0:(length(a)-1))
    for (i in 2:length(uniq)) {
      lorenz[i,1]=sum(a[1:uniq[i]])/l
      lorenz[i,2]=sum(b[2:uniq[i]])/nReads
    }

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
    try(lines(smooth.spline(lorenz), col=col1[cp,2], lwd=2.5), silent=TRUE)
    lines(c(0,1), c(0,1), lwd=2.5)
    tu <- par('usr')
    par(xpd=FALSE)
    legend("topleft", inset=.05, legend=c("Perfect Uniformity", "Sample Uniformity"), fill=c("black", col1[cp,2]), cex=2.5)

  dev.off()

  #Plot GC correction
  jpeg(filename=paste(lab[k], "_GC.jpeg", sep=""), width=2500, height=1250)
    par(mar = c(7.0, 7.0, 7.0, 3.0))
    layout(matrix(c(1,2), 1, 2, byrow=TRUE))

    low <- lowess(GC[,1], log(normal2[,k]), f=0.05)
    app <- approx(low$x, low$y, GC[,1])
    cor <- exp(log(normal2[,k]) - app$y)
    
    try(plot(GC[,1], log(normal2[,k]), main=paste("GC Content vs. Bin Count\nSample ", lab[k], " (Uncorrected)", sep=""), type= "n", xlim=c(min(.3, min(GC[,1])), max(.6, max(GC[,1]))), xlab="GC content", ylab="Normalized Read Counts (log scale)", cex.main=3, cex.axis=2, cex.lab=2))
    tu <- par('usr')
    par(xpd=FALSE)
    rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
    abline(v=axTicks(1), col="white", lwd=2)
    abline(h=axTicks(2), col="white", lwd=2)
    points(GC[,1], log(normal2[,k]))
    points(app, col=col1[cp,2])
    legend("bottomright", inset=.05, legend="Lowess Fit", fill=col1[cp,2], cex=2.5)

    try(plot(GC[,1], log(cor), main=paste("GC Content vs. Bin Count\nSample ", lab[k], " (Corrected)", sep=""), type= "n", xlim=c(min(.3, min(GC[,1])), max(.6, max(GC[,1]))), xlab="GC content", ylab="Normalized Read Counts (log scale)", cex.main=3, cex.axis=2, cex.lab=2))
    tu <- par('usr')
    par(xpd=FALSE)
    rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
    abline(v=axTicks(1), col="white", lwd=2)
    abline(h=axTicks(2), col="white", lwd=2)
    points(GC[,1], log(cor))

  dev.off()

  #Plot Scaled/Normalized Bin Count Histogram
  jpeg(filename=paste(lab[k], "_hist.jpeg", sep=""), width=2500, height=1500)
    par(mar = c(7.0, 7.0, 7.0, 3.0))

    if (f == 0) {
      reads <- hist(normal[,k]*CNmult[1,k], breaks=seq(0,ceiling(max(normal[,k]*CNmult[1,k])),.05), plot=FALSE)
      plot(reads, col='gray50', main=paste("Frequency of Bin Counts for Sample \"", lab[k], "\"\nNormalized and Scaled by Predicted CN (", CNmult[1,k], ")", sep=""), xlab="Copy Number", xlim=c(0,10), cex.main=3, cex.axis=2, cex.lab=2)
    } 
    else {
      reads <- hist(normal[,k]*ploidy[which(lab[k]==ploidy[,1]),2], breaks=seq(0,ceiling(max(normal[,k]*ploidy[which(lab[k]==ploidy[,1]),2])),.05), plot=FALSE)
      plot(reads, col='gray50', main=paste("Frequency of Bin Counts for Sample \"", lab[k], "\"\nNormalized and Scaled by Provided Ploidy (", ploidy[which(lab[k]==ploidy[,1]),2], ")", sep=""), xlab="Copy Number", xlim=c(0,10), cex.main=3, cex.axis=2, cex.lab=2) 
     }

    tu <- par('usr')
    par(xpd=FALSE)
    rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
    abline(h=axTicks(2), col="white", lwd=2)
    abline(v=seq(0,10,1), col="white", lwd=2)
    plot(reads, col='gray50', add=TRUE) 
    abline(v=seq(0,10,1), col=col1[cp,2], lty=2, lwd=3) 
  
  dev.off()

  #Plot sum of squares error for each potential copy number
  jpeg(filename=paste(lab[k], "_SoS.jpeg", sep=""), width=2500, height=1500)
    par(mar = c(7.0, 7.0, 7.0, 3.0))

    plot(CNgrid, outerColsums[,k], xlim=c(1,6), type= "n", xaxt="n", main="Sum of Squares Error Across Potential Copy Number States", xlab="Copy Number Multiplier", ylab="Sum of Squares Error", cex.main=3, cex.axis=2, cex.lab=2)
    tu <- par('usr')
    par(xpd=FALSE)
    rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
    abline(v=seq(1, 6, .25), col="white", lwd=2)
    abline(h=axTicks(2), col="white", lwd=2)
    points(CNgrid, outerColsums[,k], "b", xaxt="n", lwd=3.5)
    points(CNmult[1,k], CNerror[1,k], pch=23, cex=4, lwd=3.5, col=col1[cp,2])
    axis(side=1, at=1:6, cex.axis=2)
    legend("topright", inset=.05, legend=c("Lowest SoS Error"), fill=col1[cp,2], cex=2.5)

  dev.off()

  #Plot colored CN profile
  jpeg(filename=paste(lab[k], "_CN.jpeg", sep=""), width=3000, height=750)
    par(mar = c(7.0, 7.0, 7.0, 3.0))

    if (f == 0) { 
      plot(normal[,k]*CNmult[1,k], main=paste("Integer Copy Number Profile for Sample \"", lab[k], "\"\n Predicted Ploidy = ", CNmult[1,k], sep=""), ylim=c(0, 8), type="n", xlab="Bin", ylab="Copy Number", cex.main=3, cex.axis=2, cex.lab=2)

      tu <- par('usr')
      par(xpd=FALSE)
      rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")

      flag=1
      abline(h=0:19, lty=2)

      points(normal[(0:bounds[1,2]),k]*CNmult[1,k], ylim=c(0, 6), pch=20, cex=2, col=alpha(col1[cp,flag], .2))
      points(final[(0:bounds[1,2]),k], ylim=c(0, 8), pch=20, cex=2, col=alpha(col2[cp,flag], .2))
      for (i in 1:(dim(bounds)[1]-1)){
        points((bounds[i,2]:bounds[(i+1),2]), normal[(bounds[i,2]:bounds[(i+1),2]),k]*CNmult[1,k], ylim=c(0, 6), pch=20, cex=2, col=alpha(col2[cp,flag], .2))
        points((bounds[i,2]:bounds[(i+1),2]), final[(bounds[i,2]:bounds[(i+1),2]),k], ylim=c(0, 8), pch=20, cex=2, col=alpha(col1[cp,flag], .2))
        if (flag == 1) { flag = 2} else {flag = 1}
      }
      points((bounds[(i+1),2]:l), normal[(bounds[(i+1),2]:l),k]*CNmult[1,k], ylim=c(0, 8), pch=20, cex=2, col=alpha(col2[cp,flag], .2))
      points((bounds[(i+1),2]:l), final[(bounds[(i+1),2]:l),k], ylim=c(0, 6), pch=20, cex=2, col=alpha(col1[cp,flag], .2))
 
      dev.off()
    }
    else {
      plot(normal[,k]*ploidy[which(lab[k]==ploidy[,1]),2], main=paste("Integer Copy Number Profile for Sample \"", lab[k], "\"\n Provided Ploidy = ", ploidy[which(lab[k]==ploidy[,1]),2], sep=""), ylim=c(0, 8), type="n", xlab="Bin", ylab="Copy Number", cex.main=3, cex.axis=2, cex.lab=2)

      tu <- par('usr')
      par(xpd=FALSE)
      rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")

      flag=1
      abline(h=0:19, lty=2)

      points(normal[(0:bounds[1,2]),k]*ploidy[which(lab[k]==ploidy[,1]),2], ylim=c(0, 6), pch=20, cex=2, col=alpha(col1[cp,flag], .2))
      points(final[(0:bounds[1,2]),k], ylim=c(0, 8), pch=20, cex=2, col=alpha(col2[cp,flag], .2))
      for (i in 1:(dim(bounds)[1]-1)){
        points((bounds[i,2]:bounds[(i+1),2]), normal[(bounds[i,2]:bounds[(i+1),2]),k]*ploidy[which(lab[k]==ploidy[,1]),2], ylim=c(0, 6), pch=20, cex=2, col=alpha(col2[cp,flag], .2))
        points((bounds[i,2]:bounds[(i+1),2]), final[(bounds[i,2]:bounds[(i+1),2]),k], ylim=c(0, 8), pch=20, cex=2, col=alpha(col1[cp,flag], .2))
        if (flag == 1) { flag = 2} else {flag = 1}
      }
      points((bounds[(i+1),2]:l), normal[(bounds[(i+1),2]:l),k]*ploidy[which(lab[k]==ploidy[,1]),2], ylim=c(0, 8), pch=20, cex=2, col=alpha(col2[cp,flag], .2))
      points((bounds[(i+1),2]:l), final[(bounds[(i+1),2]:l),k], ylim=c(0, 6), pch=20, cex=2, col=alpha(col1[cp,flag], .2))
 
      dev.off()
    }

}


############################################################
##################  Save Processed Data  ###################
############################################################

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Saving Data</processingfile>", sep=""), paste("<percentdone>", (w*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

#Close output stream
sink()

#Store processed sample information
write.table(normal, file=paste(user_dir, "/SegNorm", sep=""), row.names=FALSE, col.names=lab, sep="\t")
write.table(fixed, file=paste(user_dir, "/SegFixed", sep=""), row.names=FALSE, col.names=lab, sep="\t")
write.table(final, file=paste(user_dir, "/SegCopy", sep=""), row.names=FALSE, col.names=lab, sep="\t")
write.table(breaks, file=paste(user_dir, "/SegBreaks", sep=""), row.names=FALSE, col.names=lab, sep="\t")
write.table(stats, file=paste(user_dir, "/SegStats", sep=""), sep="\t")

############################################################
##################  Generate Dendrograms ###################
############################################################

statusFile <- file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Computing Cluster (Read Count)</processingfile>", sep=""), paste("<percentdone>", ((w+1)*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

#Ignore sex chromomes if specified
if (sex == 0) {
  l=bounds[(dim(bounds)-1)[1],][[2]]-1
  raw=raw[1:l,]
  final=final[1:l,]
  fixed=fixed[1:l,]
  breaks=breaks[1:l,]
  normal=normal[1:l,]
}

#Calculate read distance matrix for clustering

#jk mat=matrix(0,nrow=w,ncol=w)
#jk   for (i in 1:w){
#jk     for (j in 1:w){
#jk      mat[i,j]=dist(rbind(fixed[,i], fixed[,j]), method = dm)
#jk     }
#jk   }

#Create cluster of samples
#jk d <- dist(mat, method = dm)
d <- dist(t(fixed), method=dm)
if(cm == "NJ"){
  library(ape)
  clust <- nj(d)
  clust$tip.label <- lab
  write.tree(clust, file=paste(user_dir, "/clust.newick", sep=""))
}else{
  clust <- hclust(d, method = cm)
  clust$labels <- lab  
  write(hc2Newick(clust), file=paste(user_dir, "/clust.newick", sep=""))
}

###
main_dir="/mnt/data/ginkgo/scripts"
command=paste("java -cp ", main_dir, "/forester_1025.jar org.forester.application.phyloxml_converter -f=nn ", user_dir, "/clust.newick ", user_dir, "/clust.xml", sep="");
unlink( paste(user_dir, "/clust.xml", sep="") );
system(command);
###

#Plot read cluster
jpeg("clust.jpeg", width=2000, height=1400)
  op = par(bg = "gray85")
  plot(clust, xlab="Sample", hang=-1, ylab=paste("Distance (", dm, ")", sep=""), lwd=2)
dev.off()

pdf("clust.pdf", width=10, height=7)
  op = par(bg = "gray85")
  plot(clust, xlab="Sample", hang=-1, ylab=paste("Distance (", dm, ")", sep=""), lwd=2)
dev.off()

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Computing Cluster (Copy Number)</processingfile>", sep=""), paste("<percentdone>", ((w+2)*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

#Calculate copy number distance matrix for clustering
#jk mat2=matrix(0,nrow=w,ncol=w)
#jk   for (i in 1:w){
#jk     for (j in 1:w){
#jk       mat2[i,j]=dist(rbind(final[,i], final[,j]), method = dm)
#jk     }
#jk   }

#Create cluster of samples
d2 <- dist(t(final), method = dm)
#clust2 <- hclust(d2, method = cm)
#clust2$labels <- lab
#write(hc2Newick(clust2), file=paste(user_dir, "/clust2.newick", sep=""))
if(cm == "NJ"){
  library(ape)
  clust2 <- nj(d2)
  clust2$tip.label <- lab
  write.tree(clust2, file=paste(user_dir, "/clust2.newick", sep=""))
}else{
  clust2 <- hclust(d2, method = cm)
  clust2$labels <- lab  
  write(hc2Newick(clust2), file=paste(user_dir, "/clust2.newick", sep=""))
}


###
main_dir="/mnt/data/ginkgo/scripts"
command=paste("java -cp ", main_dir, "/forester_1025.jar org.forester.application.phyloxml_converter -f=nn ", user_dir, "/clust2.newick ", user_dir, "/clust2.xml", sep="");
unlink( paste(user_dir, "/clust2.xml", sep="") );
system(command);
###

#Plot copy number cluster
jpeg("clust2.jpeg", width=2000, height=1400)
  op = par(bg = "gray85")
  plot(clust2, xlab="Sample", hang=-1, ylab=paste("Distance (", dm, ")", sep=""), lwd=2)
dev.off()

pdf("clust2.pdf", width=10, height=7)
  op = par(bg = "gray85")
  plot(clust2, xlab="Sample", hang=-1, ylab=paste("Distance (", dm, ")", sep=""), lwd=2)
dev.off()


#Calculate correlation distance matrix for clustering
d3 <- as.dist((1 - cor(final))/2)
#clust3=hclust(d3, method = cm)
#clust3$labels=lab
#write(hc2Newick(clust3), file=paste(user_dir, "/clust3.newick", sep=""))
if(cm == "NJ"){
  library(ape)
  clust3 <- nj(d3)
  clust3$tip.label <- lab
  write.tree(clust3, file=paste(user_dir, "/clust3.newick", sep=""))
}else{
  clust3 <- hclust(d3, method = cm)
  clust3$labels <- lab  
  write(hc2Newick(clust3), file=paste(user_dir, "/clust3.newick", sep=""))
}


###
main_dir="/mnt/data/ginkgo/scripts"
command=paste("java -cp ", main_dir, "/forester_1025.jar org.forester.application.phyloxml_converter -f=nn ", user_dir, "/clust3.newick ", user_dir, "/clust3.xml", sep="");
unlink( paste(user_dir, "/clust3.xml", sep="") );
system(command);
### 

#Plot correlation cluster
jpeg("clust3.jpeg", width=2000, height=1400)
  op = par(bg = "gray85")
  plot(clust3, xlab="Sample", hang=-1, ylab="Distance (Pearson correlation)", lwd=2)
dev.off()

pdf("clust3.pdf", width=10, height=7)
  op = par(bg = "gray85")
  plot(clust3, xlab="Sample", hang=-1, ylab="Distance (Pearson correlation)", lwd=2)
dev.off()

############################################################
####################  Generate Heat Maps ###################
############################################################

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Creating Heat Maps</processingfile>", sep=""), paste("<percentdone>", ((w+3)*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

#Create breakpoint heatmaps
rawBPs=breaks[unique(sort((which(breaks==1)%%l))),]
fixedBPs=fixed[unique(sort((which(breaks==1)%%l))),]
finalBPs=final[unique(sort((which(breaks==1)%%l))),]
colnames(rawBPs) <- lab
colnames(fixedBPs) <- lab
colnames(finalBPs) <- lab


# RA: Need to root NJ tree, make tree ultrametric by extending branch lengths then convert to hclust object!
phylo2hclust <- function(phy) {
  # Root tree
  clustR = root(phy, outgroup=1, resolve.root=TRUE)
  # If edge lengths are exactly 0, chronopl will delete those edges.....
  clustRE= clustR$edge.length
  clustRE[which(clustRE == 0)] = clustRE[which(clustRE == 0)]+ 1e-4
  clustRE[which(clustRE < 0)] = 1e-4
  clustR$edge.length = clustRE
  # Chronopl to make tree ultrametric
  clustU = chronopl(clustR, 0)
  phy  = as.hclust(clustU)
  return(phy)
}

#
if(cm == "NJ"){
  clust  = phylo2hclust(clust)
  clust2 = phylo2hclust(clust2)
  clust3 = phylo2hclust(clust3)
}


jpeg("heatRaw.jpeg", width=2000, height=1400)
heatmap.2(t(rawBPs), Colv=FALSE, Rowv=as.dendrogram(clust), margins=c(5,20), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=bluered(2))
dev.off()

step=quantile(fixedBPs, c(.98))[[1]]
jpeg("heatNorm.jpeg", width=2000, height=1400)
heatmap.2(t(fixedBPs), Colv=FALSE, Rowv=as.dendrogram(clust), margins=c(5,20), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=bluered(15), breaks=seq(0,step,step/15))
dev.off()

step=min(20, quantile(finalBPs, c(.98))[[1]])
jpeg("heatCN.jpeg", width=2000, height=1400)
heatmap.2(t(finalBPs), Colv=FALSE, Rowv=as.dendrogram(clust2), margins=c(5,20), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=colorRampPalette(c("white","green","green4","violet","purple"))(15), breaks=seq(0,step,step/15))
dev.off()

jpeg("heatCor.jpeg", width=2000, height=1400)
heatmap.2(t(finalBPs), Colv=FALSE, Rowv=as.dendrogram(clust3), margins=c(5,20), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=colorRampPalette(c("white","steelblue1","steelblue4","orange","sienna3"))(15), breaks=seq(0,step,step/15))
dev.off()

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Finished</processingfile>", sep=""), paste("<percentdone>100</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

