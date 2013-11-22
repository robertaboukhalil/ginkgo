Ginkgo
=========

#### Ginkgo is a cloud-based single-cell copy-number variation analysis tool.
#### Launch Ginkgo: [qb.cshl.edu/ginkgo](http://qb.cshl.edu/ginkgo)

Usage
=========

**Step 0: Upload .bed files**

<img src="http://qb.cshl.edu/ginkgo/screenshots/0.png" width="400" />

**Step 1: Choose analysis parameters**

<img src="http://qb.cshl.edu/ginkgo/screenshots/1.png" width="400" />

**Step 2: Phylogenetic Tree**

<img src="http://qb.cshl.edu/ginkgo/screenshots/2.png" width="400" />

**Step 3: Analyse Individual Cells**

<img src="http://qb.cshl.edu/ginkgo/screenshots/3.png" width="400" />


Setup Ginkgo on your own server
=========

**Requirements:**

- PHP >=5.2
- R >=2.15.2
- R Packages:
	- ctc
	- DNAcopy
	- inline
	- gplots
	- scales
	- plyr

**Install Ginkgo:**

Type ```make``` in the ginkgo/ directory

**Server Configuration:**

- /etc/php.ini
	- ```upload_tmp_dir```: make sure this directory has write permission
	- ```upload_max_filesize```: set to >2G since .bam files can be large

- ginkgo/includes/fileupload/server/php/UploadHandler.php
	- In constructor, on line 43 and 44:
		- ```upload_dir = [FULL_PATH_TO_UPLOADS_DIR] . $_SESSION["user_id"] . '/'```
		- ```upload_url = [FULL_URL_TO_UPLOADS_DIR]  . $_SESSION["user_id"] . '/'```

- ginkgo/bootstrap.php
	- Change ```DIR_ROOT```, ```DIR_UPLOADS``` and ```URL_ROOT```

- ginkgo/scripts/analyze
	- Change ```home``` variable to where the ginkgo/ folder is located

- ginkgo/scripts/process.R
	- Change ```main_dir``` variable to the folder where ginkgo/scripts is located

