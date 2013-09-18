Ginkgo: Single-cell copy-number analysis
=========

### Todo

###### First release
- If launch an analysis that was already run before, don't redo it

###### Next release
- Have "Cancel" button to stop an analysis (but how? store pid?)
- Using the same dataset, run parallel analyses with different settings

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

