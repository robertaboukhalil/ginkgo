<?php

error_reporting(E_ALL);
set_time_limit(0);
#set_memory_limit(-1);

/** Configuration **/

set_include_path(PATH_SEPARATOR . 'includes/phpseclib');
include("Net/SFTP.php");

/** Initialization **/

//
$query	= $_POST["query"];
if(isset($_GET["query"]))
	$query = $_GET["query"];
$params	= $_POST["params"];

//
define('DIR', "data/" . $params["id"] . "/");

/** Do stuff based on what query is given **/
switch($query)
{
	// -----------------------------------------------------------------------------------
	// Download files from FTP
	// -----------------------------------------------------------------------------------
	case "upload-ftp":
		// Parse URL
		$URL = parse_url($params["ftp"]);
		$html= "";

		// Connect and login
		$connection	 = ftp_connect($URL["host"]);
		$loginResult	 = ftp_login($connection, "anonymous", "");

		// List files
		$allFiles = ftp_nlist($connection, $URL["path"]);

		// Download .bam files
		foreach($allFiles as $currentFile)
		{
			$fileInfo = pathinfo( strtolower($currentFile) );
			$saveTo   = DIR . $currentFile;

			if($fileInfo["extension"] != "txt" || file_exists($saveTo))
				continue;

			// Download file
			ftp_get($connection, $saveTo, $URL["path"] . $currentFile, FTP_BINARY);

			// And add to queue 1 and 2
			q($currentFile, Array(1, 2));

			// Append checkbox
			$html .= '<label class="checkbox">	<input type="checkbox" checked> ' . $currentFile . '</label>';
		}

		// Close FTP connection
		ftp_close($connection);

		echo $html;
		break;

	// -----------------------------------------------------------------------------------
	// List my uploaded .bed files
	// -----------------------------------------------------------------------------------
	case "list-files":
		$allFiles = glob("data/" . $_GET["user_id"] . "/*.bed");
		$excludedFiles = glob("data/" . $_GET["user_id"] . "/*.old");
		$excludedFiles = array_merge($excludedFiles, glob("data/" . $_GET["user_id"] . "/*_done"));
		$excludedFiles = array_merge($excludedFiles, glob("data/" . $_GET["user_id"] . "/*_rdy"));
		foreach($allFiles as $file)
		{
			if(is_dir($file) || in_array($file, $excludedFiles))
				continue;

			echo '<label class="checkbox">	<input type="checkbox" checked id="' . basename($file) . '"> ' . basename($file) . '</label>';
		}
		
		break;


	// -----------------------------------------------------------------------------------
	// Start analyzing the data: NOTE THIS IS **NOT** DONE IN PARALLEL; don't wait for this
	// 							 to return a value
	// -----------------------------------------------------------------------------------
	case "analyze":

		$user_id = $_GET["user_id"];
		$files   = $_GET["files"];

		/* Parameters */
		$param_bins_type		= $_GET['param_bins_type'];
		$param_bins_size		= $_GET['param_bins_size'];
		$param_segmentation	= $_GET['param_segmentation'];
		$param_clustering	= $_GET['param_clustering'];
		$param_distance		= $_GET['param_distance'];

		// Do we have new parameters? (boolean)
		$new_bins			= $_GET['new_bins'];
		$new_segmentation	= $_GET['new_segmentation'];
		$new_clustering		= $_GET['new_clustering'];
		$new_distance		= $_GET['new_distance'];

		$Step1 = Array();
		$Step2 = Array();
		$Step3 = Array();

		/* Go through all files and put into $Step1/2/3 arrays */
		foreach($files as $file)
		{
			$currentFile = "data/$user_id/$file";

			// Invalid file?
			if(!file_exists($currentFile))
				continue;

			// Did the user select a new file
			if(!file_exists($currentFile . ".old"))
			{
				$files_new[] = $file; #$currentFile;
				file_put_contents($currentFile . ".old", "");
			}
			else
				$files_old[] = $file; #$currentFile;

#			// Has this file been processed before?
#			$newFile = false;
#			if(!file_exists($currentFile.".old"))
#			{
#				$newFile = true;
#				file_put_contents($currentFile.".old", "");
#			}
		}

		// WARNING: Having 2 queues (old/new) seems overkill but is necessary in the following scenario:
		// 			If the user selects e.g. file 1, 3 and does analysis (=> run step 1 thru 3),
		//			then changes tree options and selects file 2 and does analysis. In this case,
		//			we need to run file 1/3 only with step 3, and file 2 with 1, 2, 3

		/* NEW FILES - Which steps are needed? */
		// Need all steps for new files (if any)
		$steps_new = Array(1 => true, 2 => true, 3 => true);

		/* OLD FILES - Which steps are needed? */
		// Step 1:	Map uploaded files based on selected binning options
		// 			Only do that if changed binning options
		$steps_old = Array(1 => 0, 2 => 0, 3 => 0); // won't work with ___ => false
		if($new_bins)
			$steps_old[1] = true;
		// Step 2:	Segmentation
		// 			Only do that if changed binning options or changed segmentation options
		if($new_bins || $new_segmentation)			
			$steps_old[2] = true;
		// Step 3:	Clustering + Tree
		// 			Only do that if new file or user changes clustering method/distance metric
		if($new_bins || $new_segmentation || $new_clustering || $new_distance)
			$steps_old[3] = true;

		/* Reset previous analyses */
		@unlink("data/$user_id/thumbnail/files_new");
		@unlink("data/$user_id/thumbnail/files_old");
		@unlink("data/$user_id/thumbnail/status_new.xml");
		@unlink("data/$user_id/thumbnail/status_old.xml");
		@unlink("data/$user_id/hist.xml");

		/* Specify which files to analyze for old + new analysis */
		file_put_contents("data/$user_id/thumbnail/files_old", implode("\n", $files_old) . "\n");
		file_put_contents("data/$user_id/thumbnail/files_new", implode("\n", $files_new) . "\n");
		
		/* Prepare analysis */
		$parameters = ($param_bins_size + $param_bins_type) . " " . $param_segmentation . " " . $param_clustering . " " . $param_distance;
		$cmd_old = "./analyze " . escapeshellarg("/mnt/data/atwal/singlecell/data/$user_id") . " " . escapeshellarg("thumbnail/files_old") . " " . escapeshellarg("thumbnail/status_old.xml") . " " . $steps_old[1] . " " . $steps_old[2] . " " . $steps_old[3] . " " . $parameters;
		$cmd_new = "./analyze " . escapeshellarg("/mnt/data/atwal/singlecell/data/$user_id") . " " . escapeshellarg("thumbnail/files_new") . " " . escapeshellarg("thumbnail/status_new.xml") . " 1 1 1 " . $parameters;

#if(!empty($files_old)) echo 'OLD:'. $cmd_old . "\n";
#if(!empty($files_new)) echo 'NEW:'. $cmd_new . "\n";
#exit;

		/* Run analysis */
		if(!empty($files_old))
		{
			echo $cmd_old;
			// Add redirection so we can get stderr.
			session_regenerate_id(TRUE);	
			$handle = popen($cmd_old, 'r');
			$out = stream_get_contents($handle);
			pclose($handle);
			echo $out . "[old]\n";
		}
		else
		{
			file_put_contents("/mnt/data/atwal/singlecell/data/$user_id/thumbnail/status_old.xml", "<?xml version='1.0'?>
<status>
<step>4</step>
<processingfile>Done</processingfile>
<percentdone>100</percentdone>
<tree>0</tree>
</status>");	
		}

		if(!empty($files_new))
		{
			echo $cmd_new;

			// Add redirection so we can get stderr.
			session_regenerate_id(TRUE);	
			$handle = popen($cmd_new, 'r');
			$out = stream_get_contents($handle);
			pclose($handle);
			echo $out . "[new]\n";		
		}
		else
		{
			file_put_contents("/mnt/data/atwal/singlecell/data/$user_id/thumbnail/status_new.xml", "<?xml version='1.0'?>
<status>
<step>4</step>
<processingfile>Done</processingfile>
<percentdone>100</percentdone>
<tree>0</tree>
</status>");	
		}

		echo "Done!\n";
		break;
}





/** Queue Management **/

// Add file to one or more of the queues
function q($file, $queues)
{
	foreach($queues as $currentQ)
	{
		$dir = DIR . "q{$currentQ}/";
		if(!file_exists($dir))
			mkdir($dir);
			
		file_put_contents($dir . $file, "");
	}
}

