<?php

// =============================================================================
//   _____ _       _
//  / ____(_)     | |
// | |  __ _ _ __ | | ____ _  ___
// | | |_ | | '_ \| |/ / _` |/ _ \
// | |__| | | | | |   < (_| | (_) |
//  \_____|_|_| |_|_|\_\__, |\___/
//                      __/ |
//                     |___/   1.0
// 
// =============================================================================


// =============================================================================
// == Configuration ============================================================
// =============================================================================

include "bootstrap.php";


// =============================================================================
// == Parse user query =========================================================
// =============================================================================

$query = explode("/", $_GET['q']);

// Extract page
$GINKGO_PAGE = $query[0];
if(!$GINKGO_PAGE)
	$GINKGO_PAGE = 'home';

// Extract user ID
$GINKGO_USER_ID	= $query[1];
if(!$GINKGO_USER_ID)
	$GINKGO_USER_ID	= generateID(20);


// Security patch: don't allow user IDs that aren't alphanumerical
// e.g. could use "a; echo b" as user ID; will create file "a" and write "b" to it
if(preg_match('/[^A-Za-z0-9_-]/', $GINKGO_USER_ID))
  exit;


// =============================================================================
// == Page-specific configuration ==============================================
// =============================================================================

// Step 1 (choose cells), Step 2 (job name, genome, etc), Step 3 (specify email)
if($GINKGO_PAGE == "dashboard")
  $MY_CELLS = getMyFiles($GINKGO_USER_ID);

// Step 4 (results)
if($GINKGO_PAGE == "results")
  $CURR_CELL = $query[2];


// =============================================================================
// == Session management =======================================================
// =============================================================================

$_SESSION["user_id"] = $GINKGO_USER_ID;

// Define user directories
$userDir = DIR_UPLOADS . '/' . $GINKGO_USER_ID;
$userUrl = URL_ROOT . '/uploads/' . $GINKGO_USER_ID;
$permalink = URL_ROOT . '?q=results/' . $GINKGO_USER_ID;

if(file_exists($descFile = $userDir . '/description.txt'))
	setcookie("ginkgo[$GINKGO_USER_ID]", file_get_contents($descFile), time()+36000000);


// =============================================================================
// == Template configuration ===================================================
// =============================================================================

// -- Panel for info -----------------------------------------------------------
if($GINKGO_PAGE == "results" || $GINKGO_PAGE == "analyze-subset")
{
	$configFile = $userDir . "/config";
	if(file_exists($configFile)) {
		$f = file($configFile);
		$config = array();
		foreach($f as $index => $val) {
			$values = explode("=", $val, 2);
			$config[$values[0]] = str_replace("'", "", trim($values[1]));
		}
	}	
}
$tmpBinMeth = split('_', $config['binMeth']);
$binInfo    = $tmpBinMeth[0] . ' bins of ' . $tmpBinMeth[1]/1000 . 'kb size <small><small>(' . $tmpBinMeth[3] . '/' . $tmpBinMeth[2] . 'bp reads)</small></small>';
$clusteringInfo = $config['clustMeth'] . ' linkage, ' . str_replace('euclidian','euclidean',$config['distMeth']) . ' distance';
$segInfo    = 'normalized read counts';
if($config['segMeth'] == '1')
	$segInfo = 'sample with lowest IOD';
if($config['segMeth'] == '2')
	$segInfo = 'uploaded reference sample';
$genome = $config['chosen_genome'];

$PANEL_INFO = <<<PANEL
	<!-- Panel: Summary of parameters used -->
	<div class="panel panel-primary">
		<div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-list-alt"></span> Analysis Parameters</h3></div>
		<div class="panel-body">
			<b>Genome:</b> $genome<br/>
			<b>Binning:</b> $binInfo<br/>
			<b>Segmentation:</b> using $segInfo<br/>
			<b>Clustering:</b> $clusteringInfo<br/>
		</div>
	</div>
PANEL;


// -- Panel for permalink ------------------------------------------------------
$PANEL_LATER = <<<PANEL
	<!-- Panel: Save for later -->
	<div class="panel panel-primary">
		<div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-time"></span> View analysis later</h3></div>
		<div class="panel-body">Access your results later at the following address:<br/><br/><textarea class="input-sm permalink">{$permalink}</textarea></div>
	</div>
PANEL;

// -- Panel to show user's last analysis, if any -------------------------------
if(file_exists(DIR_UPLOADS . '/' . $GINKGO_USER_ID . '/status.xml'))
{
	$PANEL_PREVIOUS = <<<PANEL
	<!-- Panel: View previous analysis results -->
	<div class="panel panel-primary">
		<div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-stats"></span> Previous analysis results</h3></div>
		<div class="panel-body">See your <a href="?q=results/$GINKGO_USER_ID">previous analysis results</a>.<br/><br/><strong>Note</strong>: Running another analysis will overwrite previous results.</div>
	</div>
PANEL;
}

// -- Panel for downloading tree -----------------------------------------------
$dloadFileSizes = array(
	'SegStats' => humanFileSize("{$userDir}/SegStats"),
	'SegBreaks' => humanFileSize("{$userDir}/SegBreaks"),
	'SegCopy' => humanFileSize("{$userDir}/SegCopy"),
	'SegNorm' => humanFileSize("{$userDir}/SegNorm"),
	'SegFixed' => humanFileSize("{$userDir}/SegFixed"),
	'CNV1' => humanFileSize("{$userDir}/CNV1"),
	'CNV2' => humanFileSize("{$userDir}/CNV2"),
);
$rnd = rand(1e6,2e6);
$PANEL_DOWNLOAD = <<<PANEL
	<!-- Panel: Download results -->
	<div id="results-download" class="panel panel-default" style="display:none;">
		<div class="panel-heading"><span class="glyphicon glyphicon-tree-deciduous"></span> Tree display</div>
		<!-- Table -->
		<table class="table" style="font-size:12.5px;">
			<tr class="active"><td><input type="radio" name="results-tree-view" onclick="javascript:drawTree('clust.xml');" checked>&nbsp;&nbsp;<strong>Normalized read counts</strong> (<a target="_blank" href="{$userUrl}/clust.newick">newick</a> | <a target="_blank" href="{$userUrl}/clust.xml">xml</a> | <a target="_blank" href="{$userUrl}/clust.pdf">pdf</a> | <a target="_blank" href="{$userUrl}/clust.jpeg">jpeg</a>)</td></tr>
			<tr class="active"><td><input type="radio" name="results-tree-view" onclick="javascript:drawTree('clust2.xml');">&nbsp;&nbsp;<strong>Copy-number</strong> (<a target="_blank" href="{$userUrl}/clust2.newick">newick</a> | <a target="_blank" href="{$userUrl}/clust2.xml">xml</a> | <a target="_blank" href="{$userUrl}/clust2.pdf">pdf</a> | <a target="_blank" href="{$userUrl}/clust2.jpeg">jpeg</a>)</td></tr>
			<tr class="active"><td><input type="radio" name="results-tree-view" onclick="javascript:drawTree('clust3.xml');">&nbsp;&nbsp;<strong>Correlations</strong> (<a target="_blank" href="{$userUrl}/clust3.newick">newick</a> | <a target="_blank" href="{$userUrl}/clust3.xml">xml</a> | <a target="_blank" href="{$userUrl}/clust3.pdf">pdf</a> | <a target="_blank" href="{$userUrl}/clust3.jpeg">jpeg</a>)</td></tr>
		</table>
	</div>

	<div id="results-download2" class="panel panel-default" style="display:none;">
		<div class="panel-heading"><span class="glyphicon glyphicon-file"></span> Download processed data</div>
		<!-- Table -->
		<table class="table" style="font-size:12.5px;">
			<tr class="active"><td><a target="_blank" href="{$userUrl}/SegStats?uniq={$rnd}"><strong>Statistics</strong></a>: Bin count statistics for each cell <strong>({$dloadFileSizes['SegStats']})</strong>.</span></td></tr>
			<tr class="active"><td><a target="_blank" href="{$userUrl}/SegBreaks?uniq={$rnd}"><strong>Breakpoints</strong></a>: Matrix that encodes whether a cell has a breakpoint at a bin position; rows = bins, columns = cells <strong>({$dloadFileSizes['SegBreaks']}).</strong></span></td></tr>
			<tr class="active"><td><a target="_blank" href="{$userUrl}/SegCopy?uniq={$rnd}"><strong>Copy Number</strong></a>: Copy number state for each cell at every bin position; rows = bins, columns = cells <strong>({$dloadFileSizes['SegCopy']}).</strong></span></td></tr>
			<tr class="active"><td><a target="_blank" href="{$userUrl}/SegNorm?uniq={$rnd}"><strong>Normalized Counts</strong></a>: Normalized bin counts for each cell at every bin position; rows = bins, columns = cells <strong>({$dloadFileSizes['SegNorm']}).</strong></span></td></tr>
			<tr class="active"><td><a target="_blank" href="{$userUrl}/SegFixed?uniq={$rnd}"><strong>Normalized and Segmented Counts</strong></a>: Normalized and segmented bin counts for each cell at every bin position; rows = bins, columns = cells <strong>({$dloadFileSizes['SegFixed']}).</strong></span></td></tr>
			<tr class="active"><td><a target="_blank" href="{$userUrl}/CNV1?uniq={$rnd}"><strong>Copy number events</strong></a>: List of regions with copy number events <strong>({$dloadFileSizes['CNV1']}).</strong></span></td></tr>
			<tr class="active"><td><a target="_blank" href="{$userUrl}/CNV2?uniq={$rnd}"><strong>Copy number regions</strong></a>: List of regions of amplifications (+1) and deletions (-1) <strong>({$dloadFileSizes['CNV2']}).</strong></span></td></tr>
		</table>
	</div>
PANEL;


// =============================================================================
// == Upload facs / binning file ===============================================
// =============================================================================

if($GINKGO_PAGE == 'admin-upload')
{
	// Create user directory if doesn't exist
	@mkdir($userDir);
	$result = "";

	// FACS file
	if(!empty($_FILES['params-facs-file']))
		// Upload facs file
		if(is_uploaded_file($_FILES['params-facs-file']['tmp_name']))
		{
			move_uploaded_file($_FILES['params-facs-file']['tmp_name'], $userDir . "/user-facs.txt");
			$result .= "facs";
		}

	// Segmentation file
	if(!empty($_FILES['params-segmentation-file']))
		// Upload binning file
		if(is_uploaded_file($_FILES['params-segmentation-file']['tmp_name']))
		{
			move_uploaded_file($_FILES['params-segmentation-file']['tmp_name'], $userDir . "/user-segmentation.txt");
			$result .= "segmentation";
		}

	die($result);
}


// =============================================================================
// == Launch analysis ==========================================================
// =============================================================================

if(isset($_POST['analyze']))
{
	// Create user directory if doesn't exist
	@mkdir($userDir);

	// Sanitize user input (see bootstrap.php)
	array_walk_recursive($_POST, 'sanitize');
	$user = $GINKGO_USER_ID;
	sanitize($user);

	// Defaults for new analysis
	$init = 1;
	$process = 1;
	$fix = 0;

	// Did the user change the analysis parameters from the last time?
	// Load previous configuration
	$configFile = $userDir . "/config";
	if(file_exists($configFile))
	{
		//
		$f = file($configFile);
		$oldParams = array();
		foreach($f as $index => $val)
		{
			$values = explode("=", $val, 2);
			$oldParams[$values[0]] = str_replace("", "", trim($values[1]));
		}

		// Defaults for old analysis (do nothing)
		$init = 0;
		$process = 0;
		$fix = 0;

		// Do we need to remap? This sets init to 1 if yes, 0 if not
		$newBinParams	= ($oldParams['binMeth']	!= $_POST['binMeth']) || 
				  			($oldParams['binList']	!= $_POST['binList']) ||
				  			($oldParams['rmbadbins']!= $_POST['rmbadbins']) ||
				  			($oldParams['rmpseudoautosomal']!= $_POST['rmpseudoautosomal']);
		$newFacs		= ($oldParams['facs']		!= $_POST['facs']);
		$newSegParams	= ($oldParams['segMeth']	!= $_POST['segMeth']) || ($_POST['segMethCustom'] != '');
		$newClustering	= ($oldParams['clustMeth']	!= $_POST['clustMeth']);
		$newDistance	= ($oldParams['distMeth']	!= $_POST['distMeth']);
		$newColor		= ($oldParams['color']		!= $_POST['color']);
		$sexChange		= ($oldParams['sex']		!= $_POST['sex']);

		// Different cells to analyze than last time?
		$cells = '';
		foreach($_POST['cells'] as $cell)
			$cells .= str_replace("'", "", $cell) . "\n";
		if($cells != file_get_contents($userDir . '/list'))
			$newBinParams = 1;

		// -- Set new variable values
		// Redo the mapping for all files
		if($newBinParams)
			$init = 1;
		// Redo binning stuff
		if($newBinParams || $newSegParams || $newColor || $newFacs)
			$process = 1;
		// Redraw dendrogams
		// Only need to run fix when not running process.R
		if(!$process && !$init && ($newClustering || $newDistance || $sexChange))
			$fix = 1;
		// When redirect, if status file isn't changed quickly enough, will show up as 100% completed
		// However, only delete status file if we need to redo at least 1 part of the analysis
		if($init || $process || $fix) {
			$statusFile = DIR_UPLOADS . '/' . $GINKGO_USER_ID . '/status.xml';
			unlink($statusFile);
		}
	}

	// Make sure have enough cells for analysis
	if(count($_POST['cells']) < $GINKGO_MIN_NB_CELLS)
		die("Please select at least " . $GINKGO_MIN_NB_CELLS . " cells for your analysis.");

	// Create list-of-cells-to-analyze file	
	$cells = '';
	foreach($_POST['cells'] as $cell)
		$cells .= str_replace("'", "", $cell) . "\n";
	file_put_contents($userDir . '/list', $cells);

	// Create config file
	$config = '#!/bin/bash' . "\n";
	$config.= 'user=' . $user . "\n";
	$config.= 'email=' . $_POST['email'] . "\n";
	$config.= 'permalink=\'' . URL_ROOT . '/?q=results/' . str_replace("'", "", $user) . "'\n";
	//
	$config.= 'segMeth=' . $_POST['segMeth'] . "\n";
	$config.= 'binMeth=' . $_POST['binMeth'] . "\n";
	$config.= 'clustMeth=' . $_POST['clustMeth'] . "\n";
	$config.= 'distMeth=' . $_POST['distMeth'] . "\n";
	//
	$config.= 'b=' . $_POST['b'] . "\n";
	$config.= 'binList=' . $_POST['binList'] . "\n";
	$config.= 'f=' . $_POST['f'] . "\n";
	$config.= 'facs=' . $_POST['facs'] . "\n";
	$config.= 'q=' . $_POST['g'] . "\n";
	$config.= 'chosen_genome=' . $_POST['chosen_genome'] . "\n";
	//
	$config.= 'init=' . $init . "\n";
	$config.= 'process=' . $process . "\n";
	$config.= 'fix=' . $fix . "\n";
	//
	$config.= 'ref=' . $_POST['segMethCustom'] . "\n";
	//
	$config.= 'color=' . $_POST['color'] . "\n";
	$config.= 'sex=' . $_POST['sex'] . "\n";
	$config.= 'rmbadbins=' . $_POST['rmbadbins'] . "\n";
	$config.= 'rmpseudoautosomal=' . $_POST['rmpseudoautosomal'] . "\n";
	//
	file_put_contents($userDir . '/config', $config);

	// Start analysis
	$cmd = "./scripts/analyze $GINKGO_USER_ID >> $userDir/ginkgo.out 2>&1  &";
	session_regenerate_id(TRUE);	
	$handle = popen($cmd, 'r');
	pclose($handle);

	// Save to cookie and file
	setcookie("ginkgo[$GINKGO_USER_ID]", $_POST['job_name'], time()+36000000);
	file_put_contents($userDir . '/description.txt', $_POST['job_name']);

	// Return OK status
	echo "OK";
	exit;
}


// =============================================================================
// == Launch analysis **on subset of cells** ===================================
// =============================================================================

if(isset($_POST['analyze-subset']))
{
	// Job settings
	$selectedCells = $_POST['selectedCells'];
	$analysisType  = $_POST['analysisType'];
	//
	$analysisID    = generateID(10);

	// Save job settings
	$configTxt = $analysisType . "\n";
	foreach($selectedCells as $cell)
		$configTxt .= $cell . "\n";
	file_put_contents($userDir . '/' . $analysisID . '.config', $configTxt);

	// Start analysis
	$PAR = 'original';
	if($config['rmpseudoautosomal'] == '1')
		$PAR = 'pseudoautosomal';

	$cmd = "./scripts/analyze-subset.R $GINKGO_USER_ID $analysisID $genome {$config['binMeth']} $PAR >> $userDir/ginkgo-" . $analysisID . ".out 2>&1  &";
	session_regenerate_id(TRUE);	
	$handle = popen($cmd, 'r');
	pclose($handle);

	echo $analysisID;
	exit;
}

if($GINKGO_PAGE == "results-subset")
{
	if(file_exists('uploads/' . $GINKGO_USER_ID . '/' . $query[2] . '.done'))
		echo '<img src="./uploads/' . $GINKGO_USER_ID . '/' . $query[2] . '.jpeg?uniq=' . rand(1e6,2e6) . '">';
	else
		echo 'Generating figure...<meta http-equiv="refresh" content="1">';
	exit;
}


// =============================================================================
// == Prepare file for UCSC custom track =======================================
// =============================================================================

if(isset($_POST['ucsc']))
{
	// Job settings
	$cell  = $_POST['cell'];
	$range = $_POST['range'];
	//
	$analysisID    = generateID(10);

	// Save job settings
	$configTxt = "browser position {$range}\n";

	$CMD  = <<<CM
awk -v CELL='"{$cell}"' 'BEGIN{ print "track name=Amplifications description="CELL" color=0,0,255,"; }{  if(NR==1){ for(i=1;i<=NF;i++){if(\$i==CELL)cellID=i;} }else{ if(\$cellID>2)print \$1"\t"\$2"\t"\$3;  }  }' ./uploads/{$GINKGO_USER_ID}/SegCopy;
awk -v CELL='"{$cell}"' 'BEGIN{ print "track name=Deletions description="CELL" color=255,0,0,"; }{  if(NR==1){ for(i=1;i<=NF;i++){if(\$i==CELL)cellID=i;} }else{ if(\$cellID<2)print \$1"\t"\$2"\t"\$3;  }  }' ./uploads/{$GINKGO_USER_ID}/SegCopy;
CM;

	file_put_contents($userDir . '/' . $analysisID . '.ucsc', "browser position {$range}\n".shell_exec($CMD));
	echo $analysisID;
	exit;
}


// =============================================================================
// == If analysis under way, redirect to status page ===========================
// =============================================================================

// Load status.xml if exists and check if analysis under way
if($GINKGO_PAGE == "" || $GINKGO_PAGE == "home" || $GINKGO_PAGE == "dashboard") {
	$statusFile = DIR_UPLOADS . '/' . $GINKGO_USER_ID . '/status.xml';
	if(file_exists($statusFile)) {
		$status = simplexml_load_file($statusFile);

		if($status->step < 3 && $status->percentdone < 100) {
			header("Location: ?q=results/" . $GINKGO_USER_ID);
			exit;
		}
	}
}

// Load current settings
$configFile = $userDir . "/config";
if(file_exists($configFile)) {
	$f = file($configFile);
	$config = array();
	foreach($f as $index => $val) {
		$values = explode("=", $val, 2);
		$config[$values[0]] = str_replace("'", "", trim($values[1]));
	}
}

//
if($GINKGO_PAGE == 'admin-search')
{
	$file = DIR_ROOT . '/genomes/' . $config['chosen_genome'] . '/' . ($config['rmpseudoautosomal'] == '1' ? 'pseudoautosomal' : 'original') . '/genes_' . $config['binMeth'];
	$gene = escapeshellarg($_GET['gene']);
	$bin  = escapeshellarg($_GET['binNumber']);

	//
	if(isset($_GET['gene']))
		die(`grep -i -w $gene $file | head -n 1 | cut -f3`);
	//
	if(isset($_GET['binNumber']))
		die(`awk '{if($3==$bin) print $2}' $file | sort | uniq`);
}


// =============================================================================
// == HTML template ============================================================
// =============================================================================

?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="">
		<meta name="author" content="">
		<title>Ginkgo</title>

		<!-- Bootstrap core CSS -->
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-theme.min.css">

		<!-- Custom styles -->
		<style>
			html, body  { height:100%; }
			td          { vertical-align:middle !important; }
			code input  { border:none; color:#c7254e; background-color:#f9f2f4; width:100%; }
			code textarea  { border:none; color:#c7254e; background-color:#f9f2f4; width:100%; resize:none; }
			svgCanvas   { fill:none; pointer-events:all; }
			.jumbotron  { padding:50px 30px 15px 30px; }
			.glyphicon  { vertical-align:top; }
			.badge      { vertical-align:top; margin-top:5px; }
			.permalink  { border:1px solid #DDD; width:100%; color:#666; background:transparent; font-family:"courier"; resize:none; height:50px; }
			.sorting_asc { background: url('includes/datatables/images/sort_asc.png') no-repeat center right; }
			.sorting_desc { background: url('includes/datatables/images/sort_desc.png') no-repeat center right; }
			.sorting { background: url('includes/datatables/images/sort_both.png') no-repeat center right; }
			.sorting_asc_disabled { background: url('includes/datatables/images/sort_asc_disabled.png') no-repeat center right; }
			.sorting_desc_disabled { background: url('includes/datatables/images/sort_desc_disabled.png') no-repeat center right; }

			/* callout */
			.bs-callout { margin:20px 0; padding:15px 30px 15px 15px; border-left:5px solid #eee; }
			.bs-callout h4 { margin-top:0; }
			.bs-callout p:last-child { margin-bottom:0; }
			.bs-callout code, .bs-callout .highlight { background-color:#fff; }
			/* */ 
			.bs-callout-danger { background-color:#fcf2f2; border-color:#dFb5b4; }
			.bs-callout-warning { background-color:#fefbed; border-color:#f1e7bc; }
			.bs-callout-info { background-color:#f0f7fd; border-color:#d0e3f0; } 
			/* */
			#results-QA-table tr td:first-child { text-align: center; }
			#results-QA-table tr td:first-child:before { content: "\f096"; font-family: FontAwesome; }
			#results-QA-table tr.selected td:first-child:before { content: "\f046"; }
			/* */
			.codesample { background-color:#f9f2f4; font-family:Monaco, monospace, serif; font-size:90%; color:#C7254E; padding:10px; }
		</style>

		<!-- Tinycon styles/javascript -->
		<script type="text/javascript" src="includes/tinycon/tinycon.min.js"></script>
		<link rel="icon" href="includes/tinycon/ginkgo.ico" />
		<script>
		  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		  ga('create', 'UA-31249083-2', 'auto');
		  ga('send', 'pageview');
		</script>
	</head>
	<body>

		<!-- Navigation bar -->
		<div class="navbar navbar-inverse navbar-fixed-top">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>

					<ul class="nav navbar-nav">
						<li>
							<a class="navbar-brand dropdown-toggle" data-toggle="dropdown" href="#"><span class="glyphicon glyphicon-tree-deciduous"></span> Ginkgo <span class="caret" style="border-top-color:#ccc !important; border-bottom-color:#ccc !important;"></span></a>
							<ul class="dropdown-menu" role="menu">
								<li><a href="?q=">Home</a></li>
								<li><a href="https://github.com/robertaboukhalil/ginkgo">Github</a></li>
								<li class="divider"></li>
								<li><a href="?q=results/_t10breast_navin"><small><small style="color:#bdc3c7">DOP-PCR</small></small> Polygenomic breast tumor &mdash; <i>Navin et al, 2011</i></a></li>
								<li><a href="?q=results/_t16breast_liver_met_navin"><small><small style="color:#bdc3c7">DOP-PCR</small></small> Breast cancer + liver metastasis &mdash; <i>Navin et al, 2011</i></a></li>
								<li><a href="?q=results/_neuron_mcconnell"><small><small style="color:#bdc3c7">DOP-PCR</small></small> Neurons &mdash; <i>McConnell et al, 2013</i></a></li>
								<li><a href="?q=results/_ctc_ni"><small><small style="color:#bdc3c7">MALBAC&nbsp;</small></small> Circulating lung tumor cells &mdash; <i>Ni et al, 2013</i></a></li>
								<li><a href="?q=results/_oocyte_hou"><small><small style="color:#bdc3c7">MALBAC&nbsp;</small></small> Oocytes &mdash; <i>Hou et al, 2013</i></a></li>
								<li><a href="?q=results/_sperm_lu"><small><small style="color:#bdc3c7">MALBAC&nbsp;</small></small> Sperm &mdash; <i>Lu et al, 2012</i></a></li>
								<li><a href="?q=results/_sperm_kirkness"><small><small style="color:#bdc3c7">MDA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</small></small> Sperm &mdash; <i>Kirkness et al, 2013</i></a></li>
								<li><a href="?q=results/_sperm_wang"><small><small style="color:#bdc3c7">MDA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</small></small> Sperm &mdash; <i>Wang et al, 2012</i></a></li>
								<li><a href="?q=results/_neuron_evrony"><small><small style="color:#bdc3c7">MDA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</small></small> Neurons &mdash; <i>Evrony et al, 2012</i></a></li>

								<?php if(count($_COOKIE['ginkgo']) > 0): ?>
									<li class="divider"></li>

									<?php foreach($_COOKIE['ginkgo'] as $id => $name): ?>
										<?php if($id != "sample"): ?>
										<li><a href="?q=dashboard/<?php echo $id;?>"><?php echo str_replace("'", "", $name);?></a></li>
										<?php endif; ?>
									<?php endforeach; ?>
								<?php endif; ?>
							</ul>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<!-- Welcome message -->
		<div class="jumbotron">
			<div class="container">
				<h1><a style="text-decoration:none; color:#000" href="?q=/<?php echo $GINKGO_USER_ID; ?>">Ginkgo</a> <small><?php echo str_replace("'", "", @file_get_contents($userDir.'/description.txt')); ?></small></h1>
				<div id="status" style="margin-top:20px;">
					<?php if($GINKGO_PAGE == 'home'): ?>
					A web tool for analyzing single-cell sequencing data.
					<br>

					<div class="btn-group">
					  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">Sample analyses <span class="caret"></span></button>
					  <ul class="dropdown-menu" role="menu">
						<li><a href="?q=results/_t10breast_navin"><small><small style="color:#bdc3c7">DOP-PCR</small></small> Polygenomic breast tumor &mdash; <i>Navin et al, 2011</i></a></li>
						<li><a href="?q=results/_t16breast_liver_met_navin"><small><small style="color:#bdc3c7">DOP-PCR</small></small> Breast cancer + liver metastasis &mdash; <i>Navin et al, 2011</i></a></li>
						<li><a href="?q=results/_neuron_mcconnell"><small><small style="color:#bdc3c7">DOP-PCR</small></small> Neurons &mdash; <i>McConnell et al, 2013</i></a></li>
						<li><a href="?q=results/_ctc_ni"><small><small style="color:#bdc3c7">MALBAC&nbsp;</small></small> Circulating lung tumor cells &mdash; <i>Ni et al, 2013</i></a></li>
						<li><a href="?q=results/_oocyte_hou"><small><small style="color:#bdc3c7">MALBAC&nbsp;</small></small> Oocytes &mdash; <i>Hou et al, 2013</i></a></li>
						<li><a href="?q=results/_sperm_lu"><small><small style="color:#bdc3c7">MALBAC&nbsp;</small></small> Sperm &mdash; <i>Lu et al, 2012</i></a></li>
						<li><a href="?q=results/_bonemarrow_hou"><small><small style="color:#bdc3c7">MDA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</small></small> Bone marrow &mdash; <i>Hou et al, 2012</i></a></li>
						<li><a href="?q=results/_kidney_xu"><small><small style="color:#bdc3c7">MDA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</small></small> Kidney &mdash; <i>Xu et al, 2012</i></a></li>
						<li><a href="?q=results/_neuron_evrony"><small><small style="color:#bdc3c7">MDA&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</small></small> Neurons &mdash; <i>Evrony et al, 2012</i></a></li>
					  </ul>
					</div>

					<?php if(count($_COOKIE['ginkgo']) > 0): ?>
					<div class="btn-group">
					  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">Load previous analysis <span class="caret"></span></button>
					  <ul class="dropdown-menu" role="menu">
							<li class="divider"></li>
							<?php foreach($_COOKIE['ginkgo'] as $id => $name): ?>
								<?php if($id != "sample"): ?>
								<li><a href="?q=dashboard/<?php echo $id;?>"><?php echo str_replace("'", "", $name);?></a></li>
								<?php endif; ?>
							<?php endforeach; ?>
					  </ul>
					</div>
					<?php endif; ?>

					<?php elseif($GINKGO_PAGE == 'dashboard'): ?>
					<div class="status-box">Your files are uploaded. Now let's do some analysis:</div>
					<?php elseif($GINKGO_PAGE == 'results'): ?>
					<div class="status-box" id="results-status">
						<span id="results-status-text">Updating status...</span><br />
						<div class="progress progress-striped active"><div id="results-progress" class="progress-bar" role="progressbar" style="width: 0%"></div></div>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Main container -->
		<div class="container">
			<?php // ================================================================ ?>
			<?php // == Home: Upload files ========================================== ?>
			<?php // ================================================================ ?>
			<?php if($GINKGO_PAGE == 'home'): ?>
			<!-- Upload files -->
			<?php if($GINKGO_USER_ID != 'sample' && $GINKGO_USER_ID != 'sample2'): ?>
			<div class="row" style="height:100%;">
				<div class="col-lg-8">
					<h3 style="margin-top:-5px;"><span class="badge">STEP 0</span> Upload your .bed files <small><strong>(We accept *.bed and *.bed.gz, max 1GB/file, min 3 cells)</strong></small></h3>
					<iframe id="upload-iframe" style="width:100%; height:100%; border:0;" src="includes/fileupload/?user_id=<?php echo $GINKGO_USER_ID; ?>"></iframe>
					<p>
						<div style="float:right">
							<a id="fileupload-next-step" class="btn btn-lg btn-primary" href="?q=dashboard/<?php echo $GINKGO_USER_ID; ?>">Next step <span class="glyphicon glyphicon-chevron-right"></span></a>
						</div>
					</p>
				</div>
				<div class="col-lg-4">

					<?php echo $PANEL_PREVIOUS; ?>
					<?php echo $PANEL_LATER; ?>

					<!-- Panel: Help -->
					<div class="panel panel-primary">
						<div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-question-sign"></span> Help</h3></div>
						<div class="panel-body">

							<div class="panel-group" id="help-bedfmt">
								<div class="panel panel-default">
									<div class="panel-heading"><h4 class="panel-title"><a class="accordion-toggle" data-toggle="collapse" data-parent="#help-bedfmt" href="#help-bedfmt-content">Sample .bed file</a></h4></div>
									<div id="help-bedfmt-content" class="panel-collapse collapse in"><div class="panel-body">
										<table class="table">
											<thead><tr><th>chrom</th><th>chromStart</th><th>chromEnd</th></tr></thead>
											<tbody><tr><td>chr1</td><td>555485</td><td>555533</td></tr><tr><td>chr1</td><td>676584</td><td>676632</td></tr><tr><td>chr1</td><td>745136</td><td>745184</td></tr></tbody>
										</table>
									</div></div>
								</div>
							</div>

							<br/>
							<div class="panel-group" id="help-makebed">
								<div class="panel panel-default">
									<div class="panel-heading"><h4 class="panel-title"><a class="accordion-toggle" data-toggle="collapse" data-parent="#help-makebed" href="#help-makebed-content">How to make .bed files</a></h4></div>
									<div id="help-makebed-content" class="panel-collapse collapse in">
										<div class="panel-body">
											<p>If your mapped reads are saved in the file <strong>reads.bam</strong>:</p>
											<div class="codesample">bamToBed -i reads.bam > reads.bed</div>
										</div>
									</div>
								</div>
							</div>

							<br/>
							<div class="panel-group" id="help-details">
								<div class="panel panel-default">
									<div class="panel-heading"><h4 class="panel-title"><a class="accordion-toggle" data-toggle="collapse" data-parent="#help-details" href="#help-details-content">Detailed instructions</a></h4></div>
									<div id="help-details-content" class="panel-collapse collapse out">
										<div class="panel-body">
											<p>
												<strong>Step 1</strong><br/>
												If you do not have a reference genome index (e.g. hg19), download it from the <a href="http://bowtie-bio.sourceforge.net/bowtie2/index.shtml" target="_blank">Bowtie2 website</a> (menu on right, under <i>Indexes</i>).
												Then, map your reads (in <strong>reads.fastq</strong>) to the genome, and output the results to <strong>reads.sam</strong>:</p>
											<div class="codesample">bowtie2 -x hg19 -U reads.fastq -S reads.sam</div>
											<br/>
											<p>If you have paired-end reads, use the following command instead:</p>
											<div class="codesample">bowtie2 -x hg19 -1 reads_r1.fastq -2 reads_r2.fastq -S reads.sam</div>
											<br/>
											<p>
												<strong>Step 2</strong><br/>
												Convert <strong>reads.sam</strong> to <strong>reads.bam</strong>:</p>
											<div class="codesample">samtools view -Sb reads.sam -q 20 -o reads.bam</div>
											<br/><p>
											<strong>Step 3</strong><br/>
											Convert <strong>reads.bam</strong> to <strong>reads.bed</strong>:</p>
											<div class="codesample">bamToBed -i reads.bam > reads.bed</div>
										</div>
									</div>
								</div>
							</div>
						</div>
			<?php else: ?>
			<script>
			window.location = '?q=results/<?php echo $GINKGO_USER_ID; ?>';
			</script>
			<?php endif; ?>

			<?php // ================================================================ ?>
			<?php // == Dashboard: Analysis settings ================================ ?>
			<?php // ================================================================ ?>
			<?php elseif($GINKGO_PAGE == 'dashboard'): ?>
			<!-- Dashboard -->
			<div class="row">
				<div id="dashboard" class="col-lg-8">
				<form id="form-dashboard">
					<!-- Choose cells of interest -->
					<h3 style="margin-top:-5px;"><span class="badge">STEP 1</span> Choose cells for analysis</h3>

					<?php if($GINKGO_USER_ID != 'sample' && $GINKGO_USER_ID != 'sample2'): ?>
					<button id="dashboard-toggle-cells" class="btn btn-info" style="margin:20px;">Select all cells</button>
					<br/>
					<div id="params-cells" style="max-height:200px; overflow:auto">
						<?php $previouslySelected = file(DIR_UPLOADS . '/' . $GINKGO_USER_ID . '/list', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); ?>
						<?php $selected = array(); ?>
						<?php foreach($MY_CELLS as $currCell): ?>
						<?php
								// Was the current cell previously selected in an analysis?
								$selected[$currCell] = "";
								if(in_array($currCell, $previouslySelected))
									$selected[$currCell] = " checked";
						?>
				    	<label><div class="input-group" style="margin:20px;"><span class="input-group-addon"><input type="checkbox" name="dashboard_cells[]" value="<?php echo $currCell; ?>"<?php echo $selected[$currCell];?>></span><span class="form-control"><?php echo $currCell; ?></span></div></label>
						<?php endforeach; ?>
					</div>

					<!-- Which genome? -->
					<br/><br/><h3 style="margin-top:-5px;"><span class="badge">STEP 2</span> Set analysis options <small></small></h3>
					<div id="params-genome" style="margin:20px;">
						<table class="table table-striped">
							<tr>
								<td width="20%">Job name:</td>
								<td>
									<input id="param-job-name" class="form-control" type="text" placeholder="Single-cells from tissue X" value="<?php echo str_replace("'", "", file_get_contents($userDir . '/description.txt')); ?>">
								</td>
							</tr>

							<tr>
								<td width="20%">Genome:</td>
								<td>
									<select id="param-genome" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
										<optgroup label="Latest genomes">
											<!--<option value="hg20">Human (hg20)</option>-->
											<?php $selected = array(); $selected[$config['chosen_genome']] = ' selected'; ?>
											<option value="hg19"<?php echo $selected['hg19']; ?>>Human (hg19)</option>
											<option value="panTro4"<?php echo $selected['panTro4']; ?>>Chimpanzee (panTro4)</option>
											<option value="mm10"<?php echo $selected['mm10']; ?>>Mus musculus (mm10)</option>
											<option value="rn5"<?php echo $selected['rn5']; ?>>R. norvegicus (rn5)</option>
											<option value="dm3"<?php echo $selected['dm3']; ?>>D. Melanogaster (dm3)</option>
										</optgroup>
										<optgroup label="Older genomes">
											<option value="hg18"<?php echo $selected['hg18']; ?>>Human (hg18)</option>
											<option value="panTro3"<?php echo $selected['panTro3']; ?>>Chimpanzee (panTro3)</option>
										</optgroup>
									</select>
								</td>
							</tr>
						</table>
					</div>

					<!-- Get informed by email when done? -->
					<br/><br/><h3 style="margin-top:-5px;"><span class="badge">STEP 3</span> E-mail notification <small></small></h3>
					<div id="params-email" style="margin:20px;">
						<p>If you want to be notified once the analysis is done, enter your e-mail here:<br/></p>
						<div class="input-group">
							<?php if($config['email'] != '') $email = $config['email']; ?>
							<span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
							<input id="email" class="form-control" type="text" placeholder="my@email.com" value="<?php echo $email; ?>">
						</div>
					</div>
					<br/><br/>

					<!-- Set parameters -->
					<h3 style="margin-top:-5px;"><span class="badge">OPTIONAL</span> <a href="#parameters" onClick="javascript:$('#params-table').toggle();">Advanced parameters</a></h3>
					<table class="table" id="params-table">
						<tbody>
							<tr class="active"><td colspan="2"><strong>Sample Parameters</strong></td></tr>
							<tr>
								<td>CNV Profile Color Scheme</td>
								<?php $selected = array(); $selected[$config['color']] = ' selected'; ?>
								<td>
									Use <select id="param-color-scheme" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="3"<?php echo $selected['3']; ?>>dark blue / red</option>
									<option value="1"<?php echo $selected['1']; ?>>light blue / orange</option>
									<option value="2"<?php echo $selected['2']; ?>>magenta / gold</option>
									</select> color scheme.
								</td>
							</tr>
							<tr>
								<td>General Binning Options</td>
								<?php
									if(empty($config))
										$config['binMeth'] = 'variable_500000_101_bowtie';
									$binMeth = split('_', $config['binMeth']);
								?>
								<td>
									<?php $selected = array(); $selected[$binMeth[0]] = ' selected'; ?>
									Use a <select id="param-bins-type" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="variable_"<?php echo $selected['variable']; ?>>variable</option>
									<option value="fixed_"<?php echo $selected['fixed']; ?>>fixed</option>
									</select> bin size of 

									<?php $selected = array(); $selected[$binMeth[1]] = ' selected'; ?>
									<select id="param-bins-value" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="500000_"<?php echo $selected['500000']; ?>>500kb</option>
									<option value="250000_"<?php echo $selected['250000']; ?>>250kb</option>
									<option value="175000_"<?php echo $selected['175000']; ?>>175kb</option>
									<option value="100000_"<?php echo $selected['100000']; ?>>100kb</option>
									<option value="50000_"<?php echo $selected['50000']; ?>>50kb</option>
									<!-- <option value="40000_"<?php echo $selected['40000']; ?>>40kb</option> -->
									<option value="25000_"<?php echo $selected['25000']; ?>>25kb</option>
									<option value="10000_"<?php echo $selected['10000']; ?>>10kb</option>
									<!-- <option value="5000_"<?php echo $selected['5000']; ?>>5kb</option> -->
									</select> size.
								</td>
							</tr>
							<tr id="param-binning-sim-options">
								<td>Binning Simulation Options</td>
								<td>
									<?php $selected = array(); $selected[$binMeth[2]] = ' selected'; ?>
									Bins based on simulations of <select id="param-bins-sim-rlen" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="150_"<?php echo $selected['150']; ?>>150</option>
									<option value="101_"<?php echo $selected['101']; ?>>101</option>
									<option value="76_"<?php echo $selected['76']; ?>>76</option>
									<option value="48_"<?php echo $selected['48']; ?>>48</option>
									</select> bp reads, mapped with

									<?php $selected = array(); $selected[$binMeth[3]] = ' selected'; ?>
									<select id="param-bins-sim-mapper" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="bowtie"<?php echo $selected['bowtie']; ?>>bowtie</option>
									<option value="bwa"<?php echo $selected['bwa']; ?>>bwa</option>
									</select>.
								</td>
							</tr>
							<tr>
								<td>Segmentation</td>
								<?php $selected = array(); $selected[$config['segMeth']] = ' selected'; ?>
								<td>Use <select id="param-segmentation" class="input-medium" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
								<option value="0"<?php echo $selected[0]; ?>>Independent (normalized read counts)</option>
								<option value="1"<?php echo $selected[1]; ?>>Global (sample with lowest IOD)</option>
								<option value="2"<?php echo $selected[2]; ?>>Custom (using uploaded reference sample)</option>
								</select> method to segment.</td>
							</tr>
							<tr style="display:none" id="param-segmentation-custom">
								<td>Custom segmentation</td>
								<td style="height:45px;">
									<div class="fileupload fileupload-new" data-provides="fileupload">
										<div class="input-append">
											<div class="uneditable-input span3">
												<i class="glyphicon glyphicon-upload"></i>
												<span class="fileupload-preview"></span>
											</div>

											<span class="btn btn-file">
												<span class="fileupload-new btn btn-success">Select .bed file</span>
												<span class="fileupload-exists btn btn-success">Change</span>
												<input type="file" name="params-segmentation-file" />
											</span>

											<a href="#" class="btn btn-danger fileupload-exists" data-dismiss="fileupload">Remove</a>
										</div>
									</div>
								</td>
							</tr>

							<tr id="param-segmentation-maskbadbins" style="display:none">
								<td>Mask bad bins <i><small>(experimental)</small></i><br/><i><small>Removes bins with consistent read pileups from the analysis (e.g. at chromosome boundaries)</small></i></td>
								<td>
									<?php /* Uncomment this later */ $checked = " "; if($config['rmbadbins'] == '0') $checked=""; ?>
									<input type="checkbox" id="dashboard-rmbadbins"<?php echo $checked;?>>
								</td>
							</tr>

							<tr id="param-segmentation-pseudoautosomal" style="display:none">
								<td>Mask Y-chr pseudoautosomal regions <i><small>(experimental)</small></i><br/><i><small>Description</small></i></td>
								<td>
									<?php /* Uncomment this later */ $checked = " "; if($config['rmpseudoautosomal'] == '0') $checked=""; ?>
									<input type="checkbox" id="dashboard-rmpseudoautosomal"<?php echo $checked;?>>
								</td>
							</tr>

							<tr class="active"><td colspan="2"><strong>Clustering Parameters</strong></td></tr>
							<tr>
								<td>Clustering</td>
								<td>
									<?php $selected = array(); $selected[$config['clustMeth']] = ' selected'; ?>
									Use <select id="param-clustering" class="input-small" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="ward"<?php echo $selected['ward']; ?>>ward linkage</option>
									<option value="single"<?php echo $selected['single']; ?>>single linkage</option>
									<option value="complete"<?php echo $selected['complete']; ?>>complete linkage</option>
									<option value="average"<?php echo $selected['average']; ?>>average linkage</option>
									<option value="NJ"<?php echo $selected['NJ']; ?>>neighbor-joining</option>
									</select>.
								</td>
							</tr>
							<tr>
								<td>Distance metric</td>
								<td>
									<?php $selected = array(); $selected[$config['distMeth']] = ' selected'; ?>
									Use <select id="param-distance" class="input-small" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="euclidian"<?php echo $selected['euclidian']; ?>>Euclidean</option>
									<option value="maximum"<?php echo $selected['maximum']; ?>>maximum</option>
									<option value="manhattan"<?php echo $selected['manhattan']; ?>>Manhattan</option>
									<option value="canberra"<?php echo $selected['canberra']; ?>>Canberra</option>
									<option value="binary"<?php echo $selected['binary']; ?>>binary</option>
									<option value="minkowski"<?php echo $selected['minkowski']; ?>>Minkowski</option>
									</select> distance.
								</td>
							</tr>
							<tr>
								<td>Include sex chromosomes?<br/><i><small>Not recommended for mixed-gender samples</small></i></td>
								<td>
									<?php $checked = " checked"; if($config['sex'] == '0') $checked=""; ?>
									<input type="checkbox" id="dashboard-include-sex"<?php echo $checked;?>>
								</td>
							</tr>

							<tr class="active"><td colspan="2"><strong>FACS File</strong></td></tr>
							<tr>
								<td>FACS file:</td>
								<td>
									<div class="fileupload fileupload-new" data-provides="fileupload">
										<div class="input-append">
											<div class="uneditable-input span3">
												<i class="glyphicon glyphicon-upload"></i>
												<span class="fileupload-preview"></span>
											</div>
											<span class="btn btn-file">
												<span class="fileupload-new btn btn-success">Select .txt file</span>
												<span class="fileupload-exists btn btn-success">Change</span>
												<input type="file" name="params-facs-file" />
											</span>
											<a href="#" class="btn btn-danger fileupload-exists" data-dismiss="fileupload">Remove</a>
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				<?php else: ?>
				<script>
				window.location = '?q=results/<?php echo $GINKGO_USER_ID; ?>';
				</script>
				<?php endif; ?>
					<br/>
					<a name="parameters"></a>

					<!-- Buttons: back or next -->
					<?php
						$btnCaption = 'Start Analysis';
						if(file_exists($configFile))
							$btnCaption = 'View Results';
					?>
					<hr><br/>
					<div style="float:left"><a class="btn btn-lg btn-primary" href="?q=/<?php echo $GINKGO_USER_ID; ?>"><span class="glyphicon glyphicon-chevron-left"></span> Manage Files </a></div>
					<div style="float:right"><a id="analyze" class="btn btn-lg btn-primary" href="javascript:void(0);"><?php echo $btnCaption; ?> <span class="glyphicon glyphicon-chevron-right"></span></a></div><br/><br/><br/>
				</form>
				</div>
				<div class="col-lg-4">
					<?php echo $PANEL_PREVIOUS; ?>
					<?php echo $PANEL_LATER; ?>
				</div>
			</div>


			<?php // ================================================================ ?>
			<?php // == Dashboard: Results/Main ===================================== ?>
			<?php // ================================================================ ?>
			<?php elseif($GINKGO_PAGE == 'results' && $CURR_CELL == ""): ?>
			<!-- Results -->
			<div class="row">
				<div id="results" class="col-lg-8">
					<h3 style="margin-top:-5px;"><span class="badge">STEP 3</span> View results</h3>
					<div id="results-tree" class="panel panel-info">
						<div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-tree-deciduous"></span> Tree</h3></div>
						<div class="panel-body" style="border:0px solid green; ">
							<div id="svgCanvas" class="row-fluid" style="border:0px solid red; ">
								Loading tree... <img src="loading.gif" />
							</div>
						</div>
					</div>
					<br/>

					<!-- Panel: Summary -->
					<div id="results-summary" class="panel panel-default">
						<div class="panel-heading"><span class="glyphicon glyphicon-certificate"></span> Summary</div>
						<div style="height:350px; overflow:auto;">
							<table class="table" id="results-QA-table" style="display:none;"></table>
						</div>
						<table class="table">
							<tr>
								<td style="width:75%; vertical-align:middle">
									With selected cells, plot:
									<a aria-controls="results-QA-table" class="DTTT_button DTTT_button_text" onclick="javascript:analyze_subset('cnvprofiles')"><span>CNV profiles</span></a>
									<a aria-controls="results-QA-table" class="DTTT_button DTTT_button_text" onclick="javascript:analyze_subset('lorenz')"><span>Lorenz curve</span></a>
									<a aria-controls="results-QA-table" class="DTTT_button DTTT_button_text" onclick="javascript:analyze_subset('gc')"><span>GC bias</span></a>
									<a aria-controls="results-QA-table" class="DTTT_button DTTT_button_text" onclick="javascript:analyze_subset('mad')"><span>MAD</span></a>
								</td>
								<td id="results-summary-btns" style="width:25% vertical-align:middle; padding-top:20px;"></td>
							</tr>
						</table>
					</div>

					<!-- Panel: More results -->
					<div id="results-heatmaps" class="panel panel-default" style="display:none;">
						<div class="panel-heading"><span class="glyphicon glyphicon-barcode"></span> Heatmaps</div>
						<div class="panel-body">
							<div class="bs-callout bs-callout-info" style="margin:-15px;">
								<p>Heatmaps are generated using the unique breakpoints found across all samples, and their values correspond to their relative read counts or copy number as determined by their dissimilarity structure for each respective dendrogram.</p>
							</div>
						</div>
						<!-- Table -->
						<table class="table" style="text-align:center;">
							<tr>
								<td>
									<strong>Heatmap of copy number values across all segment breakpoints (using <?php echo ucfirst($config['distMeth']); ?> distance metric)</strong><br/>
									<a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/heatCN.jpeg"; ?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/heatCN.jpeg?uniq=" . rand(1e6,2e6); ?>"></a>
								</td>
							</tr>
							<tr>
								<td>
									<strong>Heatmap of copy number values across all segment breakpoints (using correlation)</strong><br/>
									<a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/heatCor.jpeg"; ?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/heatCor.jpeg?uniq=" . rand(1e6,2e6); ?>"></a>
								</td>
							</tr>
							<tr>
								<td>
									<strong>Heatmap of normalized read counts across segment breakpoints (using <?php echo ucfirst($config['distMeth']); ?> distance metric)</strong><br/>
									<a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/heatNorm.jpeg"; ?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/heatNorm.jpeg?uniq=" . rand(1e6,2e6); ?>"></a>
								</td>
							</tr>
						</table>
					</div>

					<!-- Buttons: back or next -->
					<div id="results-navigation">
						<br/>
						<hr>
						<?php if($GINKGO_USER_ID != '_t10breast_navin' && $GINKGO_USER_ID != '_t16breast_liver_met_navin' && $GINKGO_USER_ID != '_neuron_mcconnell' && $GINKGO_USER_ID != '_ctc_ni' && $GINKGO_USER_ID != '_oocyte_hou' && $GINKGO_USER_ID != '_sperm_lu' && $GINKGO_USER_ID != '_sperm_kirkness' && $GINKGO_USER_ID != '_sperm_wang' && $GINKGO_USER_ID != '_neuron_evrony'): ?>
						<div style="float:left"><a class="btn btn-lg btn-primary" href="?q=dashboard/<?php echo $GINKGO_USER_ID; ?>"><span class="glyphicon glyphicon-chevron-left"></span> Analysis Options </a></div>
						<?php endif; ?>
					</div>
					<br><br><br><br>
				</div>

				<div class="clearfix visible-sm"></div>

				<div class="col-lg-4">
					<h3 style="margin-bottom:-15px;">&nbsp;</h3>
					<?php echo $PANEL_LATER; ?>
					<?php echo $PANEL_INFO; ?>
					<?php echo $PANEL_DOWNLOAD; ?>
					<br>
				</div>
			</div>


			<?php // ================================================================ ?>
			<?php // == Dashboard: Results/Cell ==================================== ?>
			<?php // ================================================================ ?>
			<?php elseif($GINKGO_PAGE == 'results' && $CURR_CELL != ""): ?>
			<!-- Results -->
			<div class="row">
				<div id="results" class="col-lg-12">

					<h3 style="margin-top:-5px;">Viewing cell <?php echo $CURR_CELL; ?></h3><br/>

					<!-- Buttons: back or next -->
					<div id="results-navigation">
						<div style="float:left"><a class="btn btn-lg btn-primary" href="?q=results/<?php echo $GINKGO_USER_ID; ?>"><span class="glyphicon glyphicon-chevron-left"></span> Back to tree </a></div>
					</div>
					<br style="clear:both"/>
					<br/>

					<!-- Panel: Copy-number profile -->
					<div class="panel panel-default">
						<div class="panel-heading">
							<span class="glyphicon glyphicon-align-center"></span> Interactive Profile Viewer
							<div style="float:right; margin-top:-5px;">
								<a id="results-ucsc-btn" class="btn btn-sm btn-primary" href="#" onclick="javascript:viewRegionUCSC()">View region in UCSC browser</a>
							</div>
						</div>
						<!-- Table -->
						<table class="table">
							<tr><td colspan="3"><div class="div_g" id="cell_cnv" style="width:95%;height:200px;"></div></td></tr>
							<tr>
								<td style="text-align:center; width:50%">
									<small><b>Click + Drag</b>: zoom in</small>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<small><b>Shift + Click</b>: move profile</small>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<small><b>Double-click</b>: zoom out</small><br/>
								</td>
								<td style="text-align:center; width:30%; border-left:1px solid #ccc;">
									<form class="form-inline" role="form">
										<div class="form-group">
											<small><label for="searchForGeneName">Find gene:</label></small>
											<input id="searchForGeneName" type="text" class="form-control" style="width:120px !important;">
										</div>
										<a id="searchForGeneBtn" class="btn btn-sm btn-info" href="#" onclick="javascript:searchForGene()">Search</a>
									</form>
								</td>
								<td style="text-align:center; width:30%; border-left:1px solid #ccc;">
									<form class="form-inline" role="form">
										<div class="form-group">
											<small><label for="searchForBin">List genes in bin #</label></small>
											<input id="searchForBin" type="text" class="form-control" style="width:90px !important;">
										</div>
										<a id="listGenesBtn" class="btn btn-sm btn-info" href="#" onclick="javascript:listGenes()">List</a>
									</form>
								</td>
							</tr>
						</table>
					</div>

					<div class="panel panel-default">
						<div class="panel-heading"><span class="glyphicon glyphicon-align-center"></span> Static Profile Viewer</div>
						<!-- Table -->
						<table class="table">
							<tr><td><a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_CN.jpeg?uniq=" . rand(1e6,2e6);?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_CN.jpeg?uniq=" . rand(1e6,2e6);?>"></a></td></tr>
						</table>
					</div>

					<!-- Panel: Histogram of read counts freq. -->
					<div class="panel panel-default">
						<div class="panel-heading"><span class="glyphicon glyphicon-stats"></span> Quality Control</div>
							<table class="table">
								<tr><td colspan="3">
									<b>Genome-wide read distribution</b>
									<a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_dist.jpeg?uniq=" . rand(1e6,2e6); ?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_dist.jpeg?uniq=" . rand(1e6,2e6);?>"></a>
									<p style="text-align:center"><small>The read count distribution spanning the full genome once reads have been tallied within bin boundaries.</small></p>
								</td></tr>
								<tr>
									<td style="text-align:center"><b>Histogram of read count frequency</b></td>
									<td style="text-align:center"><b>Lorenz Curve</b> (for
									<?php
									preg_match('/_([0-9]*)_/', $config['binMeth'], $matches);
									echo $matches[1] / 1000;
									?>kb bins)</td>
									<td style="text-align:center"><b>Frequency of Bin Counts</b></td>
								</tr>

								<tr>
									<td><a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_counts.jpeg?uniq=" . rand(1e6,2e6); ?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_counts.jpeg?uniq=" . rand(1e6,2e6);?>"></a></td>
									<td><a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_lorenz.jpeg?uniq=" . rand(1e6,2e6); ?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_lorenz.jpeg?uniq=" . rand(1e6,2e6);?>"></a></td>
									<td><a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_hist.jpeg?uniq=" . rand(1e6,2e6); ?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_hist.jpeg?uniq=" . rand(1e6,2e6);?>"></a></td>
								</tr>

								<tr>
									<td><p style="text-align:center"><small>Single cell data should fit a negative binomial distribution with narrower histograms representing higher quality data. Wide histograms without a distinct peak are representative of a high degree of amplification bias. Histograms with a mode near zero have a high degree of “read dropout” and are generally the result of poor library preparation or technical sequencing error.</small></p></td>
									<td><p style="text-align:center"><small>The Lorenz curve gives the cumulative fraction of reads as a function of the cumulative fraction of the genome.  Perfect coverage uniformity results in a straight line with slope 1.  The wider the curve below the line of “perfect uniformity” the lower the coverage uniformity of a sample.</small></p></td>
									<td><p style="text-align:center"><small>A histogram of the scaled read count distribution.  Samples with a low coverage dispersion will have reads counts the closely bound the interger copy number state.  These can be seen as clear peaks at integer values of the histrogram.  Samples without clear peaks are indicative of lower quality samples and we recommend choosing a larger binning interval to control the signal to noise ratio.</small></p></td>
								</tr>

							</table>
 					</div>

					<!-- Panel: Analysis JPEG -->
					<div class="panel panel-default">
						<div class="panel-heading"><span class="glyphicon glyphicon-sort-by-attributes"></span> Analysis details</div>
							<table class="table">
								<tr><td>
									<b>GC Correction</b>
									<a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_GC.jpeg?uniq=" . rand(1e6,2e6); ?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_GC.jpeg?uniq=" . rand(1e6,2e6);?>"></a>
								</td></tr>

								<tr><td>
									<b>Sum Of Squares Error</b>
									<a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_SoS.jpeg?uniq=" . rand(1e6,2e6); ?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_SoS.jpeg?uniq=" . rand(1e6,2e6);?>"></a>
								</td></tr>
							</table>
						</div>

						<!-- Buttons: back or next -->
						<div id="results-navigation2">
							<div style="float:left"><a class="btn btn-lg btn-primary" href="?q=results/<?php echo $GINKGO_USER_ID; ?>"><span class="glyphicon glyphicon-chevron-left"></span> Back to tree </a></div>
							<br/><br/><br/><br/>
						</div>
					</div>
				</div>

			<?php endif; ?>

		</div> <!-- /container -->

		<!-- Dialog to confirm creating a new analysis -->
		<div class="modal fade" id="modal-new-analysis">
		  <div class="modal-dialog">
		    <div class="modal-content">
		      <div class="modal-header">
		        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		        <h4 class="modal-title">Start a new analysis</h4>
		      </div>
		      <div class="modal-body">
				<h4>Are you sure? This will require uploading new files.</h4><br/>
		        <p>
					<strong>Note</strong>: You can always come back to this analysis later:<br/><br/>
					<textarea class="input-sm permalink"><?php echo $permalink; ?></textarea>
		        </p>
		      </div>
		      <div class="modal-footer">
		        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
		        <button type="button" class="btn btn-primary" id="btn-new-analysis">Create new analysis</button>
		      </div>
		    </div><!-- /.modal-content -->
		  </div><!-- /.modal-dialog -->
		</div><!-- /.modal -->


		<!-- Bootstrap core JavaScript
		================================================== -->
		<script src="http://code.jquery.com/jquery-2.0.3.min.js"></script>
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>


		<!-- JQuery/Bootstrap customization
		================================================== -->
		<script language="javascript">
		// Transform dropdown menu forms into dialog boxes
		$('.dropdown-toggle').dropdown();
		$('.dropdown-menu').find('form').click(function (e) { e.stopPropagation(); });
		
		// When click inside permalink box, select all
		$(".permalink").focus(function() { $(this).select(); } );
		
		// Search button: query 
		$("#results-search-btn").click(function(){

			$.post("?q=admin-annotate/" + ginkgo_user_id, {
					// 
					'genes': $('#results-search-txt').val(),
				},
				// If get response, refresh page
				function(data) {
					alert("Click OK to start annotating your graphs");
					window.location = "<?php echo URL_ROOT . "/?q=results/"; ?>" + ginkgo_user_id;
				}
			);
		});
		$("#param-segmentation").change(function() {
			// If custom upload, show upload form
			if( $("#param-segmentation").val() == 2 )
				$("#param-segmentation-custom").show();
			else
				$("#param-segmentation-custom").hide();
		});
		$("#param-genome").change(function() {
			// If custom upload, show upload form
			if( $('#param-genome').val() == 'hg19' )
			{
				$("#param-segmentation-maskbadbins").show();
				$("#param-segmentation-pseudoautosomal").show();
			}
			else
			{
				$("#param-segmentation-maskbadbins").hide();
				$("#param-segmentation-pseudoautosomal").hide();
			}
		});
		$("#param-segmentation").change()
		$("#param-genome").change()

		</script>


		<!-- .js files for upload functionality
		================================================== -->
		<script src="includes/fileupload/js/vendor/jquery.ui.widget.js"></script>
		<script src="includes/fileupload/js/jquery.iframe-transport.js"></script>
		<script src="includes/fileupload/js/jquery.fileupload.js"></script>
		<script src="includes/fileupload/js/jquery.fileupload-process.js"></script>
		<script src="includes/fileupload/js/jquery.fileupload-image.js"></script>
		<script src="includes/fileupload/js/jquery.fileupload-audio.js"></script>
		<script src="includes/fileupload/js/jquery.fileupload-video.js"></script>
		<script src="includes/fileupload/js/jquery.fileupload-validate.js"></script>
		<script src="includes/fileupload/js/jquery.fileupload-ui.js"></script>
		<script src="includes/fileupload/js/main.js"></script>
		<!--[if (gte IE 8)&(lt IE 10)]>
		<script src="includes/fileupload/js/cors/jquery.xdr-transport.js"></script>
		<![endif]-->

		<!-- jasny bootstrap-upload -->
		<script src="includes/jasny/bootstrap-fileupload.min.js"></script>
		<link rel="stylesheet" type="text/css" href="includes/jasny/bootstrap-fileupload.min.css">

		<!-- jsPhyloSVG
		================================================== -->
		<script type="text/javascript" src="includes/jsphylosvg/raphael-min.js" ></script>
		<script type="text/javascript" src="includes/jsphylosvg/jsphylosvg.js"></script>

		<!-- uniTip
		================================================== -->
		<link rel="stylesheet" type="text/css" href="includes/unitip/unitip.css">
		<script type="text/javascript" src="includes/unitip/unitip.js"></script>

		<!-- DataTables (sortable tables)
		================================================== -->
		<!-- <script type="text/javascript" language="javascript" src="includes/datatables/jquery.dataTables.min.js"></script> -->
		<link rel="stylesheet" type="text/css" href="includes/datatables/1.10.4/dataTables.tableTools.css">
		<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.0.3/css/font-awesome.css">
		<script type="text/javascript" language="javascript" src="includes/datatables/1.10.4/jquery.dataTables.1.10.4.min.js"></script>
		<script type="text/javascript" language="javascript" src="includes/datatables/1.10.4/dataTables.tableTools.2.2.3.min.js"></script>

		<!-- <link rel="stylesheet" type="text/css" href="includes/datatables/1.10.4/dataTables.fixedHeader.css"> -->
		<script type="text/javascript" language="javascript" src="includes/datatables/1.10.4/dataTables.fixedHeader.min.js"></script>

		<!-- CNV profiles
		================================================== -->
		<script type="text/javascript" language="javascript" src="includes/dygraph/dygraph-combined.js"></script>

		<!-- Ginkgo
		================================================== -->
		<script language="javascript">
		var ginkgo_user_id = "<?php echo $GINKGO_USER_ID; ?>";
		<?php if($GINKGO_PAGE == 'home' && $query[1] == ""): ?>
		window.history.pushState("", "", "?q=/" + ginkgo_user_id);
		<?php endif; ?>
		var g = null;

		// -------------------------------------------------------------------------
		// -- On page load ---------------------------------------------------------
		// -------------------------------------------------------------------------
		$(document).ready(function(){
			<?php if($GINKGO_PAGE == 'home'): ?>
				// Set initial size of upload 
				$(window).resize();

			<?php elseif($GINKGO_PAGE == 'dashboard'): ?>
				// Hide parameters table and analysis status
				$("#params-table").hide();

			<?php elseif($GINKGO_PAGE == 'results'): ?>
				// Don't wait 1 second to show 'Analysis Complete'
				Tinycon.setBubble(0);
				getAnalysisStatus();
				$("#results-summary").hide();
				$("#results-tree").hide();
				
				// Launch function to keep updating status
				ginkgo_progress = setInterval( "getAnalysisStatus()", 1000 );

			<?php endif; ?>
		});

		// -------------------------------------------------------------------------
		// -- Miscellaneous --------------------------------------------------------
		// -------------------------------------------------------------------------
		// On page resize
		$(window).resize(function() {
		    $(".col-lg-8").height(window.innerHeight - $(".navbar").height() - $(".jumbotron").height() - 200 );
		});
		// New Analysis button
		$("#btn-new-analysis").on("click", function(event){
			window.location = '?q=home'
		});
		// Dashboard: Toggle b/w select all cells and select none
		$('#dashboard-toggle-cells').click(function() {
			$('#params-cells input[type="checkbox"]').prop('checked',!$('input[type="checkbox"]').prop('checked'));
			return false;
		});
		// Detect when user changes something in analysis parameters
		$("#form-dashboard :input").change(function() {
			$("#param-binning-sim-options").show();
			
			if($('#param-bins-type').val() == 'fixed_')
				$("#param-binning-sim-options").hide();
			$('#analyze').html('Start Analysis <span class="glyphicon glyphicon-chevron-right"></span>')
		});

		// -------------------------------------------------------------------------
		// -- Create new analysis --------------------------------------------------
		// -------------------------------------------------------------------------
		function startOver()
		{
			if(confirm("Are you sure?\n\nPS: You can come back to this analysis later:\n\n<?php echo $permalink; ?>"))
				window.location = '?q=';
		}

		// -------------------------------------------------------------------------
		// -- Launch analysis ------------------------------------------------------
		// -------------------------------------------------------------------------
		$('#analyze').click(function() {
			// -- Get list of cells of interest
			arrCells = [];
			$("#params-cells :checked").each(function() { arrCells.push($(this).val()); });
			if(arrCells.length < 3)
			{
				alert("Please choose at least 3 cells for your analysis.");
				return;
			}

			// -- Get email
			email = $('#email').val();

			// -- Submit query
			var fd = new FormData( $("form#form-dashboard")[0] );
			$.ajax({
			  url: '?q=admin-upload/' + ginkgo_user_id,
			  data: fd,
			  processData: false,
			  contentType: false,
			  type: 'POST',
			  success: function(data){

					if(data == "error")
						alert("Error: please use the correct extension for custom parameter files.")
					else
					{
						facs = ""; bins = ""; genes = ""; segmentation = "";
						if(data == "facs" || data == "facssegmentation")
							facs = "user-facs.txt"
						if(data == "segmentation" || data == "facssegmentation")
							segmentation = "user-segmentation.txt"

						f = 0; b = 0; g = 0;
						if(facs != "")
							f = 1;
						if(bins != "")
							b = 1;
						if(genes != "")
							g = 1;

						//
						binMethVal = $('#param-bins-type').val() + $('#param-bins-value').val() + $('#param-bins-sim-rlen').val() + $('#param-bins-sim-mapper').val();
						if($('#param-bins-type').val() == 'fixed_')
						{
							binMethVal = $('#param-bins-type').val() + $('#param-bins-value').val();
							binMethVal = binMethVal.substring(0, binMethVal.length - 1);
						}

						//
						rmbadbins = $('#dashboard-rmbadbins').is(':checked') == true ? 1 : 0;
						rmpseudoautosomal = $('#dashboard-rmpseudoautosomal').is(':checked') == true ? 1 : 0;

						//
						$.post("?q=dashboard/" + ginkgo_user_id, {
								// General
								'analyze':	1,
								'cells[]':	arrCells,
								'email':		email,
								// Methods
								'binMeth':	binMethVal,
								'segMeth':	$('#param-segmentation').val(),
								'clustMeth':$('#param-clustering').val(),
								'distMeth':	$('#param-distance').val(),
								// FACS file
								'f':				f,
								'facs':			facs,
								// Plot gene locations
								'g':				0,
								'query':	'',
								// User-specified bin file
								'b':				b,
								'binList':	bins,
								// User specified binning segmentation file
								'segMethCustom': segmentation,
								// Genome
								'chosen_genome': $('#param-genome').val(),
								// Job name
								'job_name'	   : $('#param-job-name').val(),
								// Color scheme
								'color': $('#param-color-scheme').val(),
								// Other options
								'sex': $('#dashboard-include-sex').is(':checked') == true ? 1 : 0,
								'rmbadbins': $('#dashboard-rmbadbins').is(':checked') == true ? 1 : 0,
								'rmpseudoautosomal': $('#dashboard-rmpseudoautosomal').is(':checked') == true ? 1 : 0,
							},
							// If get response
							function(data) {
								if(data == "OK")
									window.location = "<?php echo URL_ROOT . "/?q=results/"; ?>" + ginkgo_user_id;
								else
									alert('Error 87: ' + data)
							}
						);
					}
			  }
			});
		});


		function analyze_subset(analysisType)
		{
			// -- Generate list of selected cells
			var selectedCells = [];
			$('.selected').each(function(){
				selectedCells.push(this.children[1].textContent)
			});

			if(selectedCells.length == 0)
				return

			// -- Submit subset analysis
			$.post("?q=analyze-subset/" + ginkgo_user_id, {
					'analyze-subset'	: 1,
					'selectedCells'		: selectedCells,
					'analysisType'		: analysisType,
				},
				function(data)
				{
					if(data != '-1')
						window.open('?q=results-subset/<?php echo $GINKGO_USER_ID; ?>/' + data, '_blank')
				}
			);
		}
		


		// -------------------------------------------------------------------------
		// -- Refresh progress -----------------------------------------------------
		// -------------------------------------------------------------------------
		function getAnalysisStatus()
		{
			// Load status file
			rndNb = Math.round(Math.random()*10000); // to prevent browser from caching xml file!
			$.get("<?php echo URL_UPLOADS; ?>/" + ginkgo_user_id + "/status.xml?uniq=" + rndNb, function(xmlFile)
			{

				// Extract status fields from status file
				step 		= xmlFile.getElementsByTagName("step")[0].childNodes[0].nodeValue;
				processing 	= xmlFile.getElementsByTagName("processingfile")[0].childNodes[0].nodeValue;
				percentdone = xmlFile.getElementsByTagName("percentdone")[0].childNodes[0].nodeValue;
				tree 		= xmlFile.getElementsByTagName("tree")[0].childNodes[0];
				if(typeof tree != 'undefined')
					tree = tree.nodeValue;

				// Determine progress status output
				if(step == "1")
					desc = "Mapping reads to bins";
				else if(step == "2")
					desc = "Computing quality control statistics";
				else if(step == "3")
					desc = "Calling copy number events";
				else if(step == "4")
					desc = "Re-clustering with new parameters";

				denominator = 3;
				// Keep in mind: step > 3 is for reclust.R (re-draw dendrograms)
				if(step > denominator)
					denominator = step;
				overallDone = Math.floor(100*(step-1+percentdone/100)/denominator);
				$("#results-progress").width(overallDone + "%");
				Tinycon.setBubble(overallDone);

				// We don't do step 2 anymore
				stepShow = step
				if(step > 1)
					stepShow = step - 1

				// Show status
				processingMsg = "(" + processing.replace("_", " ") + ")";
				$("#results-status-text").html(overallDone + "% complete.<br><small style='color:#999'>Step " + stepShow + ": " + percentdone + "%" + " " + desc + "... " + processingMsg + "</small>");
				// Update progress bar % completed
				if(percentdone > 100)
					percentdone = 100;

				// When we're done with the analysis, stop getting progress continually
				// if((step >= 3 && percentdone >= 100) || typeof step == 'undefined')
				if( overallDone >= 100 || typeof step == 'undefined' )
				{
					// Plot tree
					// drawTree(tree);
					drawTree('clust.xml');
					// Remove auto-update timer
					clearInterval(ginkgo_progress);
					Tinycon.setBubble(0);

					// Fix UI
					$(".progress").hide();
					$("#results-status-text").html("Analysis complete!");
					$("#results-navigation").show();
					$("#results-download").show();
					$("#results-download2").show();
					$("#results-search-genes").show();
					$("#results-summary").show();
					$("#results-tree").show();
					//
					$("#results-heatmaps").show();
					$("#results-heatmaps img").each(function(index, value){
						newImg = $(value).attr('src') + '-' + Math.round(Math.random()*10000);
						$(value).attr('src', newImg );
					});
				}

			});

			// Load Quality Assessment file (only runs if file exists)
			$.get("<?php echo URL_UPLOADS; ?>/" + ginkgo_user_id + "/SegStats", function(qaFile)
			{
				if(typeof overallDone != 'undefined' && overallDone < 100)
					return;

				// Turn string into array of lines
				lineNb = 0;
				table = '<thead> ' + '\n' +
						'	<tr style="background-color: white !important"> ' + '\n' +
						'		<th style="text-align:center; vertical-align:middle" width="5%"></th> ' + '\n' +
						'		<th style="text-align:center; vertical-align:middle" width="10%">Cell</th> ' + '\n' +
						'		<th style="text-align:center; vertical-align:middle" width="25%">CNV Profile</th> ' + '\n' +
						'		<th style="text-align:center; vertical-align:middle" width="15%"># Reads</th> ' + '\n' +
						'		<th style="text-align:center; vertical-align:middle" width="15%">Mean read count</th> ' + '\n' +
						'		<th style="text-align:center; vertical-align:middle" width="15%">Read count variance</th> ' + '\n' +
						'		<th style="text-align:center; vertical-align:middle" width="15%">Index of dispersion</th> ' + '\n' +
						'	</tr> ' + '\n' +
						'	</thead>\n';
				table += '<tbody>';
				allLines = qaFile.split("\n");

				omg = [[], [], []];
				for(var line in allLines)
				{
					lineNb++;
					if(lineNb == 1)
						continue;

					arrLine = allLines[line].split("\t");
					if(arrLine.length < 11)
						continue;
					cell  = arrLine[0].replace(/"/g, '');
					score = 0

					//
					rndNb = Math.round(Math.random()*10000); // to prevent browser from caching xml file!
					cnvProfileUrl = "<?php echo URL_UPLOADS; ?>/" + ginkgo_user_id + '/' + cell + '_CN.jpeg?uniq=' + rndNb;
					cellUrl = "?q=results/" + ginkgo_user_id + "/" + cell;

					//
					rowClass = ''
					if(numberWithCommas(arrLine[3].replace(/"/g, '')) < 1)
						rowClass = ' class="danger"'

					//
					newLine =	'<tr' + rowClass + '>' + 
									'<td width="5%" style="text-align:center"></td>' + 
									'<td width="10%" style="text-align:center"><a href="' + cellUrl + '">' + cell + '</a></td>' + 
									'<td width="25%" style="text-align:center"><a href="' + cellUrl + '" alt=""><img height="35" src="' + cnvProfileUrl + '"></a></td>' + 
									'<td width="15%" style="text-align:center">' + numberWithCommas(arrLine[1].replace(/"/g, '')) + '</td>' + 
									'<td width="15%" style="text-align:center">' + numberWithCommas(arrLine[3].replace(/"/g, '')) + '</td>' + 
									'<td width="15%" style="text-align:center">' + numberWithCommas(arrLine[4].replace(/"/g, '')) + '</td>' + 
									'<td width="15%" style="text-align:center">' + numberWithCommas(arrLine[5].replace(/"/g, '')) + '</td>' + 
								'</tr>';

					omg[score].push(newLine);
				}

				for(i=2;i>=0;i--)
					for(j in omg[i])
						table += omg[i][j];

				table += "</tbody>";

				// Hide loading text; show table
				$("#results-QA-loadingTxt").hide();
				$("#results-QA-table").show();
				$("#results-QA-table").html(table);
				oTable = $('#results-QA-table').dataTable(
					{
						"bSort": true,
						"bFilter":false,
						"bInfo":false,
						"iDisplayLength":10,
						"bPaginate":false,
						"aoColumnDefs": [ {
							"aTargets": [3,4,5],
							"fnCreatedCell": function (nTd, sData, oData, iRow, iCol) {
								var $currencyCell = $(nTd);
								var commaValue = $currencyCell.text().replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
								$currencyCell.text(commaValue);
							}
						}],

				        columns: [
				            { data: null, defaultContent: '', orderable: false },
				            { data: 'cell' },
				            { data: 'cnv_profile', orderable: false },
				            { data: 'nbreads' },
				            { data: 'avgReadsPerBin' },
				            { data: 'varReadsPerBin' },
				            { data: 'indexOfDispersion' }
				        ],
						order: [ 1, 'asc' ],

						dom: '<"top"><"bottom"lfrtipT><"clear">',
						tableTools: {
							"sRowSelect"	: "multi",
							"sRowSelector"	: 'td:first-child',
							"aButtons"		: [ "select_all", "select_none" ]
						},
						"initComplete": function() {
							$('div.DTTT_container').appendTo("#results-summary-btns");
						},

					});

    			// Fix header (there's a bug about this that makes it appear in the middle of the page)
    			// new $.fn.dataTable.FixedHeader( oTable );
				$('#results-QA-table_length').css('display', 'none');

				// Plot CNV profile of current cell
				$.get('genomes/<?php echo $config["chosen_genome"] . "/" . ($config["rmpseudoautosomal"] == "1" ? "pseudoautosomal" : "original"); ?>/bounds_<?php echo $config["binMeth"]; ?>', function(data)
				{
					chromBoundaries = data.split('\n');
					for(i=0; i<chromBoundaries.length; i++) {
						tmp = chromBoundaries[i].split('\t');
						chromBoundaries[i] = chromBoundaries[i].replace(tmp[0]+'\t', '')
					}
					loadCellProfile('cnv');
				});
			});
		}

		function numberWithCommas(x) {
		    //return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
		    return x;
		}

		// -------------------------------------------------------------------------
		// -- Load a tree ----------------------------------------------------------
		// -------------------------------------------------------------------------
		function drawTree(treeFile, outputXML)
		{
			// Reload jsphylosvg in case drawing another tree
			// Without that, going from tree1 to 2 to 1 will not display correctly...
			// Maybe there are some global variables that are changed in jsphylosvg and need to be reset
			newScript = document.createElement('script');
			newScript.src = 'includes/jsphylosvg/jsphylosvg.js';
			document.getElementsByTagName('head')[0].appendChild(newScript);

			rndNb = Math.round(Math.random()*10000); // to prevent browser from caching xml file!
			$.get("<?php echo URL_UPLOADS; ?>/" + ginkgo_user_id + "/" + treeFile + '?uniq=' + rndNb, function(xmlFile)
			{
				// Debug
				if(outputXML == true)
					console.log( (new XMLSerializer()).serializeToString(xmlFile) );

				//
				if(xmlFile == "")
					return;

				$( xmlFile.getElementsByTagName("branch_length") ).each(function(index, value)
				{
					currElement = xmlFile.getElementsByTagName("branch_length")[index];
					currVal = parseInt(value.childNodes[0].nodeValue)
					//
					if(currVal < 1)
						currElement.childNodes[0].nodeValue = '2';

					if(currVal > 200)
						currElement.childNodes[0].nodeValue = 40;
				});

				// Annotate the phyloXML file
				$( xmlFile.getElementsByTagName("name") ).each(function(index, value)
				{
					//	<name>Espresso</name>
					//	<annotation>
					//		<desc>Base of many coffees</desc>
					//		<uri>http://en.wikipedia.org/wiki/Espresso</uri>
					//	</annotation>

					// Current 'name' node
					currElement = xmlFile.getElementsByTagName("name")[index];
					cellId = value.childNodes[0].nodeValue;

					// Add 'annotation' node next to 'name'
					annotationNode	= currElement.parentNode.appendChild(xmlFile.createElement("annotation"));
					annotationNode
						.appendChild(xmlFile.createElement("desc"))
						.appendChild(xmlFile.createTextNode("<img height='80' width='290' src='<?php echo URL_UPLOADS; ?>/" + ginkgo_user_id + "/" + cellId + "_CN.jpeg'>" + cellId));
					annotationNode
						.appendChild(xmlFile.createElement("uri"))
						.appendChild(xmlFile.createTextNode("?q=results/" + ginkgo_user_id + "/" + cellId));
				});

				$("#svgCanvas").html("");
				ginkgo_phylocanvas = "";
				dataObject = "";

				// Define tree and size (based on current window size!)
				var dataObject = { xml: xmlFile, fileSource: true };

				// Show tree
				treeHeight = 200
				treeWidth  = $("#svgCanvas").width()
				ginkgo_phylocanvas = new Smits.PhyloCanvas(dataObject, 'svgCanvas', treeWidth, treeHeight); //, 'circular'

				// Resize SVG to fit by height
				var c = document.getElementsByTagName("svg");
				//var rec = c[0].getBoundingClientRect();	// Works in FF, not in Chrome
				var rec = c[0].getBBox();			// Works in FF, Chrome
				$("svg").css("height", rec.height+50 + "px");

				// Init unitip (see unitip.css to change tip size)
				init();
			});
		}


		// =========================================================================
		// == Load profile =========================================================
		// =========================================================================
		allCellProfiles = [];
		function loadCellProfile(cellName)
		{
			// -- Settings -----------------------------------------------------------		
			cellName = '<?php echo $CURR_CELL; ?>'
			graphTitle = cellName;
			axisColor = 'black';
			axisFontSize = 14;
			labelsDisplay = 'block';

			// Settings for Chr
			if(cellName == 'CHR') {
				graphTitle = '';
				axisColor = 'white';
				labelsDisplay = 'none';
			}

			// -- Load file that specifies bin # <--> chr:pos
			$.get('genomes/<?php echo $config["chosen_genome"] . "/" . ($config["rmpseudoautosomal"] == "1" ? "pseudoautosomal" : "original"); ?>/<?php echo $config["binMeth"]; ?>', function(data){

				binToPos = data.split('\n');
				// Note i=1 b/c skipping header
				for(i=1; i<binToPos.length; i++) {
					tmp = binToPos[i].split('\t');
					binToPos[i] = tmp
				}

				// -- Load data file -----------------------------------------------------
				var blockRedraw = false;
				allCellProfiles.push(
					g = new Dygraph(
						document.getElementById("cell_cnv"),
						"uploads/" + ginkgo_user_id + "/" + cellName + ".cnv?uniq=" + Math.round(Math.random()*10000),
						{
							// Settings
							rollPeriod: 0,
							showRoller: false,
							errorBars: false,
							valueRange: [-2,10],
							animatedZooms: true,
							sigFigs: 4,
							axisLabelFontSize: axisFontSize,
							axisLabelColor: axisColor,
							// Grid
							drawYGrid: true,
							drawXGrid: false,
							gridLineColor: '#ccc',
							// Title
							title: graphTitle,
							// Labels
							labelsSeparateLines: true,
							labelsDivWidth: 300,
							hideOverlayOnMouseOut: false,
							labelsDivStyles: {
								'backgroundColor': 'rgba(230, 230, 230, 0.30)',
								'padding': '10px',
								'border': '1px solid black',
								'borderRadius': '5px',
								'boxShadow': '4px 4px 4px #888',
								'display': 'block',
							},
        					labels:["Bin Number", "Copy-Number"],
							axes: {
							  x: {
							    valueFormatter: function(x) {
							      return '<b>Bin ' + x + '</b>' + '<br/><span> <span style="color: rgb(0,128,128);"><b>Position</b>:&nbsp;</span>' + binToPos[x][0] + ':' + binToPos[x][1] + '-' + binToPos[x+1][1] + ' <a target="_blank" href="https://genome.ucsc.edu/cgi-bin/hgTracks?db=<?php echo $config["chosen_genome"]; ?>&position=' + binToPos[x][0] + ':' + binToPos[x][1] + '-' + binToPos[x+1][1] + '"><img src="http://i.stack.imgur.com/3H2PQ.png"></a><br/><div style="display:none">';
							    },
							  },
							  y: {
							    valueFormatter: function(y) {
							      return '</div><b><span style="color: rgb(0,128,128);">Copy-Number</span></b>:&nbsp;' + y;
							    },
							  },
							 },

							// Chromosome boundaries
							underlayCallback: function(canvas, area, g) {
								if(cellName != 'CHR')
									for(key in chromBoundaries)
									{
										var bottom_left = g.toDomCoords(parseInt(chromBoundaries[key])-1, -20);
										var top_right = g.toDomCoords(parseInt(chromBoundaries[key])+1, +20);
										var left = bottom_left[0];
										var right = top_right[0];
										canvas.fillStyle = "rgba(255, 20, 20, 0.3)";
										canvas.fillRect(left, area.y, right - left, area.h);
									}
							},

						}
					)
				);

			});

		}
		
		// http://dygraphs.com/tests/callback.html
		pts_info = function(e, x, pts, row) {
			var str = "(" + x + ") ";
			for (var i = 0; i < pts.length; i++) {
				var p = pts[i];
				if (i) str += ", ";
				str += p.name + ": " + p.yval;
			}

			var x = e.offsetX;
			var y = e.offsetY;
			var dataXY = g.toDataCoords(x, y);
			str += ", (" + x + ", " + y + ")";
			str += " -> (" + dataXY[0] + ", " + dataXY[1] + ")";
			str += ", row #"+row;

			return str;
		};

		// 
		var toArray = function(data) {
			var lines = data.split("\n");
			var arry = [];
			for (var idx = 0; idx < lines.length; idx++)
			{
				var line = lines[idx];
				// Oftentimes there's a blank line at the end. Ignore it.
				if (line.length == 0)
					continue;
				var row = line.split(",");
				// Special processing for every row except the header.
			    row[0] = parseFloat(row[0]);
			    row[1] = parseFloat(row[1]);
				arry.push(row);
			}
			return arry;
		}


		// =====================================================================
		// == View current region in UCSC browser in new tab/window ============
		// =====================================================================
		function viewRegionUCSC()
		{
			btnOrigTxt = $("#results-ucsc-btn").text()
			$("#results-ucsc-btn").text('Loading...')
			$("#results-ucsc-btn").attr('disabled', true) // .prop() doesn't work since it's an <a> button

			//
			binRange = g.xAxisRange()
			posStart = binToPos[ Math.ceil(binRange[0]) ]
			posEnd   = binToPos[ Math.ceil(binRange[1]) ]
			//
			chrStart = posStart[0]
			chrEnd   = posEnd[0]
			//
			nuclStart= posStart[1]
			nuclEnd  = posEnd[1]

			//
			range = chrStart + ':' + nuclStart + '-' + nuclEnd

			nextChr = "chr" + ( parseInt(chrStart.replace(/^\D+/g,'')) + 1 )
			if(chrStart != chrEnd)
				for(x=-1;x<(binToPos.length-1);x++)
				{
					if(binToPos[x+1][0] == nextChr)
					{
						range = chrStart + ":" + nuclStart + "-" + binToPos[x][1]
						break
					}
				}

			// -- Submit subset analysis
			$.post("?q=ucsc/" + ginkgo_user_id, {
					'ucsc'	: 1,
					'range'	: range,
					'cell'	: '<?php echo $CURR_CELL; ?>',
				},
				function(data) {
					window.open('https://genome.ucsc.edu/cgi-bin/hgTracks?db=<?php echo $config["chosen_genome"]; ?>&position=' + range + '&hgt.customText=<?php echo $userUrl; ?>/' + data + '.ucsc', '_blank')
				}
			);

			$("#results-ucsc-btn").text(btnOrigTxt)
			$("#results-ucsc-btn").attr('disabled', false)
		}

		//
		function searchForGene()
		{
			$('#searchForGeneBtn').toggleClass('disabled', 'true');
			gene = $("#searchForGeneName").val()

			$.get("?q=admin-search/" + ginkgo_user_id + '&gene=' + gene, function(binNumber){
				if(binNumber == "" || binNumber == 'NaN')
				{
					$('#searchForGeneBtn').toggleClass('disabled', 'false');
					alert('Gene not found :(')
					return
				}

				binNumber = parseInt(binNumber)

				allAnnotations = g.annotations();
				allAnnotations.push(
				{
					series: 'Copy-Number',
					x: binNumber,
					shortText: gene,
					width: 40,
					height: 20,
					cssClass: 'graph-annotations',
					text: "Gene: " + gene + "\nBin: " + binNumber
				}
				);
				console.log(g)

				g.updateOptions({
					dateWindow: [binNumber - 200, binNumber + 200]
				});

				g.setAnnotations(allAnnotations);

				$('#searchForGeneBtn').toggleClass('disabled', 'false');
			});
		}
		//
		function listGenes()
		{
			binNumber = parseInt($("#searchForBin").val())
			$.get("?q=admin-search/" + ginkgo_user_id + '&binNumber=' + binNumber, function(genes){
				alert('Genes in bin ' + binNumber + ':\n' + '----------------------\n' + genes)
			})
		}
		//
		$('#searchForGeneName').bind('keydown', function(e) {
			if (e.keyCode == 13)
			{
				searchForGene()
				e.preventDefault();
			}
		});
		$('#searchForBin').bind('keydown', function(e) {
			if (e.keyCode == 13)
			{
				listGenes()
				e.preventDefault();
			}
		});

		</script>

		<style>
		.graph-annotations
		{
			font-size: 11px !important;
			color: #fff !important;
			background-color: #c73030;

		}
		</style>
	</body>
</html>
