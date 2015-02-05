#!/usr/bin/env Rscript

# ------------------------------------------------------------------------------
# --
# ------------------------------------------------------------------------------

args			= commandArgs(TRUE)
userID			= args[[1]]
analysisID		= args[[2]]
#
setwd(paste('/mnt/data/ginkgo/uploads/', userID, sep=''))
# cat(userID,'\n',analysisID,'\n',sep='')

# --
selectedCells	= read.table( paste(analysisID, '.config', sep=''), header=TRUE)
analysisType	= colnames(selectedCells)[1]

# --
raw <- read.table('data', header=TRUE, sep="\t")
l <- dim(raw)[1] #Number of bins
w <- dim(raw)[2] #Number of samples
#
normal <- sweep(raw+1, 2, colMeans(raw+1), '/')
normal2 = normal
#
bm = 'variable_500000_101_bowtie'
GC <- read.table(paste("../../genomes/hg19/original/GC_", bm, sep=""), header=FALSE, sep="\t", as.is=TRUE)



# --
cellIDs = c()
for(i in 1:length(selectedCells[,1]))
	cellIDs[i] = which(colnames(raw) == as.character(selectedCells[i, 1]))

if(is.null(cellIDs))
	stop("Error")

# -- Initialize color palette
cp = 2
col1 = col2 = matrix(0,3,2)
col1[1,] = c('darkmagenta', 'goldenrod')
col1[2,] = c('darkorange', 'dodgerblue')
col1[3,] = c('blue4', 'brown2')
col2[,1] = col1[,2]
col2[,2] = col1[,1]



# ------------------------------------------------------------------------------
# -- Plot Lorenz curves
# ------------------------------------------------------------------------------
if(analysisType == "lorenz")
{
	jpeg(filename=paste(analysisID, ".jpeg", sep=""), width=500, height=500)
	# par(mar = c(7.0, 7.0, 7.0, 3.0))

	#
	plottedFirst = 0
	for(k in cellIDs)
	{
		nReads = sum(raw[,k])
		uniq = unique(sort(raw[,k]))
		lorenz = matrix(0, nrow=length(uniq), ncol=2)
		a = c(length(which(raw[,k]==0)), tabulate(raw[,k], nbins=max(raw[,k])))
		b = a*(0:(length(a)-1))
		for (i in 2:length(uniq)) {
			lorenz[i,1] = sum(a[1:uniq[i]]) / l
			lorenz[i,2] = sum(b[2:uniq[i]]) / nReads
		}

		if(plottedFirst == 0) {
			plot(lorenz, type="n", xlim=c(0,1), main="Lorenz Curve of Coverage Uniformity", xlab="Cumulative Fraction of Genome", ylab="Cumulative Fraction of Total Reads", xaxt="n", yaxt="n", cex.main=2, cex.axis=1.5, cex.lab=1.5)
			# plot(lorenz, xlim=c(0,1), main=paste("Lorenz Curve of Coverage Uniformity for Sample ", k, sep=""), xlab="Cumulative Fraction of Genome", ylab="Cumulative Fraction of Total Reads", type="n", xaxt="n", yaxt="n", cex.main=3, cex.axis=2, cex.lab=2)
		} else {
			points(lorenz, type="n")
			# points(lorenz, xlim=c(0,1), type="n", xaxt="n", yaxt="n") #, cex.main=3, cex.axis=2, cex.lab=2)
		}

		try(lines(smooth.spline(lorenz), col=col1[cp,2], lwd=2.5), silent=TRUE)

		if(plottedFirst == 0)
		{
			tu <- par('usr')
			par(xpd=FALSE)
			rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
			abline(h=seq(0,1,.1), col="white", lwd=2)
			abline(v=seq(0,1,.1), col="white", lwd=2)
			axis(side=1, at=seq(0,1,.1), tcl=.5, cex.axis=2)
			axis(side=2, at=seq(0,1,.1), tcl=.5, cex.axis=2)
			axis(side=3, at=seq(0,1,.1), tcl=.5, cex.axis=2, labels=FALSE)
			axis(side=4, at=seq(0,1,.1), tcl=.5, cex.axis=2, labels=FALSE)
			lines(c(0,1), c(0,1), lwd=2.5)
			tu <- par('usr')
			par(xpd=FALSE)

		}
		plottedFirst = 1
	}

	legend("topleft", inset=.05, legend=c("Perfect Uniformity", "Sample Uniformity"), fill=c("black", col1[cp,2]), cex=1.5)
	dev.off()
	file.create(paste(analysisID,'.done', sep=""))
}


# ------------------------------------------------------------------------------
# -- Plot GC curves
# ------------------------------------------------------------------------------
if(analysisType == "gc")
{

}


























