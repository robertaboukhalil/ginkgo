#!/usr/bin/env Rscript

args<-commandArgs(TRUE)

genome <- args[[1]]
user_dir <- args[[2]]
status <- args[[3]]
dat <- args[[4]]
bm <- args[[5]]
stat <- as.numeric(args[[6]])
cm <- args[[7]]
dm <- args[[8]]

library('ctc')
library(DNAcopy) #segmentation
library(biclust) #biclustering
library(inline) #use of c++
library(gplots) #visual plotting of tables
library(plyr)

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>2</step>", "<processingfile>Initializing Variables</processingfile>", "<percentdone>0</percentdone>", "<tree>hist.newick</tree>", "</status>"), statusFile)
close(statusFile)

setwd(genome)
v=read.table(paste("GC_", bm, sep=""), header=FALSE, sep="\t")
b=read.table(paste("bounds_", bm, sep=""), header=FALSE, sep="\t")

setwd(user_dir)
T=read.table(dat, header=TRUE, sep="\t")

l=dim(T)[1] #Number of bins
w=dim(T)[2]-2 #Number of samples
lab=colnames(T[3:dim(T)[2]]) #Sample labels

#Initialize matrices/arrays
breaks=matrix(0,l,w)
fixed=matrix(0,l,w) #Post-segmented, median read counts/bin for each sample
aneuploid=matrix(0,l,w)
diploid=matrix(0,l,w)

#Find sample with greatest read count variance across bins
a=array(0,w)
for(i in 1:w){
  a[i]=var(T[,(i+2)])
  if (stat == 0){
     statusFile<-file( paste(user_dir, "/", status, sep="") )
    writeLines(c("<?xml version='1.0'?>", "<status>", "<step>2</step>", paste("<processingfile>", lab[i], "</processingfile>", sep=""), paste("<percentdone>", (i/w)*100, "</percentdone>", sep=""), "<tree>0</tree>", "</status>"), statusFile)
    close(statusFile)
  }
}
comp=which.max(a)

#Find sample whose reads most closely parallel GC content
if (stat == 1){
  p=array(0,w)
  for(i in 1:w){
     statusFile<-file( paste(user_dir, "/", status, sep="") )
    writeLines(c("<?xml version='1.0'?>", "<status>", "<step>2</step>", paste("<processingfile>", lab[i], "</processingfile>", sep=""), paste("<percentdone>", (i*100)%/%w, "</percentdone>", sep=""), "<tree>0</tree>", "</status>"), statusFile)
    close(statusFile)
    p[i]=chisq.test(T[,(i+2)], v[,1])[[3]]
  }

  if (max(p) != 0 & min(p) != 1){
    comp = which(p==min(p[p > 0]))
  }
}

#Set reference sample for segmentation algorithm
F=T[,(comp+2)]

statusFile<-file( paste(user_dir, "/", status, sep="") )
 writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>", lab[i], "</processingfile>", sep=""), paste("<percentdone>", 0, "</percentdone>", sep=""), "<tree>0</tree>", "</status>"), statusFile)
close(statusFile)

#PROCESS ALL SAMPLES
for(k in 2:w){

  statusFile<-file( paste(user_dir, "/", status, sep="") )
  writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>", lab[k], "</processingfile>", sep=""), paste("<percentdone>", (k*100)%/%w - 1, "</percentdone>", sep=""), "<tree>hist.xml</tree>", "</status>"), statusFile)
  close(statusFile)

  print(paste("Starting", k))

  #Compute log ratio between kth sample and reference
  lr = -log2((T[,(k+2)]+1)/(F+1))

  #Determine breakpoints using lr and extract chrom/locations
  CNA.object <-CNA(genomdat = lr, chrom = T[,1], maploc = T[,2], data.type = 'logratio')
  CNA.smoothed <- smooth.CNA(CNA.object)
  segs <- segment(CNA.smoothed, verbose=0, min.width=2)
  frag = segs$output[,2:3]

  #Map breakpoints to kth sample
  len=dim(frag)[1]
  bps=array(0, len)
  for (j in 1:len){
    bps[j]=which((T[,1]==frag[j,1]) & (T[,2]==frag[j,2]))
  }
  bps=sort(bps)

  #Keep track of breakpoint locations
  breaks[bps,k]=1

  #Modify bins to contain median read count/bin within each segment
  fixed[,k][1:bps[2]] = mean(T[,(k+2)][1:bps[2]])
  for(i in 2:(len-1)){
    fixed[,k][bps[i]:bps[i+1]] = mean(T[,(k+2)][bps[i]:bps[i+1]])
  }

  #Determine frequency distribution (h) of all pair-wise differences between bins
  t <- as.integer(fixed[,k])
  h <- as.integer(rep(0,max(t)-min(t)+1))
  sig <- signature(l="integer", t="integer", h="integer")
  code <- "
    for (int i=0; i<(*l)-1; i++) {
      for (int j=(i+1); j<(*l); j++) {
        int d = abs(t[j] - t[i]);
        h[d]++;
      }
    }
  "
  f <- cfunction(sig, code, convention=".C")
  h <- f(l,t,h)$h
  
  #Fit spline (fd) to smooth frequency distribution 
  fd=smooth.spline(1:length(h), h, spar=.35)
  write.table(100*fd[[2]]/(sum(fd[[2]])), file=paste(user_dir, "/", lab[k], "_fit", sep=""), row.names=FALSE, col.names=lab[k], sep="\t")
 
  #Find the second mode of the frequency distribution
  #A copy number of 1 corresponds to the number of reads associated with this mode
  for(i in 5:(length(h)-2)){
    if ( (fd[[2]][i-2] < fd[[2]][i-1]) && (fd[[2]][i-1] < fd[[2]][i]) && (fd[[2]][i] > fd[[2]][i+1]) && (fd[[2]][i+1] > fd[[2]][i+2]) ) {
      break
    }
  }

  aneuploid[,k] = round(fixed[,k]/i)
  diploid[,k] = round(2*fixed[,k]/median(fixed[,k]))

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

    layout(matrix(c(1, 2, 2, 2, 3, 3, 4, 4, 5, 5, 6, 6), 3, 4, byrow=TRUE))
    
    #plot stats table
    textplot(stats, halign="center", valign="center", show.rownames=FALSE, show.colnames=FALSE)
    hist(T[,(k+2)], breaks=100*max(T[,(k+2)])/r, xlim=range(1:r), ylim=range(1:round_any(max(e$counts), 1000, ceiling)), main="Histogram of Read Count Frequency", xlab="Read Count (reads/bin)")
    
    #Plot normalized segmented read counts
    plot(fixed[,k], main="Reads/Bin (After Segmentation)", xlab="Bin", ylab="Read Count")
    abline(v=t(b[2]), col='blue')

    #Plot frequency distribution of pair-wise differences between read counts (contains peaks)
    plot(100*fd[[2]]/(sum(fd[[2]])), main="Density Plot: Frequency Distribution of All Pair-Wise Differences Between Bin Counts", xlab="Pair-wise Difference (# of reads)", ylab="% Sampled Density")

    #Plot diploid copy number profile
    plot(diploid[,k], main="Copy Number Profile (Assuming Diploid)", xlab="Bin", ylab="Copy Number")
    abline(v=t(b[2]), col='blue')
    
    #Plot aneuploid copy number profile
    plot(aneuploid[,k], main="Copy Number Profile (Assuming Aneuploid)", xlab="Bin", ylab="Copy Number")
    abline(v=t(b[2]), col='blue')

  dev.off()

}

#Store processed sample information
write.table(fixed, file=paste(user_dir, "/SegRaw", sep=""), row.names=FALSE, col.names=lab, sep="\t")
write.table(fixed/sum(T[,3:(w+2)]), file=paste(user_dir, "/SegNorm", sep=""), row.names=FALSE, col.names=lab, sep="\t")
write.table(breaks, file=paste(user_dir, "/SegBreaks", sep=""), row.names=FALSE, col.names=lab, sep="\t")
write.table(aneuploid, file=paste(user_dir, "/SegAneuploid", sep=""), row.names=FALSE, col.names=lab, sep="\t")
write.table(diploid, file=paste(user_dir, "/SegDiploid", sep=""), row.names=FALSE, col.names=lab, sep="\t")

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Creating Dendogram</processingfile>", sep=""), paste("<percentdone>100</percentdone>", sep=""), "<tree>hist.xml</tree>", "</status>"), statusFile)
close(statusFile)

#Calculate distance matrix for clustering
mat=matrix(0,nrow=w,ncol=w)
  for (i in 1:w){
    for (j in 1:w){
      mat[i,j]=dist(rbind(fixed[,i]/sum(T[,(i+2)]), fixed[,j]/sum(T[,(j+2)])), method = dm)
    }
  }

#Create cluster of samples
d = dist(mat, method = dm)
T_clust = hclust(d, method = cm)
T_clust$labels = lab
write(hc2Newick(T_clust), file=paste(user_dir, "/hist.newick", sep=""))

#Plot cluster
jpeg("clust.jpeg", width=2000, height=1400)
plot(T_clust)
dev.off()

statusFile<-file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Finished</processingfile>", sep=""), paste("<percentdone>100</percentdone>", sep=""), "<tree>hist.xml</tree>", "</status>"), statusFile)
close(statusFile)

