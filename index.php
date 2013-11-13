<?php

// =============================================================================
// URL STRUCTURE: ?q=[page]/[userID]/[analysisID]
// -----------------------------------------------------------------------------
//	page =
//		-> '' or home:	where users upload their files
//		-> dashboard:		where users choose analysis settings or see progress of
//											current analysis (can only run 1 analysis at a time on 1
//											particular data set)
//		-> analyze:			show report of previous analysis
//
//	userID = unique ID for the current set of files to analyze
//
//	analysisID = user can perform many analyses on the same data and save
//							 analysis results. Each such analysis has a unique ID.
// 							 (NOT YET IMPLEMENTED)
// =============================================================================

## -- Configuration ------------------------------------------------------------
include "bootstrap.php";
$GINKGO_MIN_NB_CELLS= 3;

## -- Parse user query ---------------------------------------------------------
$query				= explode("/", $_GET['q']);

// Extract page
$GINKGO_PAGE			= $query[0];
if(!$GINKGO_PAGE)
	$GINKGO_PAGE		= 'home';

// Extract user ID
$GINKGO_USER_ID		= $query[1];
if(!$GINKGO_USER_ID)
	$GINKGO_USER_ID	= generateID(20);

## -- Page-specific configuration ----------------------------------------------
// Step 1 (choose cells) & Step 2 (specify email)
if($GINKGO_PAGE == "dashboard")
  $MY_CELLS = getMyFiles($GINKGO_USER_ID);

if($GINKGO_PAGE == "results")
  $CURR_CELL = $query[2];

## -- Session management -------------------------------------------------------
$_SESSION["user_id"] = $GINKGO_USER_ID;

## -- Template configuration -----
// Panel for permalink
$permalink = URL_ROOT . '?q=results/' . $GINKGO_USER_ID;
$PANEL_LATER = <<<PANEL
			<!-- Panel: Save for later -->
			<div class="panel panel-primary">
				<div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-time"></span> View analysis later</h3></div>
				<div class="panel-body">
					Access your results later at the following address:<br/><br/>
					<textarea class="input-sm permalink">{$permalink}</textarea>
				</div>
			</div>
PANEL;

// Panel to show user's last analysis, if any
if(file_exists(DIR_UPLOADS . '/' . $GINKGO_USER_ID . '/status.xml'))
	$PANEL_PREVIOUS = <<<PANEL
			<!-- Panel: View previous analysis results -->
			<div class="panel panel-primary">
				<div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-stats"></span> Previous analysis results</h3></div>
				<div class="panel-body">
					See your <a href="?q=results/$GINKGO_USER_ID">previous analysis results</a>.<br/><br/>
					<strong>Note</strong>: Running another analysis will overwrite previous results.
				</div>
			</div>
PANEL;

// Define user directory
$userDir = DIR_UPLOADS . '/' . $GINKGO_USER_ID;

## -- Upload facs / binning file -----------------------------------------------
if($GINKGO_PAGE == 'admin-upload')
{
	// Create user directory if doesn't exist
	@mkdir($userDir);

	// Removed params-binning-file but have params-segmentation-file

	// Error: invalid file type => return error
	if($_FILES['params-facs-file']['name'] != "" || $_FILES['params-segmentation-file']['name'] != "")
		if($_FILES['params-facs-file']['type'] != "text/plain" || $_FILES['params-segmentation-file']['type'] != "text/plain")
			die("error");

	$result = "";

	// FACS file
	if(!empty($_FILES['params-facs-file']))
	{
		// Upload facs file
		if(is_uploaded_file($_FILES['params-facs-file']['tmp_name']))
		{
			move_uploaded_file($_FILES['params-facs-file']['tmp_name'], $userDir . "/user-facs.txt");
			$result .= "facs";
		}
	}

	// Segmentation file
	if(!empty($_FILES['params-segmentation-file']))
	{
		// Upload binning file
		if(is_uploaded_file($_FILES['params-segmentation-file']['tmp_name']))
		{
			move_uploaded_file($_FILES['params-segmentation-file']['tmp_name'], $userDir . "/user-segmentation.txt");
			$result .= "segmentation";
		}
	}

	die($result);
}

## -- Upload facs / binning file -----------------------------------------------
if($GINKGO_PAGE == 'admin-annotate')
{
	// Sanitize user input (see bootstrap.php)
	array_walk_recursive($_POST, 'sanitize');
	$genes = str_replace("'", "", $_POST['genes']);

	// Two notes about changing config:
	// 	-> Don't want to send email to users when it's done => set email to empty
	//	-> Change gene list file name (query.txt -- see scripts/analyze)
	file_put_contents( $userDir . "/config", "email=\"\"\ngeneList=\"query.txt\"\ninit=0\nprocess=0\nfix=0\nq=1\n", FILE_APPEND );
	file_put_contents( $userDir . "/query.txt", $genes );

	// Start analysis
	$cmd = "./scripts/analyze $GINKGO_USER_ID >> $userDir/ginkgo.out 2>&1  &";
	session_regenerate_id(TRUE);	
	$handle = popen($cmd, 'r');
	//$out = stream_get_contents($handle);
	pclose($handle);

	exit;
}



## -- Submit analysis ----------------------------------------------------------
if(isset($_POST['analyze'])) {
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
	if(file_exists($configFile)) {
		$f = file($configFile);
		$oldParams = array();
		foreach($f as $index => $val) {
			$values = explode("=", $val, 2);
			$oldParams[$values[0]] = str_replace("", "", trim($values[1]));
		}

		// Defaults for old analysis (do nothing)
		$init = 0;
		$process = 0;
		$fix = 0;

		// Do we need to remap? This sets init to 1 if yes, 0 if not
		$newBinParams = ($oldParams['binMeth'] != $_POST['binMeth']) || 
											($oldParams['binList'] != $_POST['binList']) ||
											($oldParams['facs'] != $_POST['facs']);
		$newSegParams = ($oldParams['segMeth']   != $_POST['segMeth']);
		$newClustering= ($oldParams['clustMeth'] != $_POST['clustMeth']);
		$newDistance  = ($oldParams['distMeth']  != $_POST['distMeth']);

		// Set new variable values
		#
		if($newBinParams)
			$init = 1;
		#
		if($newBinParams || $newSegParams)
			$process = 1;
		# Only need to run fix when not running process
		if(!$process && ($newClustering || $newDistance))
			$fix = 1;
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

	$config.= 'segMeth=' . $_POST['segMeth'] . "\n";
	$config.= 'binMeth=' . $_POST['binMeth'] . "\n";
	$config.= 'clustMeth=' . $_POST['clustMeth'] . "\n";
	$config.= 'distMeth=' . $_POST['distMeth'] . "\n";

	$config.= 'b=' . $_POST['b'] . "\n";
	$config.= 'binList=' . $_POST['binList'] . "\n";
	$config.= 'f=' . $_POST['f'] . "\n";
	$config.= 'facs=' . $_POST['facs'] . "\n";
	$config.= 'q=' . $_POST['g'] . "\n";
	$config.= 'geneList=' . $_POST['geneList'] . "\n";
	$config.= 'chosen_genome=' . $_POST['chosen_genome'] . "\n";

	$config.= 'init=' . $init . "\n";
	$config.= 'process=' . $process . "\n";
	$config.= 'fix=' . $fix . "\n";
	
	$config.= 'ref=' . $_POST['segMethCustom'] . "\n";

	file_put_contents($userDir . '/config', $config);

	// Start analysis
	$cmd = "./scripts/analyze $GINKGO_USER_ID >> $userDir/ginkgo.out 2>&1  &";
	session_regenerate_id(TRUE);	
	$handle = popen($cmd, 'r');
	#$out = stream_get_contents($handle);
	pclose($handle);
	echo "OK";
	exit;
}

// Load status.xml if exists and check if analysis under way
if($GINKGO_PAGE == "" | $GINKGO_PAGE == "home" || $GINKGO_PAGE == "dashboard") {
	$statusFile = DIR_UPLOADS . '/' . $GINKGO_USER_ID . '/status.xml';
	if(file_exists($statusFile)) {
		$status = simplexml_load_file($statusFile);
		
		if($status->step < 3 && $status->percentdone < 100) {
			header("Location: ?q=results/" . $GINKGO_USER_ID);
			exit;
		}
	}
}

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
			html, body	{ height:100%; }
			td					{ vertical-align:middle !important; }
			code input	{ border:none; color:#c7254e; background-color:#f9f2f4; width:100%; }
			svgCanvas		{ fill: none; pointer-events: all; }
			.jumbotron	{ padding:50px 30px 15px 30px; }
			.glyphicon	{ vertical-align:top; }
			.badge			{ vertical-align:top; margin-top:5px; }
			.permalink	{ border:1px solid #DDD; width:100%; color:#666; background:transparent; font-family:"courier"; resize:none; height:50px; }
			#results-navigation { display:none; }
		</style>

		<!-- Tinycon styles/javascript -->
		<script type="text/javascript" src="includes/tinycon/tinycon.min.js"></script>
		<link rel="icon" href="includes/tinycon/ginkgo.ico" />

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
					<a class="navbar-brand" href="?q=home/<?php echo $GINKGO_USER_ID; ?>"><span class="glyphicon glyphicon-tree-deciduous"></span> Ginkgo</a>

					<ul class="nav navbar-nav">
						<li><a data-toggle="modal" href="#modal-new-analysis"><strong>New analysis</strong></a></li>
					</ul>


				</div>
				<div class="navbar-collapse collapse">
					<ul class="nav navbar-nav navbar-right">
						<li><a href="javascript:void(0);">About</a></li>
						<li><a href="javascript:void(0);">FAQ</a></li>
					</ul>
				</div><!--/.navbar-collapse -->
			</div>
		</div>

		<!-- Welcome message -->
		<div class="jumbotron">
			<div class="container">
				<h1>Ginkgo</h1>
				<div id="status" style="margin-top:20px;">
					<?php if($GINKGO_PAGE == 'home'): ?>
					A web tool for analyzing single-cell sequencing data.
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
			<div class="row" style="height:100%;">
				<div class="col-lg-8">
					<h3 style="margin-top:-5px;"><span class="badge">STEP 0</span> Upload your .bed files <small><strong>(We accept *.bed, *.zip, *.tar, *.tar.gz and *.tgz)</strong></small></h3>
					<iframe id="upload-iframe" style="width:100%; height:100%; border:0;" src="includes/fileupload/?user_id=<?php echo $GINKGO_USER_ID; ?>"></iframe>
					<p>
						<div style="float:right">
							<a class="btn btn-lg btn-primary" href="?q=dashboard/<?php echo $GINKGO_USER_ID; ?>">Next step <span class="glyphicon glyphicon-chevron-right"></span></a>
						</div>
					</p>
				</div>
				<div class="col-lg-4">

					<?php echo $PANEL_PREVIOUS; ?>
					<?php echo $PANEL_LATER; ?>

					<!-- Panel: Help -->
					<div class="panel panel-primary">
						<div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-question-sign"></span> Help</h3></div>
						<div class="panel-body"><div class="panel-group" id="help-makebed"><div class="panel panel-default">
							<div class="panel-heading"><h4 class="panel-title"><a class="accordion-toggle" data-toggle="collapse" data-parent="#help-makebed" href="#help-makebed-content">How to make .bed files</a></h4></div>
							<div id="help-makebed-content" class="panel-collapse collapse in">
								<div class="panel-body">
									<p>Open a terminal and navigate to your data folder:</p>
									<div style="background-color:#f9f2f4">
										<code>$ <input type="text" value="bowtie2 file > file.bam"></code>
										<code>$ <input type="text" value="bamToBed file.bam > file.bed"></code>
									</div>
								</div>
							</div>
						</div>
					</div>

					<br/>
					<div class="panel-group" id="help-bedfmt"><div class="panel panel-default">
						<div class="panel-heading"><h4 class="panel-title"><a class="accordion-toggle" data-toggle="collapse" data-parent="#help-bedfmt" href="#help-bedfmt-content">What a .bed file should look like</a></h4></div>
						<div id="help-bedfmt-content" class="panel-collapse collapse in">
							<div class="panel-body">
								<p><table class="table">
									<thead><tr><th>chrom</th><th>chromStart</th><th>chromEnd</th></tr></thead>
									<tbody><tr><td>chr1</td><td>555485</td><td>555533</td></tr><tr><td>chr1</td><td>676584</td><td>676632</td></tr><tr><td>chr1</td><td>745136</td><td>745184</td></tr></tbody>
								</table></p>
							</div>
						</div>
					</div>
				</div>
			</div>

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
					
					<div id="params-cells">
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
						<br/>
						<button id="dashboard-toggle-cells" class="btn btn-info" style="margin:20px;">Select all cells</button>
					</div>

					<!-- Which genome? -->
					<br/><br/><h3 style="margin-top:-5px;"><span class="badge">STEP 2</span> Set analysis options <small></small></h3>
					<div id="params-genome" style="margin:20px;">
						<table class="table table-striped">
							<tbody>
								<tr>
									<td width="20%">Genome:</td>
									<td>
										<select id="param-genome" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
											<optgroup label="Latest genomes">
												<option value="hg19">Human (hg19)</option>
												<option value="panTro4">Chimpanzee (panTro4)</option>
											</optgroup>
											<optgroup label="Older genomes">
												<option value="hg18">Human (hg18)</option>
												<option value="panTro3">Chimpanzee (panTro3)</option>
											</optgroup>
										</select>
									</td>
								</tr>

								<tr>
									<td>FACS file:</td>
									<td>
										<!-- <form enctype='multipart/form-data'> -->
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
							</table>
					</div>

					<!-- Get informed by email when done? -->
					<br/><br/><h3 style="margin-top:-5px;"><span class="badge">STEP 3</span> E-mail notification <small></small></h3>
					<div id="params-email" style="margin:20px;">
						<p>If you want to be notified once the analysis is done, enter your e-mail here:<br/></p>
						<div class="input-group">
							<span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
							<input id="email" class="form-control" type="text" placeholder="my@email.com">
						</div>
					</div>
					<br/><br/>

				<!-- buttons -->

					<!-- Set parameters -->
					<h3 style="margin-top:-5px;"><span class="badge">OPTIONAL</span> <a href="#parameters" onClick="javascript:$('#params-table').toggle();">Advanced parameters</a></h3>
					<table class="table table-striped" id="params-table">
						<tbody>
							<tr>
								<td>General Binning Options</td>
								<td>
									Use a <select id="param-bins-type" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="variable_">variable</option>
									<option value="fixed_">fixed</option>
									</select> bin size of <select id="param-bins-value" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="50000">50kb</option>
									<option value="40000">40kb</option>
									<option value="25000">25kb</option>
									<option value="10000">10kb</option>
									</select> size.
								</td>
							</tr>
							<!--<tr>
								<td>Custom binning file (format?)</td>
								<td style="height:45px;">
										<div class="fileupload fileupload-new" data-provides="fileupload">
											<div class="input-append">
												<div class="uneditable-input span3">
													<i class="glyphicon glyphicon-upload"></i>
													<span class="fileupload-preview"></span>
												</div>

												<span class="btn btn-file">
													<span class="fileupload-new btn btn-success">Select .txt file</span>
													<span class="fileupload-exists btn btn-success">Change</span>
													<input type="file" name="params-binning-file" />
												</span>

												<a href="#" class="btn btn-danger fileupload-exists" data-dismiss="fileupload">Remove</a>
											</div>
										</div>

									
									This will overwrite the general binning options.
								</td>
							</tr>-->
							<tr>
								<td>Segmentation</td>
								<td>Use <select id="param-segmentation" class="input-medium" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
								<option value="0">Independent (normalized read counts)</option>
								<option value="1">Global (sample with lowest IOD)</option>
								<option value="2">Custom (using uploaded reference sample)</option>
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
							<tr>
								<td>Clustering</td>
								<td>
									Use <select id="param-clustering" class="input-small" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="single">single</option>
									<option value="complete">complete</option>
									<option value="average">average</option>
									<option value="ward">ward</option>
									</select> clustering.
								</td>
							</tr>
							<tr>
								<td>Distance metric</td>
								<td>
									Use <select id="param-distance" class="input-small" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="euclidian">Euclidean</option>
									<option value="maximum">maximum</option>
									<option value="manhattan">Manhattan</option>
									<option value="canberra">Canberra</option>
									<option value="binary">binary</option>
									<option value="minkowski">Minkowski</option>
									</select> distance.
								</td>
							</tr>
						</tbody>
					</table>
					<br/>
					<a name="parameters"></a>
					
					
					

					<!-- Buttons: back or next -->
					<hr style="height:5px;border:none;background-color:#CCC;" /><br/>
					<div style="float:left"><a class="btn btn-lg btn-primary" href="?q=/<?php echo $GINKGO_USER_ID; ?>"><span class="glyphicon glyphicon-chevron-left"></span> Manage Files </a></div>
					<div style="float:right"><a id="analyze" class="btn btn-lg btn-primary" href="javascript:void(0);">Start Analysis <span class="glyphicon glyphicon-chevron-right"></span></a></div><br/><br/><br/>

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
					<p>Click on individual cells for details of the copy-number analysis.</p>

					<div id="svgCanvas" class="row-fluid">
						Analyzing your data...
					</div>

					<h3>&nbsp;</h3>

					<!-- Panel: Search for genes -->
					<div id="results-search-genes" class="panel panel-default" style="display:none;">
						<div class="panel-heading"><span class="glyphicon glyphicon-search"></span> Annotate copy-number profile</div>
						<div class="panel-body">
							Label copy-number profiles with the following chromosome positions:<br/><small>Format: chrName startPos endPos<br/>Specify one range per line.</small><br/><br/>
							<textarea id="results-search-txt" class="form-control" rows="3" placeholder="chr1 10000 2392392"><?php echo @file_get_contents($userDir . "/query.txt"); ?></textarea>
						</div>
						<!-- Table -->
						<table class="table">
							<tr><td style="text-align:right"> <button type="button" class="btn btn-info" id="results-search-btn">Save</button> </td></tr>
						</table>
					</div>

					<br/>

					<!-- <h3 style="margin-top:-5px;"><span class="badge">QA</span> Quality Assessment</h3> -->
					<!-- Panel: Quality Assessment -->
					<div class="panel panel-default">
						<div class="panel-heading"><span class="glyphicon glyphicon-certificate"></span> Quality Assessment</div>
						<div class="panel-body" id="results-QA-loadingTxt">
							<p>Verifying that your files are adequate for copy-number analysis...</p>
						</div>
						<!-- Table -->
							<table class="table">
								<thead><tr><th width="5%">&nbsp;</th><th style="text-align:center" width="20%">Cell</th><th style="text-align:center" width="20%">Bin Count<br/>Too Low</th><th style="text-align:center" width="20%">Index of Dispersion<br/>Too High</th><th width="55%">Recommendation</th></tr></thead><tbody>
							</table>
						<div style="height:300px; overflow:auto;">
							<table class="table" id="results-QA-table" style="display:none;">
							</table>
						</div>
					</div>

					<br/>

					<!-- Panel: Download results -->
					<div id="results-download" class="panel panel-default" style="display:none;">
						<div class="panel-heading"><span class="glyphicon glyphicon-tree-deciduous"></span> Download tree</div>
						<!-- Table -->
						<table class="table">
							<tr class="active"><td><strong>Tree built using normalized read counts</strong>: <a target="_blank" href="<?php echo URL_UPLOADS . '/' . $GINKGO_USER_ID . '/clust.newick'; ?>">.newick</a> | <a target="_blank" href="<?php echo URL_UPLOADS . '/' . $GINKGO_USER_ID . '/clust.xml'; ?>">.xml</a>&nbsp;&nbsp;&nbsp;<em>(plotted above)</em></td></tr>

							<tr class="active"><td><strong>Tree built using copy-number values</strong>: <a target="_blank" href="<?php echo URL_UPLOADS . '/' . $GINKGO_USER_ID . '/clust2.newick'; ?>">.newick</a> | <a target="_blank" href="<?php echo URL_UPLOADS . '/' . $GINKGO_USER_ID . '/clust2.xml'; ?>">.xml</a></td></tr>
						</table>
					</div>

					<br/>

					<!-- Panel: More results -->
					<div id="results-heatmaps" class="panel panel-default" style="display:none;">
						<div class="panel-heading"><span class="glyphicon glyphicon-barcode"></span> Heatmaps</div>
						<!-- Table -->
						<table class="table">
							<tr>
								<td><a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/heatCN.jpeg"; ?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/heatCN.jpeg"; ?>"></a><br/>[to add: what on earth this is]</td>
							</tr>
							<tr>
								<td><a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/heatNorm.jpeg"; ?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/heatNorm.jpeg"; ?>"></a><br/>[to add: what on earth this is]</td>
							</tr>
						</table>
					</div>



					<!-- Buttons: back or next -->
					<div id="results-navigation">
						<br/><br/>
						<hr style="height:5px;border:none;background-color:#CCC;" /><br/>
						<div style="float:left"><a class="btn btn-lg btn-primary" href="?q=dashboard/<?php echo $GINKGO_USER_ID; ?>"><span class="glyphicon glyphicon-chevron-left"></span> Analysis Options </a></div>
					</div>
				</div>

				<div class="col-lg-4">
					<?php echo $PANEL_LATER; ?>
				</div>
			</div>




			<?php // ================================================================ ?>
			<?php // == Dashboard: Results/Cell ==================================== ?>
			<?php // ================================================================ ?>
			<?php elseif($GINKGO_PAGE == 'results' && $CURR_CELL != ""): ?>
			<!-- Results -->
			<div class="row">
				<div id="results" class="col-lg-8">

					<h3 style="margin-top:-5px;">Viewing cell <?php echo $CURR_CELL; ?></h3><br/>

					<!-- Buttons: back or next -->
					<div id="results-navigation">
						<div style="float:left"><a class="btn btn-lg btn-primary" href="?q=results/<?php echo $GINKGO_USER_ID; ?>"><span class="glyphicon glyphicon-chevron-left"></span> Back to tree </a></div>
					</div>
<br style="clear:both"/>
						<hr style="height:5px;border:none;background-color:#CCC;" /><br/>


					<!-- Panel: Copy-number profile -->
					<div class="panel panel-default">
						<div class="panel-heading"><span class="glyphicon glyphicon-align-center"></span> Copy-number profile</div>
						<!-- Table -->
						<table class="table">
							<tr>
								<td><a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_result.jpeg";?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_result.jpeg";?>"></a></td>
							</tr>
						</table>
					</div>



					<!-- Panel: Histogram of read counts freq. -->
					<div class="panel panel-default">
						<div class="panel-heading"><span class="glyphicon glyphicon-stats"></span> Statistics</div>
						<div class="panel-body">
							<a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_stats.jpeg";?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_stats.jpeg";?>"></a>
						</div>
						<!-- Table -->
						<table class="table">
							<tr>
								<td>
							<b>Genome-wide read distribution</b>
							<p>This gives a quick look at the binned read count distribution across the genome and allows easy identification of cells with strange read coverage.</p>
							<b>Histogram of read count frequency</b>
							<p>Single cell data should fit a negative binomial distribution with narrower histograms representing higher quality data. Wide histograms without a distinct peak are representative of a high degree of amplification bias. Histograms with a mode near zero have a high degree of “read dropout” and are generally the result of poor library preparation or technical sequencing error.</p>
							<b>Lorenz Curve</b>
							<p>The Lorenz curve gives the cumulative fraction of reads as a function of the cumulative fraction of the genome.  Perfect coverage uniformity results in a straight line with slope 1.  The wider the curve below the line of “perfect uniformity” the lower the coverage uniformity of a sample.</p>
								</td>
							</tr>
						</table>
					</div>



					<!-- Panel: Analysis JPEG -->
					<div class="panel panel-default">
						<div class="panel-heading"><span class="glyphicon glyphicon-sort-by-attributes"></span> Analysis details</div>
						<div class="panel-body">
							<a href="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_analysis.jpeg";?>"><img style="width:100%;" src="<?php echo URL_UPLOADS . "/" . $GINKGO_USER_ID . "/" . $CURR_CELL . "_analysis.jpeg";?>"></a>
						</div>
					</div>


					<!-- Buttons: back or next -->
					<div id="results-navigation2">
						<hr style="height:5px;border:none;background-color:#CCC;" /><br/>
						<div style="float:left"><a class="btn btn-lg btn-primary" href="?q=results/<?php echo $GINKGO_USER_ID; ?>"><span class="glyphicon glyphicon-chevron-left"></span> Back to tree </a></div>
					</div>
				</div>

				<div class="col-lg-4">
					<?php echo $PANEL_LATER; ?>
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


	  <!-- Ginkgo
	  ================================================== -->
		<script language="javascript">
		var ginkgo_user_id = "<?php echo $GINKGO_USER_ID; ?>";

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
						//alert("Error: Please use a .txt extension for FACS files/custom bin files")
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

						$.post("?q=dashboard/" + ginkgo_user_id, {
								// General
								'analyze':	1,
								'cells[]':	arrCells,
								'email':		email,

								// Methods
								'binMeth':	$('#param-bins-type').val() + $('#param-bins-value').val(),
								'segMeth':	$('#param-segmentation').val(),
								'clustMeth':$('#param-clustering').val(),
								'distMeth':	$('#param-distance').val(),

								// FACS file
								'f':				f,
								'facs':			facs,

								// Plot gene locations
								'g':				0,
								'geneList':	'',

								// User-specified bin file
								'b':				b,
								'binList':	bins,
								
								// User specified binning segmentation file
								'segMethCustom': segmentation,
								
								// Genome
								'chosen_genome': $('#param-genome').val(),
							},
							// If get response
							function(data) {
								if(data == "OK")
									window.location = "<?php echo URL_ROOT . "/?q=results/"; ?>" + ginkgo_user_id;
								else
									alert(data)
							}
						);
					}
			  }
			});


			
			//
			
		});

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
				step				= xmlFile.getElementsByTagName("step")[0].childNodes[0].nodeValue;
				processing	= xmlFile.getElementsByTagName("processingfile")[0].childNodes[0].nodeValue;
				percentdone	= xmlFile.getElementsByTagName("percentdone")[0].childNodes[0].nodeValue;
				tree				= xmlFile.getElementsByTagName("tree")[0].childNodes[0];
				if(typeof tree != 'undefined')
					tree = tree.nodeValue;

				// Determine progress status output
				if(step == "1")
					desc = "Mapping reads to bins";
				else if(step == "2")
					desc = "Computing quality control statistics";
				else if(step == "3")
					desc = "Processing and clustering samples";
				else if(step > 3)
					desc = "Annotating specified genes";

				denominator = 3;
				// Keep in mind: step > 3 is for drawing genes on plots
				if(step > denominator)
					denominator = step;
				overallDone = Math.round(100*(step-1+percentdone/100)/denominator);
				$("#results-progress").width(overallDone + "%");
				Tinycon.setBubble(overallDone);

				processingMsg = "(" + processing.replace("_", " ") + ")";
				$("#results-status-text").html(overallDone + "% complete.<br><small style='color:#999'>Step " + step + ": " + percentdone + "%" + " " + desc + "... " + processingMsg + "</small>");

				// Update progress bar % completed
				if(percentdone > 100)
					percentdone = 100;

				// Load tree
				if(typeof tree != 'undefined')
					drawTree(tree);

				// When we're done with the analysis, stop getting progress continually
				if((step >= 3 && percentdone >= 100) || typeof step == 'undefined')
				{
					// Remove auto-update timer
					clearInterval(ginkgo_progress);

					// Fix UI
					$(".progress").hide();
					$("#results-status-text").html("Analysis complete!");
					$("#results-navigation").show();
					$("#results-download").show();
					$("#results-heatmaps").show();
					$("#results-search-genes").show();
				}

			});

			// Load Quality Assessment file (only runs if file exists)
			$.get("<?php echo URL_UPLOADS; ?>/" + ginkgo_user_id + "/SegStats", function(qaFile)
			{
				// Turn string into array of lines
				lineNb = 0;
				table  = '';
				allLines = qaFile.split("\n");

				omg = [[], [], []];
				for(var line in allLines)
				{
					lineNb++;
					if(lineNb == 1)
						continue;

					arrLine = allLines[line].split("\t");
					if(arrLine.length < 12)
						continue;

					cell  = arrLine[0].replace(/"/g, '');
					score = arrLine[11].replace(/"/g, '');

					meanBinCount = ""
					if(arrLine[3].replace(/"/g, '') < 25)
						meanBinCount = '<span class="glyphicon glyphicon-remove-circle"></span>'

					indexOfDispersion = ""
					if(arrLine[5].replace(/"/g, '') > 1)
						indexOfDispersion = '<span class="glyphicon glyphicon-remove-circle"></span>'

					scoreClass = "active"
					icon  = ""
					if(score == 2) {
						scoreClass = "danger";
						icon = "glyphicon-exclamation-sign"
						scoreMessage = "This file suffers from extreme coverage issues.  Proceed carefully or consider removing file from the analysis.";
					} else if(score == 1) {
						scoreClass = "warning";
						icon = "glyphicon-info-sign"
						scoreMessage = "This file suffers from moderate coverage issues. Proceed carefully.";
					} else if(score == 0) {
						scoreClass = "success";
						icon = "glyphicon-ok-sign"
						scoreMessage = "No QA issues detected";
					}
					newLine = '<tr class="' + scoreClass + '"><td width="5%" class="active" style="text-align:center"><span class="glyphicon ' + icon + '"></span></td><td width="20%" style="text-align:center">' + cell + '</td><td width="20%" style="text-align:center">' + meanBinCount + '</td><td width="20%" style="text-align:center">' + indexOfDispersion + '</td><td width="55%">' + scoreMessage + '</td></tr>';
					//table += newLine;
					
					
					omg[score].push(newLine);
				}
				
				for(i in omg)
					for(j in omg[i])
						table += omg[i][j];

				table += "</tbody>";

				// Hide loading text; show table
				$("#results-QA-loadingTxt").hide();
				$("#results-QA-table").show();
				$("#results-QA-table").html(table);
			});
		}
		
		// -------------------------------------------------------------------------
		// -- Load a tree ----------------------------------------------------------
		// -------------------------------------------------------------------------
		function drawTree(treeFile, outputXML)
		{
			Tinycon.setBubble(0);
			$.get("<?php echo URL_UPLOADS; ?>/" + ginkgo_user_id + "/" + treeFile, function(xmlFile)
			{
				// Debug
				if(outputXML == true)
					console.log( (new XMLSerializer()).serializeToString(xmlFile) );

				//
				if(xmlFile == "")
					return;

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
					//value.childNodes[0].nodeValue = 'Cell # ' + cellId; // if do that, screw up assigning cell-ID below

					// Add 'annotation' node next to 'name'
					annotationNode	= currElement.parentNode.appendChild(xmlFile.createElement("annotation"));
					annotationNode
						.appendChild(xmlFile.createElement("desc"))
						.appendChild(xmlFile.createTextNode("<img width='290' src='<?php echo URL_UPLOADS; ?>/" + ginkgo_user_id + "/" + cellId + "_result.jpeg'>" + cellId));
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
				treeHeight = 500;
				treeWidth  = 500;
				ginkgo_phylocanvas = new Smits.PhyloCanvas(dataObject, 'svgCanvas', treeWidth, treeHeight); //, 'circular'

				// Resize SVG to fit
			    var c = document.getElementsByTagName("svg");
				var rec = c[0].getBoundingClientRect();
				console.log("width: "+rec.width);
				console.log("height: "+rec.height);
				if(treeHeight < rec.height)
					$("svg").css("height", rec.height + "px");


				//alert( $("svg").getAttribute("height") )

				// Init unitip (see unitip.css to change tip size)
				init();
			});
		}
		</script>

	</body>
</html>
