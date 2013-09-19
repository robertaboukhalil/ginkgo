Ginkgo: Single-cell copy-number analysis
=========

### Todo

###### First release
- If add files to analysis, only run analysis on new files
- Show QA stats
- Show stats per cell
- Upload custom bin list
- Upload FACS file
- Upload gene list

###### Next release
- Have "Cancel" button to stop an analysis (but how? store pid?)
- Using the same dataset, run parallel analyses with different settings

---

### Files
- finish.R, facs.R
- finish2.R, facs2.R (only when the user changes the clustering method or distance metric but nothing else)
- hist.newick = dendrogram of the samples based on their normalized read count
- hist2.newick = dendrogram of the samples based on their copy number state

---

### Setup

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

