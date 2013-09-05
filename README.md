Ginkgo: A single-cell copy-number analyzer
=========

This is Ginkgo.

### Setup

- /etc/php.ini
	-> upload_tmp_dir: make sure this directory has write permission
	-> upload_max_filesize: 2G

- UploadHandler.php settings
	-> In constructor:
		-> upload_dir = [FULL_PATH_TO_UPLOADS_DIR] . $_SESSION["user_id"] . '/'
		-> upload_url = [FULL_URL_TO_UPLOADS_DIR]  . $_SESSION["user_id"] . '/'

- bootstrap.php
	-> Change DIR_ROOT, DIR_UPLOADS and URL_ROOT

### Files



