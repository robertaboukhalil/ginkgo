#!/usr/bin/env Rscript

args<-commandArgs(TRUE)

main_dir <- args[[1]]
user_dir <- args[[2]]
status <- args[[3]]
dat <- args[[4]]
bm <- as.numeric(args[[5]])
stat <- as.numeric(args[[6]])
cm <- as.numeric(args[[7]])
dm <- as.numeric(args[[8]])

library('ctc')
library(DNAcopy)

setwd(main_dir)
v=read.table(paste("GC_bins_", bm, sep=""), header=FALSE, sep="\t")

setwd(user_dir)
T=read.table(dat, header=TRUE, sep="\t")

lab=colnames(T[3:dim(T)[2]])
clust_meth=c("single", "complete", "average", "ward")
dist_metric=c("euclidean", "maximum", "manhattan", "canberra", "binary", "minkowski")

l=dim(T)[1]
w=dim(T)[2]-2

a=array(0,w)
for(i in 1:w){
  a[i]=var(T[,(i+2)])
  if (stat == 0){
    #statusFile<-file(status)
    	statusFile<-file( paste(user_dir, "/", status, sep="") )
    writeLines(c("<?xml version='1.0'?>", "<status>", "<step>2</step>", paste("<processingfile>", lab[i], "</processingfile>", sep=""), paste("<percentdone>", (i/w)*100, "</percentdone>", sep=""), "<tree>0</tree>", "</status>"), statusFile)
    close(statusFile)
  }
}
comp=which.max(a)

if (stat == 1){
  p=array(0,w)
  for(i in 1:w){
    #statusFile<-file(status)
    	statusFile<-file( paste(user_dir, "/", status, sep="") )
    writeLines(c("<?xml version='1.0'?>", "<status>", "<step>2</step>", paste("<processingfile>", lab[i], "</processingfile>", sep=""), paste("<percentdone>", (i*100)%/%wt, "</percentdone>", sep=""), "<tree>0</tree>", "</status>"), statusFile)
    close(statusFile)
    p[i]=chisq.test(T[,(i+2)], v[,1])[[3]]
  }

  if (max(p) != 0 & min(p) != 1){
    comp = which(p==min(p[p > 0]))
  }
}


F=T[,(comp+2)]

fixed=matrix(0,l,w)
final=matrix(0,l,w)

#statusFile<-file(status)
statusFile<-file( paste(user_dir, "/", status, sep="") )
 writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>", lab[i], "</processingfile>", sep=""), paste("<percentdone>", 0, "</percentdone>", sep=""), "<tree>0</tree>", "</status>"), statusFile)
close(statusFile)
    
for(k in 1:w){

	lr = -log2((T[,(k+2)]+1)/(F+1))

	CNA.object <-CNA(genomdat = lr, chrom = T[,1], maploc = T[,2], data.type = 'logratio')
	CNA.smoothed <- smooth.CNA(CNA.object)
	segs <- segment(CNA.smoothed, verbose=0, min.width=2)
	frag = segs$output[,2:3]
	
	len=dim(frag)[1]
	bps=array(0, len)
	for (j in 1:len){
		bps[j]=which((T[,1]==frag[j,1]) & (T[,2]==frag[j,2]))
	}
	bps=sort(bps)

	fixed[,k][1:bps[2]] = median(T[,(k+2)][1:bps[2]])

	for(i in 2:len-1){
	  fixed[,k][bps[i]:bps[i+1]] = median(T[,(k+2)][bps[i]:bps[i+1]])
	}
	
	final[,k]=fixed[,k]/max(fixed[,k])

        jpeg(filename=paste(lab[k], ".jpeg", sep=""), width=2000, height=1400)
        plot(final[,k], main=lab[k], ylab="bin")
        dev.off()

	if (k > 2 && ((k <= 10) || (k%%(w%/%10) == 0)))
	{
	  mat=matrix(0,nrow=k,ncol=k)
	  for (i in 1:k){
	    for (j in 1:k){
	      mat[i,j]=dist(rbind(fixed[,i]/sum(T[,(i+2)]), fixed[,j]/sum(T[,(j+2)])))
	    }
	  }
	 
	  d = dist(mat, method = dist_metric[dm+1])
	  T_clust = hclust(d, method = clust_meth[cm+1])
	  T_clust$labels = lab
	  write(hc2Newick(T_clust), file=paste(user_dir, "/hist.newick", sep=""))
	  #write(hc2Newick(T_clust), file="hist.newick")
 

	  ###
	  command=paste("java -cp ", main_dir, "/forester_1025.jar org.forester.application.phyloxml_converter -f=nn ", user_dir, "/hist.newick ", user_dir, "/hist.xml", sep="");
	  unlink( paste(user_dir, "/hist.xml", sep="") );
	  system(command);
	  ###	  
	}

	statusFile<-file( paste(user_dir, "/", status, sep="") )
	#statusFile<-file(status)
	writeLines(c("<?xml version='1.0'?>", "<status>", "<step>3</step>", paste("<processingfile>", lab[k], "</processingfile>", sep=""), paste("<percentdone>", (k*100)%/%w, "</percentdone>", sep=""), "<tree>hist.xml</tree>", "</status>"), statusFile)
	close(statusFile)
}

write.table(fixed/sum(T[,3:(w+2)]), file=paste(user_dir, "/SegFile", sep=""), row.names=FALSE, col.names=lab, sep="\t")
#write.table(fixed/sum(T[,3:(w+2)]), file="SegFile", row.names=FALSE, col.names=lab, sep="\t")

pdf(file="tree.pdf", width=2000, height=1400)
plot(T_clust)
##dev.off()
