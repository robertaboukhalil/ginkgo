Ginkgo: Single-cell CNV analysis
=========

#### Ginkgo is a cloud-based single-cell copy-number variation analysis tool.

### Todo

###### v0.8
- clust.xml: find max value and normalize by it

- If add files to analysis, only run analysis on new files
- If change parameters and re-run, only do necessary analysis
- Show stats per cell
- Rename intervals.bed to intervals.txt

- Upload gene list [${dir}/query.txt]
- analyze: ./process.R ....... query.txt ....... ---> change that to ${dir}/query.txt

- Line 476 (process.R): clust.xml -> clust2.xml

- Don't plot trees to jpeg, should be faster
- Don't plot [CELL].jpeg files with such high resolution

###### v0.9
- Check that binning file works
- Check that FACS file works

###### v1.0
- Add link to sample data set
- Add support for other species in dashboard
- Have better QA descriptions
- About page
- FAQ page
- Paper citations:
	-> Smits SA, Ouverney CC, 2010 jsPhyloSVG: A Javascript Library for Visualizing Interactive and Vector-Based Phylogenetic Trees on the Web.

###### v2.0
- Have "Cancel" button to stop an analysis (but how? store pid?)
- Using the same dataset, run parallel analyses with different settings

---

### Setup

- g++ scripts/*.cpp

- /etc/php.ini
	- upload_tmp_dir: make sure this directory has write permission
	- upload_max_filesize: 2G

- UploadHandler.php settings
	- In constructor:
		- upload_dir = [FULL_PATH_TO_UPLOADS_DIR] . $_SESSION["user_id"] . '/'
		- upload_url = [FULL_URL_TO_UPLOADS_DIR]  . $_SESSION["user_id"] . '/'

- bootstrap.php
	- Change DIR_ROOT, DIR_UPLOADS and URL_ROOT

- scripts/*.R [TO CHANGE]
	- Add main_dir as var defined somewhere else
	- Install packages

---

### Files
- finish.R, facs.R, stats.R, process.R, queryAll.R, reclust.R
- status.cpp, testBED.cpp, binUnsorted.cpp
- hist.newick = tree of the samples based on their normalized read count
- forester_1025.jar = newick to phyloxml converter

