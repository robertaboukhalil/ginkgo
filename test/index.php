<?php

// Configuration
error_reporting(E_ALL);
session_start();

// Get user's query
$query = $_GET['q'];
if($query == "dashboard")
    define('SHOW_DASHBOARD', true);
else
    define('SHOW_DASHBOARD', false);

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
    #permalink  { border:1px solid #DDD; width:100%; text-align:center; color:#666; background:transparent; font-family:"courier"; }
    .jumbotron  { padding:50px 30px 15px 30px; }
    .glyphicon  { vertical-align:top; }
    .badge      { vertical-align:top; margin-top:5px; }
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
          <a class="navbar-brand" href="."><span class="glyphicon glyphicon-tree-deciduous"></span> Ginkgo</a>
        </div>
        <div class="navbar-collapse collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="javascript:void(0);">Home</a></li>
            <li><a href="javascript:void(0);">About</a></li>
            <li><a href="javascript:void(0);">FAQ</a></li>
          </ul>

          <?php if(SHOW_DASHBOARD): ?>
          <!--<ul class="nav navbar-nav navbar-right">
           <li class="dropdown" id="menu">
             <a class="dropdown-toggle" data-toggle="dropdown" href="#menu">
               Access your results later
                <b class="caret"></b>
             </a>
             <div class="dropdown-menu">
               <form style="margin: 0px" >
                <input type="text" class="input-sm" id="permalink" value="qb.cshl.edu/ginkgo/#!/kAr13WPVaEOpbdsaNZeDkAr13WPVaEOpbdsaNZeD">
               </form>
             </div>
           </li>
          </ul>-->
          <?php endif; ?>
          
        </div><!--/.navbar-collapse -->
      </div>
    </div>

    <!-- Welcome message -->
    <div class="jumbotron">
      <div class="container">
        <h1>Ginkgo</h1>
        <?php if(SHOW_DASHBOARD): ?>
          <p style="margin-top:20px;">
            Processing...<br />
            <div class="progress progress-striped"><div class="progress-bar" role="progressbar" style="width: 45%"></div></div>
          </p>
        <?php else: ?>
          <p style="margin-top:20px;">A web tool for analyzing single-cell sequencing data.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Main container -->
    <div class="container">
    <?php if(SHOW_DASHBOARD): ?>
      <!-- Dashboard -->
      <div class="row">
        <div class="col-lg-8">
          <h3 style="margin-top:-5px;"><span class="badge">STEP 1</span> Choose cells for analysis</h3>
          <p>.</p>


          <h3 style="margin-top:-5px;"><span class="badge">STEP 2</span> Set analysis parameters</h3>
          <p>.</p>


          <h3 style="margin-top:-5px;"><span class="badge">STEP 3</span> Choose e-mail options</h3>
          <p>.</p>
          <p><hr><a class="btn btn-lg btn-primary" href="">Start Analysis <span class="glyphicon glyphicon-chevron-right"></span></a></p>
        </div>
        <div class="col-lg-4">

          <!-- Panel: Info -->
          <div class="alert alert-danger fade in">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <p>
                The following files were not valid .bed files:<br/><br/>
                <code>test.pdf</code>, <code>hello.txt</code>
            </p>
          </div>

          <!-- Panel: upload more files -->
          <div class="panel panel-primary">
            <div class="panel-heading">
              <h3 class="panel-title">Upload more files</h3>
            </div>
            <div class="panel-body">
              Panel content
            </div>
          </div>

          <!-- Panel: Save for later -->
          <div class="panel panel-primary">
            <div class="panel-heading">
              <h3 class="panel-title">View analysis later</h3>
            </div>
            <div class="panel-body">
              Access your results from anywhere at<br/><br/>
              <input type="text" class="input-sm" id="permalink" value="qb.cshl.edu/ginkgo/#!/kAr13WPVaEOpbdsaNZeDkAr13WPVaEOpbdsaNZeD"><br/><br/>
              <small><strong>Note:</strong> Closing this window does not interrupt the analysis.</small>
            </div>
          </div>
          
        </div>
      </div>


    <?php else: ?>
      <!-- Upload files -->
      <div class="row">
        <div class="col-lg-8">
          <h3 style="margin-top:-5px;"><span class="badge">STEP 0</span> Upload your .bed files</h3>
          <p></p>
          <p><a class="btn btn-lg btn-primary" href="?q=dashboard"><span class="glyphicon glyphicon-upload"></span> Upload </a></p>
        </div>
        <div class="col-lg-4">
          <h3 style="margin-top:-5px;"><span class="badge">?</span> Help</h3>
          <h4>How to make .bed files</h4>
          <p>Open a terminal and navigate to your data folder:</p>
          <p><code>$ echo "hello";</code></p>
          <p><code>$ echo "world";</code></p>

          <br/>
          <h4>What a .bed file should look like</h4>
          <p>
              <table class="table">
                <thead>
                  <tr><th>chrom</th><th>chromStart</th><th>chromEnd</th></tr>
                </thead>
                <tbody>
                  <tr><td>chr1</td><td>555485</td><td>555533</td></tr>
                  <tr><td>chr1</td><td>676584</td><td>676632</td></tr>
                  <tr><td>chr1</td><td>745136</td><td>745184</td></tr>
                </tbody>
              </table>
          </p>
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

    <!-- Ginkgo
    ================================================== -->
	<script language="javascript">
	/*var ginkgo_user_id = "esYyVyU7GZUKxwYH2tNN";
	$(document).ready(function(){
		// Make sure URL ends with user ID
		if(!window.location.hash)
			window.location = window.location + "#!/" + ginkgo_user_id;

		// Set permalink URL
		$("#permalink").val(window.location.href.replace("http://", ""));
		

	});*/
	</script>


  </body>
</html>

