Ginkgo
=========

#### Ginkgo is a cloud-based single-cell copy-number variation analysis tool.

### Todo

###### v0.6
###### v0.7
###### v0.8
###### v0.9
- Gene search: Users can plot the locations of (up to 10) specific genes on top of the segmentation plots.
- Test:
	- If change parameters and re-run, only do necessary analysis
	- If add files to analysis, only run analysis on new files
	- Check that segmentation file works
	- Check that FACS file works

###### v1.0
- Add link to sample data set
- Add support for other species in dashboard?
- Add description for heatmaps
- About page
- FAQ page
- Paper citations:
	-> Smits SA, Ouverney CC, 2010 jsPhyloSVG: A Javascript Library for Visualizing Interactive and Vector-Based Phylogenetic Trees on the Web.
- Don't plot trees to jpeg, should be faster
- Don't plot [CELL].jpeg files with such high resolution

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

