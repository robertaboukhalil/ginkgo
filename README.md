Ginkgo
=========

#### Ginkgo is a cloud-based single-cell copy-number variation analysis tool.
#### Launch Ginkgo: [qb.cshl.edu/ginkgo](http://qb.cshl.edu/ginkgo)

### Usage

**Step 0: Upload files**

![Alt text](http://qb.cshl.edu/ginkgo/screenshots/step0.png)

**Step 1: Choose analysis parameters**

![Alt text](http://qb.cshl.edu/ginkgo/screenshots/step1.png)

**Step 2: Phylogenetic Tree**

![Alt text](http://qb.cshl.edu/ginkgo/screenshots/step2.png)

**Step 3: Analyse Individual Cells**


### Setup Ginkgo on your own server

**Requirements:**

PHP >=5.2, R >=2.15.2

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
	- Change DIR_ROOT, DIR_UPLOADS and URL_ROOT

- scripts/*.R [TO CHANGE]
	- Add main_dir as var defined somewhere else
	- Install packages: ctc

