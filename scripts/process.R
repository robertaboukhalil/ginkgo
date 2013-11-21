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
query <- args[[9]]
ref <- args[[10]]
f <- as.numeric(args[[11]])
facs <- args[[12]]

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
genes <- read.table("genes", header=FALSE, sep="\t", as.is=TRUE)
bounds <- read.table(paste("bounds_", bm, sep=""), header=FALSE, sep="\t")


#load user data
setwd(user_dir)
raw <- read.table(dat, header=TRUE, sep="\t")
if (f == 1) { ploidy <- read.table(facs, header=FALSE, sep="\t", as.is=TRUE) }

type=try(dim(read.table(query, header=FALSE, sep="\t"))[2])

if(type == 3 ) {
  #Intervals queried by user
  q <- read.table(query, header=FALSE, sep="\t", as.is=TRUE)
  intervals=matrix(0, nrow=dim(q)[1], ncol=2)
  ints=array(0, dim(q)[1])
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

#Initialize data structures
l <- dim(raw)[1] #Number of bins
w <- dim(raw)[2] #Number of samples
breaks <- matrix(0,l,w)
fixed <- matrix(0,l,w)
final <- matrix(0,l,w)
CNpeak <- array(0,w)
CNmult <- matrix(0,5,w)
CNerror <- matrix(0,5,w)

#normalalize samples
normal <- sweep(raw, 2, colMeans(raw), '/')
lab <- colnames(normal)

#Correct GC biases
matGC <- matrix(GC[,1], ncol=w, nrow=l ,byrow=TRUE)
low <- lowess(matGC, log(as.matrix(normal+.0000001)), f=0.05)
app <- approx(low$x, low$y, matGC)
normal <- exp(as.matrix(log(normal+.0000001)) - app$y)

#Determine segmentation reference using:
##Dispersion index if stat=1
##Reference sample if stat=2
if (stat == 1) {
  F <- normal[,which.min(apply(normal, 2, sd)/apply(normal,2,mean))[1]]
} else if (stat == 2) {
  R <- read.table(ref, header=TRUE, sep="\t", as.is=TRUE)
  low <- lowess(GC[,1], log(R[,1]+.0000001), f=0.05)
  app <- approx(low$x, low$y, GC[,1])
  F <- exp(log(R[,1]+.0000001) - app$y)
}


############################################################
##################  PROCESS ALL SAMPLES  ###################
############################################################

#Open output stream
sink("results.txt")

if (f == 0) {
  out=paste("Sample", "SoSPredictedPloidy(Top5)", "ErrorInSoSApproach(Top5)", "CopyNumber(SoS)", "CopyNumber(Peaks)", sep="\t")
cat(out, "\n")
} else {
  out=paste("Sample", "SoSPredictedPloidy(Top5)", "ErrorInSoSApproach(Top5)", "CopyNumber(SoS)", "CopyNumber(Peaks)", "CopyNumber(Ploidy)", sep="\t")
cat(out, "\n")
}

for(k in 1:w){

  statusFile <- file( paste(user_dir, "/", status, sep="") )
  writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>", lab[k], "</processingfile>", sep=""), paste("<percentdone>", (k*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
  close(statusFile)

  ############################################################
  #####################  Segment Sample  #####################
  ############################################################

  #Compute log ratio between kth sample and reference
  if (stat == 0) {
    lr = -log2((normal[,k]+1)/(mean(normal[,k]+1)))
  } else {
    lr = -log2((normal[,k]+1)/(F+1))
  }
    
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


  ############################################################
  ###########  Determine Copy Number (SoS Method)  ###########
  ############################################################

  #Determine Copy Number     
  CNgrid <- seq(1.5, 6.0, by=0.05)
  outerRaw <- fixed[,k] %o% CNgrid
  outerRound <- round(outerRaw)
  outerDiff <- (outerRaw - outerRound) ^ 2
  outerColsums <- colSums(outerDiff, na.rm = FALSE, dims = 1)
  CNmult[,k] <- CNgrid[order(outerColsums)[1:5]]
  CNerror[,k] <- round(sort(outerColsums)[1:5], digits=2)

  if (f == 0) {
    final[,k] <- round(fixed[,k]*CNmult[1,k])
  } else {
    final[,k] <- round(fixed[,k]*ploidy[which(lab[k]==ploidy[,1]),2])
  }


  ############################################################
  ##########  Determine Copy Number (Peaks Method)  ##########
  ############################################################

  #Determine frequency distribtion (h) of all pair-wise differences between bins
  t <- round(fixed[,k]*mean(raw[,k]))
  h <- rep(0,max(t)-min(t)+1)
  sig <- signature(l="integer", t="integer", h="integer")
  code <- "
    for (int i=0; i<(*l)-1; i++) {
      for (int j=(i+1); j<(*l); j++) {
        int d = abs(t[j] - t[i]);
        h[d]++;
      }
    }
  "
  func <- cfunction(sig, code, convention=".C")
  h <- func(l,t,h)$h
  
  #Fit spline (fd) to smooth frequency distribution 
  fd <- smooth.spline(1:length(h), h, spar=.35)

  #Find the second mode of the frequency distribution (equals reads/copy number)
  for(i in 5:(length(h)-2)){
    if ( (fd[[2]][i-2] < fd[[2]][i-1]) && (fd[[2]][i-1] < fd[[2]][i]) && (fd[[2]][i] > fd[[2]][i+1]) && (fd[[2]][i+1] > fd[[2]][i+2]) ) {
      break
    }
  }

  #Estimate Copy Number from peaks
  CNpeak[k] <- round(mean(t/i), digits=2)

  #Output results of CN calculations to file
  if (f == 0) {
    out=paste(lab[k], "\t", CNmult[1,k], ",", CNmult[2,k], ",", CNmult[3,k], ",", CNmult[4,k], ",", CNmult[5,k], "\t", CNerror[1,k], ",", CNerror[2,k], ",", CNerror[3,k], ",", CNerror[4,k], ",", CNerror[5,k], "\t", CNmult[1,k], "\t", CNpeak[k], sep="")
  cat(out, "\n")
  } else {
    out=paste(lab[k], "\t", CNmult[1,k], ",", CNmult[2,k], ",", CNmult[3,k], ",", CNmult[4,k], ",", CNmult[5,k], "\t", CNerror[1,k], ",", CNerror[2,k], ",", CNerror[3,k], ",", CNerror[4,k], ",", CNerror[5,k], "\t", CNmult[1,k], "\t", CNpeak[k], "\t", round(ploidy[which(lab[k]==ploidy[,1]),2], digits=2), sep="")
  cat(out, "\n")
  }


  ############################################################
  ################  Plot Key Analysis Steps ##################
  ############################################################

  jpeg(filename=paste(lab[k], "_analysis.jpeg", sep=""), width=2500, height=1500)

    par(mar = c(7.0, 7.0, 7.0, 3.0))
    layout(matrix(c(1,2,3,4), 2,2, byrow=TRUE))

    #Plot frequency distribution of pair-wise differences between read counts (contains peaks)
    hold=100*fd[[2]]/(sum(fd[[2]]))
    plot(1:round(length(hold)/2), hold[1:round(length(hold)/2)], main="Pair-Wise Differences Between Bin Counts", xlab="Pair-Wise Difference Between Bin Counts", ylab="Density (%)", type="n", cex.main=3, cex.axis=2, cex.lab=2)
    tu <- par('usr')
    par(xpd=FALSE)
    rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
    abline(v=unique(sort(c(axTicks(1), axTicks(1)+diff(axTicks(1))[1]/2))), col="white", lwd=2)
    abline(h=axTicks(2), col="white", lwd=2)
    lines(smooth.spline(1:round(length(hold)/2), hold[1:round(length(hold)/2)]), main="Pair-Wise Differences Between Bin Counts", xaxt='n', yaxt='n', xlab="", ylab="", lwd=2.5)
    lines(c(i,i), c(tu[3], hold[i]), lty=2.5, lwd=2, col="midnightblue")
    text(i, hold[i]+diff(tu)[3]/25, i, cex=2)

    #Plot sum of squares error for each potential copy number
    plot(CNgrid, outerColsums, xlim=c(1,6), type= "n", xaxt="n", main="Sum of Squares Error Across Potential Copy Number States", xlab="Copy Number Multiplier", ylab="Sum of Squares Error", cex.main=3, cex.axis=2, cex.lab=2)
    tu <- par('usr')
    par(xpd=FALSE)
    rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
    abline(v=seq(1, 6, .25), col="white", lwd=2)
    abline(h=axTicks(2), col="white", lwd=2)
    points(CNgrid, outerColsums, "b", xaxt="n", lwd=2.5)
    points(CNmult[1,k], CNerror[1,k], pch=23, cex=2, lwd=2.5, col="midnightblue")
    axis(side=1, at=1:6, cex.axis=2)
   
    #Plot normalized segmented read counts
    plot(normal[,k], main="Read Counts (Normalized to a mean of 1)", xlab="Bin", ylab="Normalized Read Counts", type="n", yaxt="n", ylim=c(.25, 5), cex.main=3, cex.axis=2, cex.lab=2)
    tu <- par('usr')
    par(xpd=FALSE)
    rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
    points(normal[,k], yaxt="n", ylim=c(.25, 5), pch=20, col=alpha('gray50', .5))
    abline(h=0:10, lwd=1.5, lty=2)
    points(fixed[,k], pch=20, col="midnightblue")
    if (length(which(fixed[,k] > 5)) >= 1) {
      points(which(fixed[,k] > 5), array(5,length(which(fixed[,k] > 5))), pch=23, col='midnightblue')
    }
    axis(side=2, at=seq(1, 5, .5) , cex.axis=2)


    #Plot scaled normalized segmented read counts
    plot(normal[,k]*CNmult[1,k], main=paste("Read Counts Scaled by SoS Multiplier (", CNmult[1,k], ")", sep=""), xlab="Bin", ylab="Copy Number", type="n", yaxt="n", ylim=c(.25, 10), cex.main=3, cex.axis=2, cex.lab=2)
    tu <- par('usr')
    par(xpd=FALSE)
    rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
    points(normal[,k]*CNmult[1,k], yaxt="n", ylim=c(.25, 10), pch=20, col=alpha('gray50', .5))
    abline(h=0:10, lwd=1.5, lty=2)
    points(fixed[,k]*CNmult[1,k], pch=20, col="midnightblue", )
    if (length(which(fixed[,k]*CNmult[1,k] > 10)) >= 1) {
      points(which(fixed[,k]*CNmult[1,k] > 10), array(10,length(which(fixed[,k]*CNmult[1,k] > 10))), pch=23, col='midnightblue')
    }
    axis(side=2, at=1:10 , cex.axis=2)

  dev.off()


  ############################################################
  ###############  Plot Copy Number Profile ##################
  ############################################################

  jpeg(filename=paste(lab[k], "_result.jpeg", sep=""), width=2500, height=1000)

    par(mar = c(7.0, 7.0, 7.0, 3.0))

    if (type != 2) {

     layout(matrix(c(1,1,1,1,1,2), 1,6, byrow=TRUE))

      #Plot raw read distribution
      if (f == 0) {
        plot(normal[,k]*CNmult[1,k], main=paste("Integer Copy Number Profile (Ploidy = ", CNmult[1,k], ")\n Using Sum of Squares Approach", sep=""), ylim=c(0, min(20, max(final[,k]))), type="n", xlab="Bin", ylab="Copy Number", cex.main=2, cex.axis=2, cex.lab=2)
      } else {
        plot(normal[,k]*ploidy[which(lab[k]==ploidy[,1]),2], main=paste("Integer Copy Number Profile (Ploidy = ", ploidy[which(lab[k]==ploidy[,1]),2], ")\n Using Provided Sample Ploidy", sep=""), type="n", ylim=c(0, min(20, max(final[,k]))), xlab="Bin", ylab="Copy Number", cex.main=3, cex.axis=2, cex.lab=2)
      }

      tu <- par('usr')
      par(xpd=FALSE)
      rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")

      if (f == 0) {
        points(normal[,k]*CNmult[1,k], ylim=c(0, min(20, max(final[,k]))), pch=20, col=alpha('gray50', .5))
      } else {
        points(ploidy[which(lab[k]==ploidy[,1]),2], ylim=c(0, min(20, max(final[,k]))), pch=20, col=alpha('gray50', .5))
      }

      abline(h=0:19, lty=2)

      #Plot overlaying copy number profile
      points(final[,k], pch=20, col="midnightblue")
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
        plot(normal[,k]*CNmult[1,k], main=paste("Integer Copy Number Profile (Ploidy = ", CNmult[1,k], ")\n Using Sum of Squares Approach", sep=""), ylim=c(0, min(20, max(final[,k]))), type="n", xlab="Bin", ylab="Copy Number", cex.main=3, cex.axis=2, cex.lab=2)
      } else {
        plot(normal[,k]*ploidy[which(lab[k]==ploidy[,1]),2], main=paste("Integer Copy Number Profile (Ploidy = ", ploidy[which(lab[k]==ploidy[,1]),2], ")\n Using Provided Sample Ploidy", sep=""), ylim=c(0, min(20, max(final[,k]))), type="n", xlab="Bin", ylab="Copy Number", cex.main=3, cex.axis=2, cex.lab=2)
      }

      tu <- par('usr')
      par(xpd=FALSE)
      rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")

      if (f == 0) {
        points(normal[,k]*CNmult[1,k], ylim=c(0, min(20, max(final[,k]))), pch=20, col=alpha('gray50', .5))
      } else {
        points(normal[,k]*ploidy[which(lab[k]==ploidy[,1]),2], ylim=c(0, min(20, max(final[,k]))), pch=20, col=alpha('gray50', .5))
      }

      abline(h=0:19, lty=2)

      #Plot overlaying copy number profile
      points(final[,k], pch=20, col="midnightblue")
      if (length(which(final[,k] > 20)) >= 1) {
        points(which(final[,k] > 20), array(20,length(which(final[,k] > 20))), pch=23, col='midnightblue')
    }

      #Plot chrom boundaries
      abline(v=c(0,t(bounds[2]), l), lwd=1.5)
    }

  dev.off()


  ############################################################
  ##############  Dynamically Generate Cluster  ##############
  ############################################################


  if ( ((k < 10) || (k%%(w%/%10) == 0)) && (k >= 3) )
  {
    #Calculate read distance matrix for clustering
    mat=matrix(0,nrow=k,ncol=k)
    for (i in 1:k){
       for (j in 1:k){
         mat[i,j]=dist(rbind(fixed[,i]/sum(fixed[,i]), fixed[,j]/sum(fixed[,j])), method = dm)
        }
      }
    #Create cluster of samples
    d <- dist(mat, method = dm)
    clust <- hclust(d, method = cm)
    clust$labels <- lab
    write(hc2Newick((clust)), file=paste(user_dir, "/clust.newick", sep=""))


	###
	main_dir="/mnt/data/ginkgo/scripts"
	command=paste("java -cp ", main_dir, "/forester_1025.jar org.forester.application.phyloxml_converter -f=nn ", user_dir, "/clust.newick ", user_dir, "/clust.xml", sep="");
	unlink( paste(user_dir, "/clust.xml", sep="") );
	system(command);
	###



  }

}


############################################################
##################  Save Processed Data  ###################
############################################################

#Close output stream
sink()

#Store processed sample information
write.table(normal, file=paste(user_dir, "/SegNorm", sep=""), row.names=FALSE, col.names=lab, sep="\t")
write.table(fixed, file=paste(user_dir, "/SegFixed", sep=""), row.names=FALSE, col.names=lab, sep="\t")
write.table(final, file=paste(user_dir, "/SegCopy", sep=""), row.names=FALSE, col.names=lab, sep="\t")
write.table(breaks, file=paste(user_dir, "/SegBreaks", sep=""), row.names=FALSE, col.names=lab, sep="\t")

statusFile <- file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Computing Cluster (Read Count)</processingfile>", sep=""), paste("<percentdone>", ((w+1)*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)


############################################################
################  Generate Final Clusters ##################
############################################################

#Calculate read distance matrix for clusteringcat
mat=matrix(0,nrow=w,ncol=w)
  for (i in 1:w){
    for (j in 1:w){
      mat[i,j]=dist(rbind(fixed[,i], fixed[,j]), method = dm)
    }
  }

#Create cluster of samples
d <- dist(mat, method = dm)
clust <- hclust(d, method = cm)
clust$labels <- lab
write(hc2Newick((clust)), file=paste(user_dir, "/clust.newick", sep=""))

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

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Computing Cluster (Copy Number)</processingfile>", sep=""), paste("<percentdone>", ((w+2)*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

#Calculate copy number distance matrix for clustering
mat2=matrix(0,nrow=w,ncol=w)
  for (i in 1:w){
    for (j in 1:w){
      mat2[i,j]=dist(rbind(final[,i], final[,j]), method = dm)
    }
  }

#Create cluster of samples
d2 <- dist(mat2, method = dm)
clust2 <- hclust(d2, method = cm)
clust2$labels <- lab
write(hc2Newick((clust2)), file=paste(user_dir, "/clust2.newick", sep=""))

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

jpeg("heatRaw.jpeg", width=2000, height=1400)
heatmap.2(t(rawBPs), Colv=FALSE, Rowv=as.dendrogram(clust), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=bluered(2))
dev.off()

step=quantile(fixedBPs, c(.98))[[1]]
jpeg("heatNorm.jpeg", width=2000, height=1400)
heatmap.2(t(fixedBPs), Colv=FALSE, Rowv=as.dendrogram(clust), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=bluered(15), breaks=seq(0,step,step/15))
dev.off()

step=min(20, quantile(finalBPs, c(.98))[[1]])
jpeg("heatCN.jpeg", width=2000, height=1400)
heatmap.2(t(finalBPs), Colv=FALSE, Rowv=as.dendrogram(clust2), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=heat.colors(15), breaks=seq(0,step,step/15))
dev.off()

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Finished</processingfile>", sep=""), paste("<percentdone>100</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

