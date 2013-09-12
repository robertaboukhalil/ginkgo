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
// 
// =============================================================================

// Configuration
include "bootstrap.php";

// Parse user query
$query = explode("/", $_GET['q']);
$userID = $query[1];
$analysisID = $query[2];
if(!$analysisID)
	$analysisID = generateID(10);

// Steps >= 1
if($query[0] == "dashboard")
{
	define('SHOW_HOME', false);
  define('SHOW_DASHBOARD', true);
  $MY_CELLS = getMyFiles($userID);
}
// Step 0
else
{
	// Generate user ID (source: http://stackoverflow.com/questions/4356289/php-random-string-generator)
	if(!$userID)
	{
		$userID = generateID(20);
		@mkdir(DIR_UPLOADS . '/' . $userID);
	}

  //
  define('SHOW_DASHBOARD', false);
	define('SHOW_HOME', true);
}

if(isset($_POST['analyze']))
{
	// Sanitize user input (see bootstrap.php)
	array_walk_recursive($_POST, 'sanitize');
	$user = $userID; sanitize($user);

	// Create list-of-cells-to-analyze file	
	$cells = '';
	foreach($_POST['cells'] as $cell)
		$cells .= str_replace("'", "", $cell) . "\n";
	file_put_contents(DIR_UPLOADS . '/' . $userID . '/list', $cells);

	// Create config file
	$config = '#!/bin/bash' . "\n";
	$config.= 'user=' . $user . "\n";
	$config.= 'email=' . $_POST['email'] . "\n";
	$config.= 'permalink=\'' . URL_ROOT . '/?q=dashboard/' . str_replace("'", "", $user) . "'\n";

	$config.= 'segMeth=' . $_POST['segMeth'] . "\n";
	$config.= 'binMeth=' . $_POST['binMeth'] . "\n";
	$config.= 'clustMeth=' . $_POST['clustMeth'] . "\n";
	$config.= 'distMeth=' . $_POST['distMeth'] . "\n";

	$config.= 'b=' . $_POST['b'] . "\n";
	$config.= 'binList=' . $_POST['binList'] . "\n";
	$config.= 'f=' . $_POST['f'] . "\n";
	$config.= 'facs=' . $_POST['facs'] . "\n";
	$config.= 'g=' . $_POST['g'] . "\n";
	$config.= 'geneList=' . $_POST['geneList'] . "\n";
	$config.= 'chosen_genome=' . $_POST['chosen_genome'] . "\n";

	$config.= 'init=1' . "\n";
	$config.= 'process=1' . "\n";
	$config.= 'fix=0' . "\n";

	file_put_contents(DIR_UPLOADS . '/' . $userID . '/config', $config);

	// Start analysis
	#echo "\n$config\n\n";
	#print_r($_POST);
	$cmd = "./scripts/analyze $userID";
	session_regenerate_id(TRUE);	
	$handle = popen($cmd, 'r');
	$out = stream_get_contents($handle);
	pclose($handle);

	echo $out;

	// Refresh page to show progress
	#header("Location: ?q=dashboard/" . $userID);
	exit;
}

$_SESSION["user_id"] = $userID;
$_SESSION["analysis_id"] = $analysisID;

// Permalink
$permalink = URL_ROOT . '?q=/' . $userID;
$PANEL_LATER = <<<PANEL
			<!-- Panel: Save for later -->
			<div class="panel panel-primary">
				<div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-time"></span> View analysis later</h3></div>
				<div class="panel-body">
					Access your results later at the following address:<br/><br/>
					<textarea class="input-sm" id="permalink">{$permalink}</textarea>
				</div>
			</div>
PANEL;

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
		td          { vertical-align:middle !important; }
		code input  { border:none; color:#c7254e; background-color:#f9f2f4; width:100%; }
		.jumbotron  { padding:50px 30px 15px 30px; }
		.glyphicon  { vertical-align:top; }
		.badge      { vertical-align:top; margin-top:5px; }
		#permalink  { border:1px solid #DDD; width:100%; color:#666; background:transparent; font-family:"courier"; resize:none; height:50px; }
		#status-analysis	{ display:none; }
	</style>

	  <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
	  <!--[if lt IE 9]>
	    <script src="../../assets/js/html5shiv.js"></script>
	    <script src="../../assets/js/respond.min.js"></script>
	  <![endif]-->
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
					<a class="navbar-brand" href="?q=home/<?php echo $userID; ?>"><span class="glyphicon glyphicon-tree-deciduous"></span> Ginkgo</a>
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
					<?php if(SHOW_HOME): ?>
					A web tool for analyzing single-cell sequencing data.
					<?php elseif(SHOW_DASHBOARD): ?>
					<div class="status-box" id="status-upload">Your files are uploaded. Now let's do some analysis:</div>
					<div class="status-box" id="status-analysis">
						Processing...<br />
						<div class="progress progress-striped"><div class="progress-bar" role="progressbar" style="width: 45%"></div></div>
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
			<?php if(SHOW_HOME): ?>
			<!-- Upload files -->
			<div class="row" style="height:100%;">
				<div class="col-lg-8">
					<h3 style="margin-top:-5px;"><span class="badge">STEP 0</span> Upload your .bed files <small><strong>(We accept *.bed, *.zip, *.tar, *.tar.gz and *.tgz)</strong></small></h3>
					<iframe id="upload-iframe" style="width:100%; height:100%; border:0;" src="includes/fileupload/?user_id=<?php echo $userID; ?>"></iframe>
					<p>
						<div style="float:right">
							<a class="btn btn-lg btn-primary" href="?q=dashboard/<?php echo $userID; ?>">Next step <span class="glyphicon glyphicon-chevron-right"></span></a>
						</div>
					</p>
				</div>
				<div class="col-lg-4">

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
			<?php elseif(SHOW_DASHBOARD): ?>
			<!-- Dashboard -->
			<div class="row">
				<div id="dashboard" class="col-lg-8">
					<!-- Choose cells of interest -->
					<h3 style="margin-top:-5px;"><span class="badge">STEP 1</span> Choose cells for analysis</h3>
					<div id="params-cells">
						<?php foreach($MY_CELLS as $currCell): ?>
				    <label><div class="input-group" style="margin:20px;"><span class="input-group-addon"><input type="checkbox" name="dashboard_cells[]" value="<?php echo $currCell; ?>"></span><span class="form-control"><?php echo $currCell; ?></span></div></label>
						<?php endforeach; ?>
						<br/>
						<button id="dashboard-toggle-cells" class="btn btn-info" style="margin:20px;">Select all cells</button>
					</div>

					<!-- Get informed by email when done? -->
					<br/><h3 style="margin-top:-5px;"><span class="badge">STEP 2</span> E-mail notification <small></small></h3>
					<div id="params-email" style="margin:20px;">
						<p>If you want to be notified once the analysis is done, enter your e-mail here:<br/></p>
						<div class="input-group">
							<span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
							<input id="email" class="form-control" type="text" placeholder="my@email.com">
						</div>
					</div>
					<br/><br/>

					<!-- Buttons: back or next -->
					<div style="float:left"><a class="btn btn-lg btn-primary" href="?q=/<?php echo $userID; ?>"><span class="glyphicon glyphicon-chevron-left"></span> Manage Files </a></div>
					<div style="float:right"><a id="analyze" class="btn btn-lg btn-primary" href="javascript:void(0);">Start Analysis <span class="glyphicon glyphicon-chevron-right"></span></a></div><br/><br/><br/>
					<hr style="height:5px;border:none;background-color:#CCC;" /><br/>

					<!-- Set parameters -->
					<h3 style="margin-top:-5px;"><span class="badge">OPTIONAL</span> <a href="#parameters" onClick="javascript:$('#params-table').toggle();">Analysis parameters</a></h3>
					<table class="table table-striped" id="params-table">
						<tbody>
							<tr>
								<td>Binning Options</td>
								<td>
									Use a <select id="param-bins-type" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="fixed_">fixed</option>
									<option value="variable_">variable</option>
									</select> bin size of <select id="param-bins-value" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
									<option value="10000">10kb</option>
									<option value="25000">25kb</option>
									<option value="40000">40kb</option>
									<option value="50000">50kb</option>
									</select> size.
								</td>
							</tr>
							<tr>
								<td>Segmentation</td>
								<td>Use <select id="param-segmentation" class="input-medium" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
								<option value="0">bin count variability</option>
								<option value="1">GC content per bin</option>
								</select> to segment.</td>
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
				</div>

				<div class="col-lg-4">
					<!-- Panel: View previous analysis results -->
					<div class="panel panel-primary">
						<div class="panel-heading"><h3 class="panel-title"><span class="glyphicon glyphicon-stats"></span> Previous analysis results</h3></div>
						<div class="panel-body">
							[TODO: list previous analysis reports]
						</div>
					</div>
					<?php echo $PANEL_LATER; ?>
				</div>
			</div>
			<?php endif; ?>

		</div> <!-- /container -->


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
		$("#permalink").focus(function() { $(this).select(); } );
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


	  <!-- Ginkgo
	  ================================================== -->
		<script language="javascript">
		var ginkgo_user_id = "<?php echo $userID; ?>";
		
		// -- On page load ---------------------------------------------------------
		$(document).ready(function(){
			<?php if(SHOW_HOME): ?>
			// Set initial size of upload 
			$(window).resize();

			<?php elseif(SHOW_DASHBOARD): ?>
			// Hide parameters table and analysis status
			$("#params-table").hide();
			$("#status-analysis").hide();
			// Show upload status
			$("#status-upload").show();
			<?php endif; ?>				
		});

		// -- On page resize -------------------------------------------------------
		$(window).resize(function() {
	    $(".col-lg-8").height(window.innerHeight - $(".navbar").height() - $(".jumbotron").height() - 200 );
		});

		// -- Dashboard ------------------------------------------------------------
		// Toggle b/w select all cells and select none
		$('#dashboard-toggle-cells').click(function() {
			$('#params-cells input[type="checkbox"]').prop('checked',!$('input[type="checkbox"]').prop('checked'));
		});
		
		// -- Launch analysis ------------------------------------------------------
		$('#analyze').click(function() {
			// -- Get list of cells of interest
			arrCells = [];
			$("#params-cells :checked").each(function() { arrCells.push($(this).val()); });

			// -- Get email
			email = $('#email').val();

			// -- Submit query
			$.post("?q=dashboard/<?php echo $userID; ?>", {
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
					'f':				0,
					'facs':			'',

					// Plot gene locations
					'g':				0,
					'geneList':	'',

					// User-specified bin file
					'b':				0,
					'binList':	'',
					
					// Genome
					'chosen_genome': 'hg18',
				},
				function(data)
				{
					alert(data);
				}
			);
		});

		</script>

	</body>
</html>
