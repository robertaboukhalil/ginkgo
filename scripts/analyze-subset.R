#!/usr/bin/env Rscript

# ------------------------------------------------------------------------------
# --
# ------------------------------------------------------------------------------

args			= commandArgs(TRUE)
userID			= args[[1]]
analysisID		= args[[2]]
genome			= args[[3]]
bm				= args[[4]]
pseudoautosomal = args[[5]]

# --
setwd(paste('/mnt/data/ginkgo/uploads/', userID, sep=''))
maxPloidy = 6

# --
selectedCells	= read.table( paste(analysisID, '.config', sep=''), header=TRUE)
analysisType	= colnames(selectedCells)[1]

# --
raw = read.table('data', header=TRUE, sep="\t")
l = dim(raw)[1] # Number of bins
w = dim(raw)[2] # Number of samples
#
normal = sweep(raw+1, 2, colMeans(raw+1), '/')
normal2 = normal
#
GC = read.table(paste("../../genomes/", genome, "/", pseudoautosomal, "/GC_", bm, sep=""), header=FALSE, sep="\t", as.is=TRUE)
#
bounds = read.table(paste("../../genomes/", genome, "/", pseudoautosomal, "/bounds_", bm, sep=""), header=FALSE, sep="\t")
final  = read.table('SegCopy', header=TRUE, sep="\t")
fixed  = read.table('SegFixed', header=TRUE, sep="\t")
#
final  = final[,-c(1,2,3)]
fixed  = fixed[,-c(1,2,3)]



# --
cellIDs = c()
for(i in 1:length(selectedCells[,1]))
	cellIDs[i] = which(colnames(raw) == as.character(selectedCells[i, 1]))

if(is.null(cellIDs))
	stop("Error")

# -- Initialize color palette
cp = 3
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


		if(plottedFirst == 0)
		{
			tu = par('usr')
			par(xpd=FALSE)
			rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
			abline(h=seq(0,1,.1), col="white", lwd=2)
			abline(v=seq(0,1,.1), col="white", lwd=2)
			axis(side=1, at=seq(0,1,.1), tcl=.5, cex.axis=2)
			axis(side=2, at=seq(0,1,.1), tcl=.5, cex.axis=2)
			axis(side=3, at=seq(0,1,.1), tcl=.5, cex.axis=2, labels=FALSE)
			axis(side=4, at=seq(0,1,.1), tcl=.5, cex.axis=2, labels=FALSE)
			lines(c(0,1), c(0,1), lwd=2.5)
			tu = par('usr')
			par(xpd=FALSE)

		}

		plottedFirst = 1
		try(lines(smooth.spline(lorenz), col=col1[cp,2], lwd=2.5), silent=TRUE)

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
	jpeg(filename=paste(analysisID, ".jpeg", sep=""), width=500, height=500)

	#
	plottedFirst = 0
	for(k in cellIDs)
	{
		low = lowess(GC[,1], log(normal2[,k]), f=0.05)
		app = approx(low$x, low$y, GC[,1])
		cor = exp(log(normal2[,k]) - app$y)

		if(plottedFirst == 0)
			try(plot(GC[,1], log(normal2[,k]), main="GC Content vs. Bin Counts", type= "n", xlim=c(min(.3, min(GC[,1])), max(.6, max(GC[,1]))), xlab="GC content", ylab="Normalized Read Counts (log scale)", cex.main=2, cex.axis=1.5, cex.lab=1.5))
		else
			try(points(GC[,1], log(normal2[,k]), type="n"))

		if(plottedFirst == 0)
		{
			tu = par('usr')
			par(xpd=FALSE)
			rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
			abline(v=axTicks(1), col="white", lwd=2)
			abline(h=axTicks(2), col="white", lwd=2)
		}

		plottedFirst = 1
		try(points(app, col=col1[cp,2]))
	}

	# legend("bottomright", inset=.05, legend="Lowess Fit", fill=col1[cp,2], cex=1.5)
	dev.off()
	file.create(paste(analysisID,'.done', sep=""))
}


# ------------------------------------------------------------------------------
# -- Plot GC curves
# ------------------------------------------------------------------------------
if(analysisType == "cnvprofiles")
{
	library(scales)   # for alpha() opacity used in points() function

	nbCells = length(cellIDs)
	jpeg(filename=paste(analysisID, ".jpeg", sep=""), width=1000, height=200*nbCells)
	# layout(matrix(c(nbCells,1), nbCells, 1, byrow=TRUE))
	par(mfrow=c(nbCells,1)) 

	#
	for(k in cellIDs)
	{
		# -- New cell
		plot(normal[,k], main=selectedCells[k,1], ylim=c(0, 8), type="n", xlab="Bin", ylab="Copy Number", cex.main=2, cex.axis=1.5, cex.lab=1.5)
		#
		tu = par('usr')
		par(xpd=FALSE)
		rect(tu[1], tu[3], tu[2], tu[4], col = "gray85")
		abline(h=0:19, lty=2)

		# -- Calculate CNmult (because not saved anywhere)
		CNmult = matrix(0,5,w)
		outerColsums = matrix(0, (20*(maxPloidy-1.5)+1), w)

		CNgrid = seq(1.5, maxPloidy, by=0.05)
		outerRaw = fixed[,k] %o% CNgrid
		outerRound = round(outerRaw)
		outerDiff = (outerRaw - outerRound) ^ 2
		outerColsums[,k] = colSums(outerDiff, na.rm = FALSE, dims = 1)
		CNmult[,k] = CNgrid[order(outerColsums[,k])[1:5]]

		# -- Plot
		flag=1
		points(normal[(0:bounds[1,2]),k]*CNmult[1,k], ylim=c(0, 6), pch=20, cex=1.5, col=alpha(col1[cp,flag], .2))
		points(final[(0:bounds[1,2]),k], ylim=c(0, 8), pch=20, cex=1.5, col=alpha(col2[cp,flag], .2))
		for (i in 1:(dim(bounds)[1]-1))
		{
			points((bounds[i,2]:bounds[(i+1),2]), normal[(bounds[i,2]:bounds[(i+1),2]),k]*CNmult[1,k], ylim=c(0, 6), pch=20, cex=1.5, col=alpha(col2[cp,flag], 0.2))
			points((bounds[i,2]:bounds[(i+1),2]), final[(bounds[i,2]:bounds[(i+1),2]),k], ylim=c(0, 8), pch=20, cex=1.5, col=alpha(col1[cp,flag], 0.2))
			if (flag == 1)
				flag = 2
			else
				flag = 1
		}
		points((bounds[(i+1),2]:l), normal[(bounds[(i+1),2]:l),k]*CNmult[1,k], ylim=c(0, 8), pch=20, cex=1.5, col=alpha(col2[cp,flag], .2))
		points((bounds[(i+1),2]:l), final[(bounds[(i+1),2]:l),k], ylim=c(0, 6), pch=20, cex=1.5, col=alpha(col1[cp,flag], .2))
	}

	dev.off()
	file.create(paste(analysisID,'.done', sep=""))
}


# ------------------------------------------------------------------------------
# -- Plot MAD curves
# ------------------------------------------------------------------------------
if(analysisType == "mad")
{
	
}





















