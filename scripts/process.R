#!/usr/bin/env Rscript

# ==============================================================================
# == Main CNV analysis script
# ==============================================================================

# ------------------------------------------------------------------------------
# -- Variables
# ------------------------------------------------------------------------------

# Config
maxPloidy   = 6
minBinWidth = 5
main_dir="/local1/work/ginkgo/scripts"

# User settings
args        = commandArgs(TRUE)
genome      = args[[1]]
user_dir    = args[[2]]
status      = args[[3]]
dat         = args[[4]]
stat        = as.numeric(args[[5]])
bm          = args[[6]]
cm          = args[[7]]
dm          = args[[8]]
cp          = as.numeric(args[[9]])
ref         = args[[10]]
f           = as.numeric(args[[11]])
facs        = args[[12]]
sex         = as.numeric(args[[13]])
bb          = as.numeric(args[[14]])

# Libraries
library('ctc')
library(DNAcopy) # Segmentation
library(inline)  # Use of c++
library(gplots)  # Visual plotting of tables
library(scales)
library(plyr)
library(ggplot2)
library(gridExtra)


# ------------------------------------------------------------------------------
# -- Initialize Variables & Pre-Process Data
# ------------------------------------------------------------------------------

statusFile = file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", "<processingfile>Initializing Variables</processingfile>", "<percentdone>0</percentdone>", "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

# Load genome specific files
setwd(genome)
GC     = read.table(paste("GC_", bm, sep="")    , header=FALSE, sep="\t", as.is=TRUE)
loc    = read.table(bm                          , header=TRUE , sep="\t", as.is=TRUE)
bounds = read.table(paste("bounds_", bm, sep=""), header=FALSE, sep="\t")

# Load user data
setwd(user_dir)
raw    = read.table(dat, header=TRUE, sep="\t")
ploidy = rbind(c(0,0), c(0,0))
if (f == 1 | f == 2)
  ploidy = read.table(facs, header=FALSE, sep="\t", as.is=TRUE)  

# Remove bad bins
if (bb)
{
  print("Removing bad bins...")
  badbins = read.table(paste(genome, "/badbins_", bm, sep=""), header=FALSE, sep="\t", as.is=TRUE)
  GC      = data.frame(GC[-badbins[,1], 1])
  loc     = loc[-badbins[,1], ]
  raw     = data.frame(raw[-badbins[,1], ])

  step  = 1
  chrom = loc[1,1]
  for (i in 1:nrow(loc))
  {
   if (loc[i,1] != chrom)
   {
     bounds[step,1] = chrom
     bounds[step,2] = i
     step           = step+1
     chrom          = loc[i,1]
    }
  }
}

# Initialize color palette
colors     = matrix(0,3,2)
colors[1,] = c('goldenrod', 'darkmagenta')
colors[2,] = c('dodgerblue', 'darkorange')
colors[3,] = c('brown2', 'blue4')

# Initialize data structures
l            = dim(raw)[1] # Number of bins
w            = dim(raw)[2] # Number of cells
n_ploidy     = length(seq(1.5, maxPloidy, by=0.05)) # Number of ploidy tests during CN inference
breaks       = matrix(0,l,w)
fixed        = matrix(0,l,w)
final        = matrix(0,l,w)
stats        = matrix(0,w,10)
CNmult       = matrix(0,n_ploidy,w)
CNerror      = matrix(0,n_ploidy,w)
outerColsums = matrix(0, (20*(maxPloidy-1.5)+1), w)
pos          = cbind(c(1,bounds[,2]), c(bounds[,2], l))

# Normalize cells
normal  = sweep(raw+1, 2, colMeans(raw+1), '/')
normal2 = normal
lab     = colnames(normal)

# Prepare statistics
rownames(stats) = lab
colnames(stats) = c("Reads", "Bins", "Mean", "Var", "Disp", "Min", "25th", "Median", "75th", "Max")

# Determine segmentation reference using dispersion (stat = 1) or reference sample (stat = 2)
if (stat == 1)
{
  F = normal[,which.min(apply(normal, 2, sd)/apply(normal,2,mean))[1]]
} else if (stat == 2) {
  R   = read.table(ref, header=TRUE, sep="\t", as.is=TRUE)
  low = lowess(GC[,1], log(R[,1]+0.001), f=0.05)
  app = approx(low$x, low$y, GC[,1])
  F   = exp(log(R[,1]) - app$y)
}


# ------------------------------------------------------------------------------
# -- Process all cells
# ------------------------------------------------------------------------------

# Open output stream
sink("results.txt")
cat(paste("Sample\tCopy_Number\tSoS_Predicted_Ploidy\tError_in_SoS_Approach\n", sep=""))

#
for(k in 1:w)
{
  cat('===',k,'===\n')

  statusFile = file( paste(user_dir, "/", status, sep="") )
  writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>", lab[k], "</processingfile>", sep=""), paste("<percentdone>", (k*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
  close(statusFile)

  # Generate basic statistics
  stats[k,1]  = sum(raw[,k])
  stats[k,2]  = l
  stats[k,3]  = round(mean(raw[,k]), digits=2)
  stats[k,4]  = round(sd(raw[,k]), digits=2)
  stats[k,5]  = round(stats[k,4]/stats[k,3], digits=2)
  stats[k,6]  = min(raw[,k])
  stats[k,7]  = quantile(raw[,k], c(.25))[[1]]
  stats[k,8]  = median(raw[,k])
  stats[k,9]  = quantile(raw[,k], c(.75))[[1]]
  stats[k,10] = max(raw[,k])

  # ----------------------------------------------------------------------------
  # -- Segment data
  # ----------------------------------------------------------------------------

  # Calculate normalized for current cell (previous values of normal seem wrong)
  lowess.gc = function(jtkx, jtky) {
    jtklow = lowess(jtkx, log(jtky), f=0.05); 
    jtkz = approx(jtklow$x, jtklow$y, jtkx)
    return(exp(log(jtky) - jtkz$y))
  }
  normal[,k] = lowess.gc( GC[,1], (raw[,k]+1)/mean(raw[,k]+1) )

  # Compute log ratio between kth sample and reference
  if (stat == 0)
    lr = log2(normal[,k])
  else
    lr = log2((normal[,k])/(F))

  # Determine breakpoints and extract chrom/locations
  CNA.object   = CNA(genomdat = lr, chrom = loc[,1], maploc = as.numeric(loc[,2]), data.type = 'logratio')
  CNA.smoothed = smooth.CNA(CNA.object)
  segs         = segment(CNA.smoothed, verbose=0, min.width=minBinWidth)
  frag         = segs$output[,2:3]

  # Map breakpoints to kth sample
  len = dim(frag)[1]
  bps = array(0, len)
  for (j in 1:len)
    bps[j]=which((loc[,1]==frag[j,1]) & (as.numeric(loc[,2])==frag[j,2]))
  bps = sort(bps)
  bps[(len=len+1)] = l

  # Track global breakpoint locations
  breaks[bps,k] = 1

  # Modify bins to contain median read count/bin within each segment
  fixed[,k][1:bps[2]] = median(normal[,k][1:bps[2]])
  for(i in 2:(len-1))
    fixed[,k][bps[i]:(bps[i+1]-1)] = median(normal[,k][bps[i]:(bps[i+1]-1)])
  fixed[,k] = fixed[,k]/mean(fixed[,k])

  # ----------------------------------------------------------------------------
  # -- Determine Copy Number (SoS Method)
  # ----------------------------------------------------------------------------

  # Determine Copy Number     
  CNgrid           = seq(1.5, maxPloidy, by=0.05)
  outerRaw         = fixed[,k] %o% CNgrid
  outerRound       = round(outerRaw)
  outerDiff        = (outerRaw - outerRound) ^ 2
  outerColsums[,k] = colSums(outerDiff, na.rm = FALSE, dims = 1)
  CNmult[,k]       = CNgrid[order(outerColsums[,k])]
  CNerror[,k]      = round(sort(outerColsums[,k]), digits=2)

  if (f == 0 | length(which(lab[k]==ploidy[,1]))==0 ) {
    CN = CNmult[1,k]
  } else if (f == 1) {
    CN = ploidy[which(lab[k]==ploidy[,1]),2]
  } else {
    estimate = ploidy[which(lab[k]==ploidy[,1]),2]
    CN = CNmult[which(abs(CNmult[,k] - estimate)<.4),k][1]
  }
  final[,k] = round(fixed[,k]*CN)

  # Output results of CN calculations to file
  out=paste(lab[k], CN, paste(CNmult[,k], collapse= ","), paste(CNerror[,k], collapse= ","), sep="\t")
  cat(out, "\n")

  # ----------------------------------------------------------------------------
  # -- Generate Plots & Figures
  # ----------------------------------------------------------------------------

  # Plot Distribution of Read Coverage
  jpeg(filename=paste(lab[k], "_dist.jpeg", sep=""), width=3000, height=750)
  
  top=round(quantile(raw[,k], c(.995))[[1]])
  rectangles1=data.frame(pos[seq(1,nrow(pos), 2),])
  rectangles2=data.frame(pos[seq(2,nrow(pos), 2),])
  main=data.frame(x=which(raw[,k]<top), y=raw[which(raw[,k]<top),k])
  outliers=data.frame(x=which(raw[,k]>top), y=array(top*.99, length(which(raw[,k]>top))))
  anno=data.frame(x=(pos[,2]+pos[,1])/2, y=-top*.05, chrom=substring(c(as.character(bounds[,1]), "chrY"), 4 ,5))

  plot1 = ggplot() +
    geom_rect(data=rectangles1, aes(xmin=X1, xmax=X2, ymin=-top*.1, ymax=top), fill='gray85', alpha=0.75) +
    geom_rect(data=rectangles2, aes(xmin=X1, xmax=X2, ymin=-top*.1, ymax=top), fill='gray75', alpha=0.75) + 
    geom_point(data=main, aes(x=x, y=y), size=3) +
    geom_point(data=outliers, aes(x=x, y=y), shape=5, size=4) +
    geom_text(data=anno, aes(x=x, y=y, label=chrom), size=12) +
    labs(title=paste("Genome Wide Read Distribution for Sample \"", lab[k], "\"", sep=""), x="Chromosome", y="Read Count", size=16) +
    theme(plot.title=element_text(size=36, vjust=1.5)) +
    theme(axis.title.x=element_text(size=40, vjust=-.1), axis.title.y=element_text(size=40, vjust=-.06)) +
    theme(axis.text=element_text(color="black", size=40), axis.ticks=element_line(color="black"))+
    theme(axis.ticks.x = element_blank(), axis.text.x = element_blank(), axis.line.x = element_blank()) +
    theme(panel.background = element_rect(fill = 'gray90')) +
    theme(plot.margin=unit(c(.5,1,.5,1.5),"cm")) +
    theme(panel.grid.major.x = element_blank()) +
    scale_x_continuous(limits=c(0, l), expand = c(0, 0)) +
    scale_y_continuous(limits=c(-top*.1, top), expand = c(0, 0)) +
    geom_vline(xintercept = c(1, l), size=.25) +
    geom_hline(yintercept = c(-top*.1, top), size=.25)

    grid.arrange(plot1, ncol=1)
  dev.off()

  #Plot histogram of bin counts
  jpeg(filename=paste(lab[k], "_counts.jpeg", sep=""), width=2500, height=1500)
    par(mar = c(7.0, 7.0, 7.0, 3.0))

    temp=sort(raw[,k])[round(l*.01) : (l-round(l*.01))] 
    reads = hist(temp, breaks=100, plot=FALSE)
    # plot(reads, col='black', main=paste("Frequency of Bin Counts for Sample ", lab[k], "\n(both tails trimmed 1%)", sep=""), xlab="Read Count (reads/bin)", xaxt="n", cex.main=3, cex.axis=2, cex.lab=2)
    plot(reads, col='black', main=paste("Frequency of Bin Counts for Sample ", lab[k], "\n(both tails trimmed 1%)", sep=""), xlab="Read Count (reads/bin)", cex.main=3, cex.axis=2, cex.lab=2)
    # axis(side=1, at=seq(min(temp), round(diff(range(temp))/20)*22, round(diff(range(temp))/20)), cex.axis=2)
    tu = par('usr')
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

  nReads=sum(raw[,k])
  uniq=unique(sort(raw[,k]))
  
  lorenz=matrix(0, nrow=length(uniq), ncol=2)
  a=c(length(which(raw[,k]==0)), tabulate(raw[,k], nbins=max(raw[,k])))
  b=a*(0:(length(a)-1))
  for (i in 2:length(uniq)) {
    lorenz[i,1]=sum(a[1:uniq[i]])/l
    lorenz[i,2]=sum(b[2:uniq[i]])/nReads
  }

  # smooth.spline needs >= 4 points...
  fit = data.frame(x=lorenz[,1], y=lorenz[,2])
  if(nrow(lorenz) >= 4)
  {
    spline = try(smooth.spline(lorenz))
    if(class(spline) != "try-error")
      fit = data.frame(x=spline$x, y=spline$y)
  }

  perf=data.frame(x=c(0,1), y=c(0,1))

  plot1 = try(ggplot() +
    geom_line(data=perf, aes(x=x, y=y, color="Perfect Uniformity"), size=3) +
    geom_line(data=fit, aes(x=x, y=y, color="Sample Uniformity"), size=3) +
    scale_x_continuous(limits=c(0,1), breaks=seq(0, 1, .1)) +
    scale_y_continuous(limits=c(0,1), breaks=seq(0, 1, .1)) +
    labs(title=paste("Lorenz Curve of Coverage Uniformity for Sample ", lab[k], sep=""), x="Cumulative Fraction of Genome", y="Cumulative Fraction of Total Reads") +
    theme(plot.title=element_text(size=45, vjust=1.5)) +
    theme(axis.title.x=element_text(size=45, vjust=-2.8), axis.title.y=element_text(size=45, vjust=.1)) +
    theme(axis.text=element_text(color="black", size=45), axis.ticks=element_line(color="black")) +
    theme(plot.margin=unit(c(.5,1,1,1.5),"cm")) +
    theme(panel.background = element_rect(color = 'black')) +
    theme(legend.title=element_blank(), legend.text=element_text(size=40)) +
    theme(legend.key.height=unit(4,"line"), legend.key.width=unit(4,"line")) +
    theme(legend.position=c(.15, .85)) +
    scale_color_manual(name='', values=c('Perfect Uniformity'="black", 'Sample Uniformity'=colors[cp,1])))

    grid.arrange(plot1, ncol=1)
  dev.off()

  #Plot GC correction
  jpeg(filename=paste(lab[k], "_GC.jpeg", sep=""), width=2500, height=1250)

  low = lowess(GC[,1], log(normal2[,k]), f=0.05)
  app = approx(low$x, low$y, GC[,1])
  cor = exp(log(normal2[,k]) - app$y)
  
  uncorrected = data.frame(x=GC[,1], y=log(normal2[,k]))
  corrected = data.frame(x=GC[,1], y=log(cor))
  fit = data.frame(x=app$x, y=app$y)

  try(plot1 <- ggplot() +
    geom_point(data=uncorrected, aes(x=x, y=y), size=3) +
    geom_line(data=fit, aes(x=x, y=y, color="Lowess Fit"), size=3) +
    scale_x_continuous(limits=c(min(.3, min(GC[,1])), max(.6, max(GC[,1]))), breaks=seq(.3,.6,.05)) +
    labs(title=paste("GC Content vs. Bin Count\nSample ", lab[k], " (Uncorrected)", sep=""), x="GC content", y="Normalized Read Counts (log scale)") +
    theme(plot.title=element_text(size=45, vjust=1.5)) +
    theme(axis.title.x=element_text(size=45, vjust=-2.8), axis.title.y=element_text(size=45, vjust=.1)) +
    theme(axis.text=element_text(color="black", size=45), axis.ticks=element_line(color="black")) +
    theme(plot.margin=unit(c(.5,1,1,1.5),"cm")) +
    theme(panel.background = element_rect(color = 'black')) +
    theme(legend.title=element_blank(), legend.text=element_text(size=45)) +
    theme(legend.key.height=unit(4,"line"), legend.key.width=unit(4,"line")) +
    theme(legend.position=c(.85, .9)) +
    scale_color_manual(name='', values=colors[cp,1]))

  try(plot2 <- ggplot() +
    geom_point(data=corrected, aes(x=x, y=y), size=3) +
    scale_x_continuous(limits=c(min(.3, min(GC[,1])), max(.6, max(GC[,1]))), breaks=seq(.3,.6,.05)) +
    labs(title=paste("GC Content vs. Bin Count\nSample ", lab[k], " (Corrected)", sep=""), x="GC content", y="") +
    theme(plot.title=element_text(size=45, vjust=1.5)) +
    theme(axis.title.x=element_text(size=45, vjust=-2.8), axis.title.y=element_text(size=45, vjust=.1)) +
    theme(axis.text=element_text(color="black", size=45), axis.ticks=element_line(color="black")) +
    theme(plot.margin=unit(c(.5,1,1,1.5),"cm")) +
    theme(panel.background = element_rect(color = 'black')))

    try(grid.arrange(plot1, plot2, ncol=2))
  dev.off()

  #Plot Scaled/Normalized Bin Count Histogram
  jpeg(filename=paste(lab[k], "_hist.jpeg", sep=""), width=2500, height=1500)

  clouds=data.frame(x=normal[,k]*CN)

  plot1 = ggplot() +
    geom_histogram(data=clouds, aes(x=x), binwidth=.05, color="black", fill="gray60") +
    geom_vline(xintercept=seq(0,10,1), size=1, linetype="dashed", color=colors[cp,1]) +
    scale_x_continuous(limits=c(0,10), breaks=seq(0,10,1)) +
    labs(title=paste("Frequency of Bin Counts for Sample \"", lab[k], "\"\nNormalized and Scaled by Predicted CN (", CNmult[1,k], ")", sep=""), x="Copy Number", y="Frequency") +
    theme(plot.title=element_text(size=45, vjust=1.5)) +
    theme(axis.title.x=element_text(size=45, vjust=-2.8), axis.title.y=element_text(size=45, vjust=.1)) +
    theme(axis.text=element_text(color="black", size=45), axis.ticks=element_line(color="black")) +
    theme(plot.margin=unit(c(.5,1,1,1.5),"cm")) +
    theme(panel.background = element_rect(color = 'black'))

    grid.arrange(plot1, ncol=1)
  dev.off()

  #Plot sum of squares error for each potential copy number
  jpeg(filename=paste(lab[k], "_SoS.jpeg", sep=""), width=2500, height=1500)

  top = max(outerColsums[,k])
  dat = data.frame(x=CNgrid, y=outerColsums[,k])
  lim = cbind(c(seq(0,5000,500), 1000000), c(50, 100, 100, 200, 250, 400, 500, 500, 600, 600, 750, 1000))
  step = lim[which(top<lim[,1])[1],]
  minSoS = data.frame(x=CNmult[1,k], y=CNerror[1,k])
  bestSoS = data.frame(x=CN, y=outerColsums[which(CNgrid==CN),k])

  plot1 = ggplot() +
    geom_line(data=dat, aes(x=x, y=y), size=3) +
    geom_point(data=dat, aes(x=x, y=y), shape=21, fill="black", size=5) +
    geom_point(data=minSoS, aes(x=x, y=y*1.02, color="Minimum SoS Error"), shape=18, size=15) +
    geom_point(data=bestSoS, aes(x=x, y=y*.98, color="Chosen Ploidy"), shape=18, size=15) +
    scale_x_continuous(limits=c(1.5, 6), breaks=seq(1.5, 6, .5)) +
    scale_y_continuous(limits=c(.5*min(outerColsums[,k]), top), breaks=seq(0, step[1], step[2])) +
    labs(title="Sum of Squares Error Across Potential Copy Number States", x="Copy Number Multiplier", y="Sum of Squares Error") +
    theme(plot.title=element_text(size=45, vjust=1.5)) +
    theme(axis.title.x=element_text(size=45, vjust=-2.8), axis.title.y=element_text(size=45, vjust=.1)) +
    theme(axis.text=element_text(color="black", size=45), axis.ticks=element_line(color="black")) +
    theme(plot.margin=unit(c(.5,1,1,1.5),"cm")) +
    theme(panel.background = element_rect(color = 'black')) +
    theme(legend.title=element_blank(), legend.text=element_text(size=45)) +
    theme(legend.key.height=unit(4,"line"), legend.key.width=unit(4,"line")) +
    theme(legend.position=c(.85, .9)) +
    scale_color_manual(name='', values=c('Minimum SoS Error'=colors[cp,1], 'Chosen Ploidy'=colors[cp,2]))

    grid.arrange(plot1, ncol=1)
  dev.off()

  #Plot colored CN profile
  jpeg(filename=paste(lab[k], "_CN.jpeg", sep=""), width=3000, height=750)

  top=8
  rectangles1=data.frame(pos[seq(1,nrow(pos), 2),])
  rectangles2=data.frame(pos[seq(2,nrow(pos), 2),])
  clouds=data.frame(x=1:l, y=normal[,k]*CN)
  amp=data.frame(x=which(final[,k]>2), y=final[which(final[,k]>2),k])
  del=data.frame(x=which(final[,k]<2), y=final[which(final[,k]<2),k])
  flat=data.frame(x=which(final[,k]==2), y=final[which(final[,k]==2),k])
  anno=data.frame(x=(pos[,2]+pos[,1])/2, y=-top*.05, chrom=substring(c(as.character(bounds[,1]), "chrY"), 4 ,5))

  plot1 = ggplot() +
    geom_rect(data=rectangles1, aes(xmin=X1, xmax=X2, ymin=-top*.1, ymax=top), fill='gray85', alpha=0.75) +
    geom_rect(data=rectangles2, aes(xmin=X1, xmax=X2, ymin=-top*.1, ymax=top), fill='gray75', alpha=0.75) +
    geom_point(data=clouds, aes(x=x, y=y), color='gray45', size=3) +
    geom_point(data=flat, aes(x=x, y=y), size=4) +
    geom_point(data=amp, aes(x=x, y=y), size=4, color=colors[cp,1]) +
    geom_point(data=del, aes(x=x, y=y), size=4, color=colors[cp,2]) +
    geom_text(data=anno, aes(x=x, y=y, label=chrom), size=12) +
    scale_x_continuous(limits=c(0, l), expand = c(0, 0)) +
    scale_y_continuous(limits=c(-top*.1, top), expand = c(0, 0)) +
    labs(title=paste("Integer Copy Number Profile for Sample \"", lab[k], "\"\n Predicted Ploidy = ", CN, sep=""), x="Chromosome", y="Copy Number", size=16) +
    theme(plot.title=element_text(size=40, vjust=1.5)) +
    theme(axis.title.x=element_text(size=40, vjust=-.05), axis.title.y=element_text(size=40, vjust=.1)) +
    theme(axis.text=element_text(color="black", size=40), axis.ticks=element_line(color="black"))+
    theme(axis.ticks.x = element_blank(), axis.text.x = element_blank(), axis.line.x = element_blank()) +
    theme(panel.background = element_rect(fill = 'gray90')) +
    theme(plot.margin=unit(c(.5,1,.5,1),"cm")) +
    theme(panel.grid.major.x = element_blank()) +
    geom_vline(xintercept = c(1, l), size=.5) +
    geom_hline(yintercept = c(-top*.1, top), size=.5)

    grid.arrange(plot1, ncol=1)
  dev.off()

}

# ------------------------------------------------------------------------------
# -- Save processed data
# ------------------------------------------------------------------------------

# Update status
statusFile=file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Saving Data</processingfile>", sep=""), paste("<percentdone>", (w*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

# Close output stream
sink()

# Store processed sample information
loc2=loc
loc2[,3]=loc2[,2]
pos = cbind(c(1,bounds[,2]), c(bounds[,2], l))

#
for (i in 1:nrow(pos))
{
  # If only 1 bin in a chromosome
  if( (pos[i,2] - pos[i,1]) == 0 ) {
    loc2[pos[i,1],1] = 1
  # If two bins.......
  } else if( (pos[i,2] - pos[i,1]) == 1 ) {
    loc2[pos[i,1],1] = 1
    loc2[pos[i,2],1] = loc2[pos[i,1],2] + 1
  } else {
    loc2[pos[i,1]:(pos[i,2]-1),2]=c(1,loc[pos[i,1]:(pos[i,2]-2),2]+1)
  }
}

# 
loc2[nrow(loc2),2]=loc2[nrow(loc2)-1,3]+1
colnames(loc2)=c("CHR","START", "END")

write.table(cbind(loc2,normal), file=paste(user_dir, "/SegNorm", sep=""), row.names=FALSE, col.names=c(colnames(loc2),lab), sep="\t", quote=FALSE)
write.table(cbind(loc2,fixed), file=paste(user_dir, "/SegFixed", sep=""), row.names=FALSE, col.names=c(colnames(loc2),lab), sep="\t", quote=FALSE)
write.table(cbind(loc2,final), file=paste(user_dir, "/SegCopy", sep=""), row.names=FALSE, col.names=c(colnames(loc2),lab), sep="\t", quote=FALSE)
write.table(cbind(loc2,breaks), file=paste(user_dir, "/SegBreaks", sep=""), row.names=FALSE, col.names=c(colnames(loc2),lab), sep="\t", quote=FALSE)
write.table(stats, file=paste(user_dir, "/SegStats", sep=""), sep="\t", quote=FALSE)

# ------------------------------------------------------------------------------
# -- Generate phylo trees
# ------------------------------------------------------------------------------

statusFile = file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Computing Cluster (Read Count)</processingfile>", sep=""), paste("<percentdone>", ((w+1)*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

# Ignore sex chromosomes if specified
if (sex == 0) {
  l=bounds[(dim(bounds)-1)[1],][[2]]-1
  raw=raw[1:l,]
  final=final[1:l,]
  fixed=fixed[1:l,]
  breaks=breaks[1:l,]
  normal=normal[1:l,]
}

# Calculate read distance matrix for clustering
d = dist(t(fixed), method=dm)
if(cm == "NJ")
{
  library(ape)
  clust = nj(d)
  clust$tip.label = lab
  write.tree(clust, file=paste(user_dir, "/clust.newick", sep=""))
} else {
  clust = hclust(d, method = cm)
  clust$labels = lab  
  write(hc2Newick(clust), file=paste(user_dir, "/clust.newick", sep=""))
}

###
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

statusFile=file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Computing Cluster (Copy Number)</processingfile>", sep=""), paste("<percentdone>", ((w+2)*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

#Calculate copy number distance matrix for clustering
d2 = dist(t(final), method = dm)
#clust2 = hclust(d2, method = cm)
#clust2$labels = lab
#write(hc2Newick(clust2), file=paste(user_dir, "/clust2.newick", sep=""))
if(cm == "NJ"){
  library(ape)
  clust2 = nj(d2)
  clust2$tip.label = lab
  write.tree(clust2, file=paste(user_dir, "/clust2.newick", sep=""))
}else{
  clust2 = hclust(d2, method = cm)
  clust2$labels = lab  
  write(hc2Newick(clust2), file=paste(user_dir, "/clust2.newick", sep=""))
}


###
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
d3 = as.dist((1 - cor(final))/2)
#clust3=hclust(d3, method = cm)
#clust3$labels=lab
#write(hc2Newick(clust3), file=paste(user_dir, "/clust3.newick", sep=""))
if(cm == "NJ"){
  library(ape)
  clust3 = nj(d3)
  clust3$tip.label = lab
  write.tree(clust3, file=paste(user_dir, "/clust3.newick", sep=""))
}else{
  clust3 = hclust(d3, method = cm)
  clust3$labels = lab  
  write(hc2Newick(clust3), file=paste(user_dir, "/clust3.newick", sep=""))
}

###
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

# ------------------------------------------------------------------------------
# -- Generate heatmaps
# ------------------------------------------------------------------------------

statusFile=file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Creating Heat Maps</processingfile>", sep=""), paste("<percentdone>", ((w+3)*100)%/%(w+4), "</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

#Create breakpoint heatmaps
rawBPs=breaks
fixedBPs=fixed
finalBPs=final

colnames(rawBPs) = lab
colnames(fixedBPs) = lab
colnames(finalBPs) = lab

# Need to root NJ tree, make tree ultrametric by extending branch lengths then convert to hclust object!
phylo2hclust = function(phy)
{
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

write("Making heatRaw.jpeg", stderr())
jpeg("heatRaw.jpeg", width=2000, height=1400)
heatmap.2(t(rawBPs), Colv=FALSE, Rowv=as.dendrogram(clust), margins=c(5,20), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=bluered(2))
dev.off()

write("Making heatNorm.jpeg", stderr())
step=quantile(fixedBPs, c(.98))[[1]]
jpeg("heatNorm.jpeg", width=2000, height=1400)
heatmap.2(t(fixedBPs), Colv=FALSE, Rowv=as.dendrogram(clust), margins=c(5,20), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=bluered(15), breaks=seq(0,step,step/15))
dev.off()

write("Making heatCN.jpeg", stderr())
step=min(20, quantile(finalBPs, c(.98))[[1]])
jpeg("heatCN.jpeg", width=2000, height=1400)
heatmap.2(t(finalBPs), Colv=FALSE, Rowv=as.dendrogram(clust2), margins=c(5,20), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=colorRampPalette(c("white","green","green4","violet","purple"))(15), breaks=seq(0,step,step/15))
dev.off()

write("Making heatCor.jpeg", stderr())
jpeg("heatCor.jpeg", width=2000, height=1400)
heatmap.2(t(finalBPs), Colv=FALSE, Rowv=as.dendrogram(clust3), margins=c(5,20), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=colorRampPalette(c("white","steelblue1","steelblue4","orange","sienna3"))(15), breaks=seq(0,step,step/15))
dev.off()

#jpeg("heatRaw.jpeg", width=2000, height=1400)
#heatmap3(t(rawBPs), distfun = function(x) dist(x), Colv=FALSE, Rowv=as.dendrogram(clust), margins=c(5,20), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=bluered(2))
#dev.off()
#
#step=quantile(fixedBPs, c(.98))[[1]]
#jpeg("heatNorm.jpeg", width=2000, height=1400)
#heatmap3(t(fixedBPs), distfun = function(x) dist(x), Colv=FALSE, Rowv=as.dendrogram(clust), margins=c(5,20), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=bluered(15), breaks=seq(0,step,step/15))
#dev.off()
#
#step=min(20, quantile(finalBPs, c(.98))[[1]])
#jpeg("heatCN.jpeg", width=2000, height=1400)
#heatmap3(t(finalBPs), distfun = function(x) dist(x), Colv=FALSE, Rowv=as.dendrogram(clust2), margins=c(5,20), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=colorRampPalette(c("white","green","green4","violet","purple"))(15), breaks=seq(0,step,step/15))
#dev.off()
#
#jpeg("heatCor.jpeg", width=2000, height=1400)
#heatmap3(t(finalBPs), distfun = function(x) dist(x), Colv=FALSE, Rowv=as.dendrogram(clust3), margins=c(5,20), dendrogram="row", trace="none", xlab="Bins", ylab="Samples", cex.main=2, cex.axis=1.5, cex.lab=1.5, cexCol=.001, col=colorRampPalette(c("white","steelblue1","steelblue4","orange","sienna3"))(15), breaks=seq(0,step,step/15))
#dev.off()

statusFile=file( paste(user_dir, "/", status, sep="") )
writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>Finished</processingfile>", sep=""), paste("<percentdone>100</percentdone>", sep=""), "<tree>clust.xml</tree>", "</status>"), statusFile)
close(statusFile)

