<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Single Cell Analysis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- CSS -->
    <link href="includes/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="includes/unitip/unitip.css">
	<script type="text/javascript" src="includes/unitip/unitip.js"></script>
    <style type="text/css">
      body {
        padding-top: 60px;
        padding-bottom: 0px;
      }
      .sidebar-nav {
        padding: 9px 0;
      }
svgCanvas {
    fill: none;
    pointer-events: all;
}
    </style>
    <link href="/includes/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
  </head>
  <body>

    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container-fluid">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="/singlecell/">Single Cell Analyzer</a>
          <div class="nav-collapse collapse">

			<ul class="nav pull-right">
		        <li class="pull-right"><a href="javascript:void(0);" class="pull-right">FAQ</a></li>
		        <li class="pull-right"><a href="javascript:void(0);" class="pull-right">About</a></li>
			</ul>
			<!--
            <ul class="nav">
              <li class="active"><a href="#">Home</a></li>
              <li><a href="#about">About</a></li>
              <li><a href="#contact">Contact</a></li>
            </ul>
            -->
          </div>
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span3">
          <div class="well sidebar-nav">
            <ul class="nav nav-list">
            		<!-- STEP 1 -->
				<li class="nav-header"><h5>Step 1: Specify .bed files</h5></li>
				<div class="alert alert-info">Specify the .bed files to use for the analysis, either by uploading files or entering the URL of an FTP server.</div>
				<div class="tabbable">
					<ul class="nav nav-tabs">
						<li class="active"><a href="#tab1" data-toggle="tab">Upload files</a></li>
						<li><a href="#tab2" data-toggle="tab">FTP server</a></li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="tab1">
							<iframe id="upload-iframe" style="width:97%; height:250px; border:0;" src="./includes/fileupload/?user_id=esYyVyU7GZUKxwYH2tNN"></iframe>
							<a id="btn-upload-files" class="btn btn-info" href="javascript:void(0);">Next Step &raquo;</a>
						</div>
						<div class="tab-pane" id="tab2">
							<p><input id="input-upload-url" type="text" placeholder="Enter FTP server here" style="width:94%" value=""></p>

							<br style="clear:both;" />
							<div>
								<a id="btn-upload-files-ftp" class="btn btn-info" href="javascript:void(0);">Add Files</a>
								<span id="loading-upload" style="margin-left:30px;"><img src="http://hub.technophilicmag.com/admin/css/webcluster/images/loading.gif"></span>
							</div>
						</div>
					</div>
					<br />
				</div>
			</ul>
		</div>
	<div id="step-2" style="display:none">
		<div class="well sidebar-nav">
            <ul class="nav nav-list">
            		<!-- STEP 2 -->
				<li class="nav-header"><h5>Step 2: Choose the files to consider</h5></li>
				<div class="alert alert-info">Here are the files that were uploaded successfully. Choose those you'd like to use in the analysis.</div>
				<div id="list-files"></div>
			</ul>
		</div>
		<!-- <div class="well sidebar-nav">
            <ul class="nav nav-list">
				<li class="nav-header"><h5>Step 3: Start the analysis</h5></li>
				<div class="alert alert-info">That's it, just click "Start Analysis" to get started!</div>
			</ul>
		</div>-->

		<div style="text-align:center">
			<a id="btn-start-analysis" class="btn btn-primary btn-large" href="javascript:void(0);" onClick="javascript:startAnalysis();">Start Analysis</a>
			<br /><br />
		</div>
	</div>
		<div class="well sidebar-nav">
            <ul class="nav nav-list">
				<li class="nav-header"><a href="javascript:void(0);" onClick="$('#params').toggle()">Optional: Choose your parameters</a></li>
				<div id="params">
					<br />
					<div class="alert alert-error">If you know what you're doing, you can change the parameters of the analysis:</div>
					<li>
						<strong>Binning options</strong><br />
						<div style="margin-left:0px; font-size:11px;">
							Use <select id="param-bins-type" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
								<option value="0">fixed</option>
								<option value="4">variable</option>
							</select> bin size of <select id="param-bins-value" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
								<option value="0">10kb</option>
								<option value="1">25kb</option>
								<option value="2">40kb</option>
								<option value="3">50kb</option>
							</select> size.
						</div>
						<br />

						<strong>Segmentation</strong><br />
						<div style="font-size:11px;">
							Use <select id="param-segmentation" class="input-medium" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
								<option value="0">bin count variability</option>
								<option value="1">GC content per bin</option>
							</select> to segment.
						</div>
						<br />

						<strong>Clustering</strong><br />
						<div style="font-size:11px;">
							Use <select id="param-clustering" class="input-small" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
								<option value="0">single</option>
								<option value="1">complete</option>
								<option value="2">average</option>
								<option value="3">ward</option>
							</select> clustering.
						</div>
						<br />

						<strong>Distance metric</strong><br />
						<div style="font-size:11px;">
							Use <select id="param-distance" class="input-small" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
								<option value="0">Euclidean</option>
								<option value="1">maximum</option>
								<option value="2">Manhattan</option>
								<option value="3">Canberra</option>
								<option value="4">binary</option>
								<option value="5">Minkowski</option>
							</select> distance.
						</div>
					</li>
				</div>
            </ul>
          </div><!--/.well -->



        </div><!--/span-->

        <div class="span9">
          <div class="hero-unit">
			<div style="margin-top:-20px;border:0px solid red;">
				<div style="float:left"><h2 style="margin-top:-3px;">Results <small><br />Access your results from anywhere at <input style="margin-top:7px; color:#999; background:transparent;" type="text" class="input-xxlarge" id="permalink-url" value="#"></small></h2></div>

				<div style="float:right; margin-left:70px;">
					<div class="btn-group">
						<a id="btn-export-tree-txt" class="btn-export btn btn-primary dropdown-toggle disabled" data-toggle="dropdown" href="javascript:void(0);">
						Download tree representation
						<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
						      <li><a id="download-tree-newick" href="#" target="_blank">Newick</a></li>
						      <li><a id="download-tree-xml" href="#" target="_blank">PhyloXML</a></li>
						</ul>
					</div>

					<!-- <div class="btn-group">
						<a id="btn-export-tree-img" class="btn-export btn btn-primary dropdown-toggle disabled" data-toggle="dropdown" href="javascript:void(0);">
						Save image as...
						<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
						      <li><a href="javascript:void(0);">JPG</a></li>
						      <li><a href="javascript:void(0);">PDF</a></li>
						      <li><a href="javascript:void(0);">SVG</a></li>
						</ul>
					</div> -->

					<!-- <div class="btn-group">
						<a id="btn-export-tree-img" class="btn-export btn btn-primary disabled" data-toggle="dropdown" href="#" onclick="alert('Permalink: ' + window.location)">
						Save results for later
						</a>
					</div>-->
				</div>

				<div id="dashboard-status-text" style="clear:both;margin-left: 0">
					Ready.
				</div>
			</div>

			<br style="clear:both;" /><br />

			<!-- DASHBOARD: Progress bar -->
			<div class="row-fluid">
				<div id="dashboard-loading" class="row-fluid">
					<div id="bar-container" class="progress progress-striped">
						<div class="bar" style="width: 0%;"></div>
					</div>
				</div>

				<!-- <div id="dashboard-results" class="row-fluid" style="position:relative;">
					
				</div>-->

				<div id="svgCanvas" class="row-fluid"><!-- class="span8" if dashboard=results is there below not above -->
				</div>

				<!-- DASHBOARD: Done processing -->
				<!--<div id="dashboard-results" class="row-fluid">
					<div class="tabbable tabs-left">
						<ul class="nav nav-tabs">
							<li class="active"><a id="link-1" href="#cell-1" data-toggle="tab">Cell 1</a></li>
							<li><a id="link-2" href="#cell-2" data-toggle="tab">Cell 2</a></li>
							<li><a id="link-3" href="#cell-3" data-toggle="tab">Cell 3</a></li>
						</ul>
						<div class="tab-content">
							<div class="tab-pane active" id="cell-1">
								<p>OMG CELL 1</p>
							</div>
							<div class="tab-pane" id="cell-2">
								<p>CELL 2</p>
							</div>
							<div class="tab-pane" id="cell-3">
								<p>CELL 3</p>
							</div>
						</div>
					</div>
				</div>-->
			</div>
          </div>
      </div><!--/row-->
	<!--
      <footer>
        <p>&copy; Company 2012</p>
      </footer>
	-->

    </div><!--/.fluid-container-->



    <!-- Javascript Files
    		 jsPhyloSVG documentation: http://www.jsphylosvg.com/documentation.php#2.2
    ================================================== -->
	<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
    <script src="includes/bootstrap/js/bootstrap.js"></script>

    <script type="text/javascript" src="includes/jsphylosvg/raphael-min.js" ></script>
    <script type="text/javascript" src="includes/jsphylosvg/jsphylosvg.js"></script> <!-- -min -->

	<script type="text/javascript">
	//
	var _ssa_user_id				= "esYyVyU7GZUKxwYH2tNN";
//	var _ssa_nb_files			= 5;
	//
	var _ssa_is_done				= false;
	var _ssa_is_init				= false;
	//
	var _ssa_interval;
	// 
	var _ssa_options_bins_type	= 0;
	var _ssa_options_bins_size	= 0;
	var _ssa_options_segmentation=0;
	var _ssa_options_clustering	= 0;
	var _ssa_options_distance	= 0;
	//
	var _ssa_phylocanvas			= "";
	// -----------------------------------------------------------------------------------
	// Initialization
	// -----------------------------------------------------------------------------------
	$(document).ready(function()
	{
		bootstrap();

		// -----------------------------------------------------------------------------------
		// Load previously analyzed file
		// TODO: Load results
		// -----------------------------------------------------------------------------------
		if(window.location.hash)
		{
			_ssa_user_id = window.location.hash.replace("#!/", "");
			showFinalTree();
		}
		else
			window.location = window.location + "#!/" + generateID();

		_ssa_user_id = window.location.hash.replace("#!/", "");
		$("#upload-iframe").attr("src", "./includes/fileupload/?user_id=" + _ssa_user_id);
		//$("#permalink-url").attr("href", window.location);
		$("#permalink-url").val(window.location.href.replace("http://", "") + _ssa_user_id); //window.location.replace("http://", "").replace("www.", "")
		//alert(_ssa_user_id)

		// -----------------------------------------------------------------------------------
		// Upload files
		// -----------------------------------------------------------------------------------
		$("#btn-upload-files-ftp").click(function()
		{
			// Show loading animation
			$("#loading-upload").show();

			// Disable 'Add Files' & 'Start Analysis' buttons
			$("#btn-upload-files-ftp, #btn-start-analysis").addClass("disabled");

			// Upload the files
			$.post("query.php", { query:"upload-ftp", "params[id]":_ssa_user_id, "params[ftp]":$("#input-upload-url").val() },
			function(data)
			{
				// Done so hide loading animation
				$("#loading-upload").hide();

				// Enable 'Add Files' / 'Start Analysis' buttons
				$("#btn-upload-files-ftp, #btn-start-analysis").removeClass("disabled");

				// Update list of available files
				$("#list-files").append(data);
				$("#step-2").show();
			});
		});
		$("#btn-upload-files").click(function()
		{
			// List all uploaded files
			listMyFiles();

			// Enable 'Add Files' / 'Start Analysis' buttons
			$("#btn-upload-files, #btn-start-analysis").removeClass("disabled");
			$("#step-2").show();
		});

		// -----------------------------------------------------------------------------------
		// List all available files
		// -----------------------------------------------------------------------------------
		function listMyFiles() {
			$.get("query.php", { query:"list-files", user_id:_ssa_user_id }, function(html) {
				$("#list-files").html(html);
			});
		}
	});

	// -----------------------------------------------------------------------------------
	// Generate random ID
	// TODO: make sure it's not already used
	// http://stackoverflow.com/questions/1349404/generate-a-string-of-5-random-characters-in-javascript
	// -----------------------------------------------------------------------------------
	function generateID()
	{
		var text = "";
		var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

		for( var i=0; i < 20; i++ )
		    text += possible.charAt(Math.floor(Math.random() * possible.length));

		return text;
	}

	// -----------------------------------------------------------------------------------
	// Bootstrap
	// -----------------------------------------------------------------------------------
	function bootstrap()
	{
		// Disable buttons
		$(".btn-export").addClass("disabled");
		$("#btn-start-analysis").addClass("disabled").css("width", "90%");

		// Hide/show some divs
		//, #param-bins-fixed-value
		$("#loading-upload, #params, #dashboard-results").hide();
		$("#dashboard-loading").show();
	}

	// -----------------------------------------------------------------------------------
	// Launch analysis (once files uploaded)
	// -----------------------------------------------------------------------------------
	function startAnalysis()
	{
		// Go to top
		$("html, body").animate({ scrollTop: 0 }, "slow");
		
		//$("#dashboard-results").hide();
		$("#svgCanvas").html(""); _ssa_phylocanvas = "";
		$("#dashboard-loading").show();
		$(".btn-export").addClass("disabled");
	
		/* 1.1: Get a list of all selected files */
		var selected = new Array();
		$('#list-files input:checked').each(function() {
			selected.push($(this).attr('id'));
		});

		/* 1.2: If list empty, alert(select at least one file!) */
		if(selected.length == 0) {
			alert("Please choose at least one file to analyze.");
			return;
		}

		/* 1.3: Did the user choose new parameters? */
		var _ssa_options_new_bins		= 0;
		var _ssa_options_new_segmentation=0;
		var _ssa_options_new_clustering	= 0;
		var _ssa_options_new_distance	= 0;

		// New binning parameters? (bin type / size)
		if(_ssa_options_bins_type != $('#param-bins-type').val() || _ssa_options_bins_size != $('#param-bins-size').val())
			_ssa_options_new_bins = 1;
		// New segmentation parameters?
		if(_ssa_options_segmentation != $('#param-segmentation').val())
			_ssa_options_new_segmentation = 1;
		// New clustering parameters?
		if(_ssa_options_clustering != $('#param-clustering').val())
			_ssa_options_new_clustering = 1;
		// New distance parameters?
		if(_ssa_options_distance != $('#param-distance').val())
			_ssa_options_new_distance = 1;

		// Set new parameters
		_ssa_options_bins_type		= $('#param-bins-type').val();
		_ssa_options_bins_size		= $('#param-bins-size').val();
		_ssa_options_segmentation	= $('#param-segmentation').val();
		_ssa_options_clustering		= $('#param-clustering').val();
		_ssa_options_distance		= $('#param-distance').val();

		/* 2: Start the analysis with the list of files */
		$("#bar-container").css("width","0%");
		$("#bar-container").addClass("active");
		$("#btn-start-analysis").addClass("disabled");
		$("#btn-upload-files").addClass("disabled");
///TODO
		$("#bar-container").css("width","100%");
///TODO

		//
		//$.get("query.php", { query:"analyze", user_id:_ssa_user_id, files:selected, new_binning:new_binning_bool, bin_size:_ssa_options_bins, new_tree:"0" }, function(data){
		$.get("query.php", {
				query:				"analyze",
				user_id:				_ssa_user_id,
				files:				selected,

				param_bins_type:		_ssa_options_bins_type,
				param_bins_size:		_ssa_options_bins_size,
				param_segmentation:	_ssa_options_segmentation,
				param_clustering:	_ssa_options_clustering,
				param_distance:		_ssa_options_distance,

				new_bins:			_ssa_options_new_bins,
				new_segmentation:	_ssa_options_new_segmentation,
				new_clustering:		_ssa_options_new_clustering,
				new_distance:		_ssa_options_new_distance
			}, function(data){
				//alert(data);
				// Once all steps are done
				$("#btn-start-analysis").removeClass("disabled");
				$("#btn-upload-files").removeClass("disabled");	
				//$("#svgCanvas").show();
			}
		);

		/* 3: Start retrieving status */
		$("#dashboard-status-text").text("Processing old files...");
		_ssa_interval = setInterval( "getAnalysisStatus('old')", 1500 );
	}

	// -----------------------------------------------------------------------------------
	// Retrieve analysis status every once in a while (function called by setInterval())
	// -----------------------------------------------------------------------------------
	var prevStep = -1;
	function getAnalysisStatus(whichXML)
	{
		// Load status.xml
		$.get("data/" + _ssa_user_id + "/thumbnail/status_" + whichXML + ".xml", function(xmlFile)
		{
			// document.getElementById("to").innerHTML
			step = xmlFile.getElementsByTagName("step")[0].childNodes[0].nodeValue;
			processing = xmlFile.getElementsByTagName("processingfile")[0].childNodes[0].nodeValue;
			percentdone = xmlFile.getElementsByTagName("percentdone")[0].childNodes[0].nodeValue;
			tree = xmlFile.getElementsByTagName("tree")[0].childNodes[0].nodeValue;

			if(step == "1")
				desc = "Mapping reads to bins";
			else if(step == "2")
				desc = "Computing statistics for segmentation";
			else if(step == "3")
				desc = "Clustering";
			else if(step == "4")
				desc = "Finalizing";

			processingMsg = "(" + processing.replace("_", " ") + ")";
			$("#dashboard-status-text").html("Step " + step + ": " + percentdone + "%" + " <small style='color:#999'>" + desc + "... " + processingMsg + "<small>");

			if(percentdone > 100)
				percentdone = 100;
			$(".bar").width((100*(step-1+percentdone/100)/4) + "%");

			// Get current tree
			if(tree != "0")
				loadTree(tree);

			// When we're done with the analysis, stop getting progress continually
			if((step == 4 && percentdone >= 100) || typeof step == 'undefined')
			{
				if(whichXML == "new")
				{
					clearInterval(_ssa_interval);
					_ssa_is_done = false; showFinalTree();
					_ssa_is_done = true;
					clearInterval(_ssa_interval);

					$(".btn-export").removeClass("disabled");
					$("#bar-container").removeClass("active");
					$("#dashboard-status-text").text("Ready. Click on the tree labels to see the copy number profile.");
				}
				else
				{
					clearInterval(_ssa_interval);
					$('.bar').toggleClass('active');
					$(".bar").width("0%");
					$('.bar').toggleClass('active');
					$("#dashboard-status-text").text("Done with old files. Now processing new files...");
					_ssa_interval = setInterval( "getAnalysisStatus('new')", 1000 );
				}
			}
		});

		prevStep = step;
	}

	// -----------------------------------------------------------------------------------
	// Load a tree
	// -----------------------------------------------------------------------------------
	function loadTree(treeFile, outputXML, dendrogram)
	{
		$.get("data/" + _ssa_user_id + "/" + treeFile, function(xmlFile)
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
					.appendChild(xmlFile.createTextNode("<img width='500' src='./data/" + _ssa_user_id + "/" + cellId + ".jpeg'>" + cellId));
				annotationNode
					.appendChild(xmlFile.createElement("uri"))
					.appendChild(xmlFile.createTextNode("javascript:showProfile('" + cellId + "')"));
			});

			$("#svgCanvas").html("");
			_ssa_phylocanvas = "";
			dataObject = "";

			// Define tree and size (based on current window size!)
			var dataObject = { xml: xmlFile, fileSource: true };
			treeHeight = 500;
			treeWidth  = 500;
			// Show tree
			if(dendrogram)
				_ssa_phylocanvas = new Smits.PhyloCanvas(dataObject, 'svgCanvas', treeWidth, treeHeight);
			else
				_ssa_phylocanvas = new Smits.PhyloCanvas(dataObject, 'svgCanvas', treeWidth, treeHeight, 'circular');

			// Init unitip (see unitip.css to change tip size)
			init();

			// Show results
			$("#dashboard-results").show();
			_ssa_is_init = true;
		});
	}

	// -----------------------------------------------------------------------------------
	// Show a cell's copy number profile
	// -----------------------------------------------------------------------------------
	function showProfile(cellId)
	{
		window.open('./data/' + _ssa_user_id + '/' + cellId + '.jpeg', 'Cell <' + cellId + '> Copy-number profile');
	}

	// -----------------------------------------------------------------------------------
	// Show final tree and export options
	// -----------------------------------------------------------------------------------
	function showFinalTree()
	{
		/** Load phyloXML tree **/
		loadTree('hist.xml', false, true);

		/** Show download options **/
		$("#download-tree-newick").attr("href", "./data/" + _ssa_user_id + "/hist.newick");
		$("#download-tree-xml").attr("href", "./data/" + _ssa_user_id + "/hist.xml");

		/** Enable export buttons **/
		$(".btn-export").removeClass("disabled");
		$("#dashboard-results").show();
		$("#dashboard-loading").hide();
	}

	</script>

  </body>
</html>
