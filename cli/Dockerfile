
FROM debian:jessie

MAINTAINER Robert Aboukhalil <robert.aboukhalil@gmail.com>

# -- Update package list -------------------------------------------------------
RUN apt-get update

# -- Install R packages --------------------------------------------------------
# Appends the CRAN repository to your sources.list file 
RUN sh -c 'echo "deb http://cran.rstudio.com/bin/linux/debian lenny-cran/" >> /etc/apt/sources.list'

# Add CRAN GPG key
RUN apt-key adv --keyserver hkp://pgp.mit.edu:11371 --recv-key 381BA480
RUN apt-get update
RUN apt-get install r-base r-base-dev -y

# Install bioconductor + bioconductor packages
RUN R -e 'source("http://bioconductor.org/biocLite.R"); biocLite(suppressUpdates=TRUE); biocLite("ctc",suppressUpdates=TRUE); biocLite("DNAcopy",suppressUpdates=TRUE);'

# Install R packages
RUN R -e 'install.packages("inline", repos="http://cran.us.r-project.org");'
RUN R -e 'install.packages("gplots", repos="http://cran.us.r-project.org");'
RUN R -e 'install.packages("plyr", repos="http://cran.us.r-project.org");'
RUN R -e 'install.packages("gridExtra", repos="http://cran.us.r-project.org");'
RUN R -e 'install.packages("fastcluster", repos="http://cran.us.r-project.org");'
RUN R -e 'install.packages("heatmap3", repos="http://cran.us.r-project.org")'

# scales/ggplot has plyr dependency...
RUN apt-get install r-cran-plyr -y
RUN R -e 'install.packages("scales", repos="http://cran.us.r-project.org");'
RUN R -e 'install.packages("ggplot2", repos="http://cran.us.r-project.org");'

# -- Install Java --------------------------------------------------------------
RUN echo "deb http://ppa.launchpad.net/webupd8team/java/ubuntu xenial main" | tee /etc/apt/sources.list.d/webupd8team-java.list
RUN echo "deb-src http://ppa.launchpad.net/webupd8team/java/ubuntu xenial main" | tee -a /etc/apt/sources.list.d/webupd8team-java.list
RUN echo "oracle-java8-installer shared/accepted-oracle-license-v1-1 select true" | debconf-set-selections
RUN apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys EEA14886
RUN apt-get update
RUN apt-get install oracle-java8-installer -y

# -- Retrieve latest Ginkgo code from Github -----------------------------------
RUN apt-get install git wget -y
RUN mkdir -p /mnt/data/ginkgo/ && \
    cd /mnt/data/ && \
    git clone https://github.com/robertaboukhalil/ginkgo.git && \
    cd /mnt/data/ginkgo/ && \
    make

# # -- Setup hg19 --------------------------------------------------------------
# # -- NOTE: removed this to make genome support not dependent on docker image
# #RUN mkdir -p /mnt/data/ginkgo/genomes/hg19/original && \
# #    cd /mnt/data/ginkgo/genomes/hg19/original && \
# #    wget http://qb.cshl.edu/mnt/data/ginkgo/uploads/hg19.original.tar.gz && \
# #    tar -xvf hg19.original.tar.gz

