<?php

// Configuration
error_reporting(E_ALL);
session_start();

// Get user's query
$query = explode("/", $_GET['q']);
if($query[0] == "dashboard")
{
    define('SHOW_DASHBOARD', true);
    $userID = $query[1];
}
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
    #permalink  { border:1px solid #DDD; width:100%; color:#666; background:transparent; font-family:"courier"; resize:none; height:50px; }
    .jumbotron  { padding:50px 30px 15px 30px; }
    .glyphicon  { vertical-align:top; }
    .badge      { vertical-align:top; margin-top:5px; }
    td          { vertical-align:middle !important; }
    code input  { border:none; color:#c7254e; background-color:#f9f2f4; }
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
          <ul class="nav navbar-nav navbar-right">
            <!-- <li class="active"><a href="javascript:void(0);">Home</a></li> -->
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

          <!-- Panel: Info -->
          <div class="alert alert-danger fade in">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <p>
                The following files are not valid .bed files:<br/><br/>
                <code>test.pdf</code>, <code>hello.txt</code>
            </p>
          </div>

          <!-- Choose cells of interest -->
          <h3 style="margin-top:-5px;"><span class="badge">STEP 1</span> Choose cells for analysis</h3>
          <div id="params-cells">
            <div class="row">
              <div class="col-md-2"><div class="checkbox"><label><input type="checkbox"> Cell 45</label></div></div>
              <div class="col-md-2"><div class="checkbox"><label><input type="checkbox"> Cell 45</label></div></div>
              <div class="col-md-2"><div class="checkbox"><label><input type="checkbox"> Cell 45</label></div></div>
              <div class="col-md-2"><div class="checkbox"><label><input type="checkbox"> Cell 45</label></div></div>
              <div class="col-md-2"><div class="checkbox"><label><input type="checkbox"> Cell 45</label></div></div>
              <div class="col-md-2"><div class="checkbox"><label><input type="checkbox"> Cell 45</label></div></div>
            </div>
            <div class="row">
              <div class="col-md-2"><div class="checkbox"><label><input type="checkbox"> Cell 45</label></div></div>
              <div class="col-md-2"><div class="checkbox"><label><input type="checkbox"> Cell 45</label></div></div>
              <div class="col-md-2"><div class="checkbox"><label><input type="checkbox"> Cell 45</label></div></div>
              <div class="col-md-2"><div class="checkbox"><label><input type="checkbox"> Cell 45</label></div></div>
              <div class="col-md-2"><div class="checkbox"><label><input type="checkbox"> Cell 45</label></div></div>
              <div class="col-md-2"><div class="checkbox"><label><input type="checkbox"> Cell 45</label></div></div>
            </div>
    		<br/>
          </div>

          <!-- Get informed by email when done? -->
          <h3 style="margin-top:-5px;"><span class="badge">STEP 2</span> E-mail notification <small></small></h3>
          <div id="params-email">
            <p>If you want to be notified once the analysis is done, enter your e-mail here:<br/></p>
            <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
              <input id="email" class="form-control" type="text" placeholder="my@email.com">
            </div>
          </div>
          <br/>

          <p><br/><a class="btn btn-lg btn-primary" href="">Start Analysis <span class="glyphicon glyphicon-chevron-right"></span></a></p>

          <hr style="height:5px;border:none;background-color:#CCC;" />
          
          <!-- Set parameters -->
          <h3 style="margin-top:-5px;"><span class="badge">OPTIONAL</span> <a href="#" onClick="javascript:$('#params-table').toggle();">Analysis parameters</a></h3>
          <table class="table table-striped" id="params-table">
            <tbody>
                <tr>
                    <td>Binning Options</td>
                    <td>
                        Use a <select id="param-bins-type" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
        					  <option value="0">fixed</option>
        					  <option value="4">variable</option>
        				</select> bin size of <select id="param-bins-value" class="input-mini" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
        				 	  <option value="0">10kb</option>
        					  <option value="1">25kb</option>
        					  <option value="2">40kb</option>
        					  <option value="3">50kb</option>
        				</select> size.
        			</td>
        		</tr>
                <tr>
                    <td>Segmentation</td>
                    <td>
                        Use <select id="param-segmentation" class="input-medium" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
        					<option value="0">bin count variability</option>
        					<option value="1">GC content per bin</option>
        				</select> to segment.
                    </td>
                </tr>
                <tr>
                    <td>Clustering</td>
                    <td>
                        Use <select id="param-clustering" class="input-small" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
        				<option value="0">single</option>
        				<option value="1">complete</option>
        				<option value="2">average</option>
        				<option value="3">ward</option>
        			    </select> clustering.
                    </td>
                </tr>
                <tr>
                    <td>Distance metric</td>
                    <td>
                        Use <select id="param-distance" class="input-small" style="margin-top:8px; font-size:11px; padding-top:3px; padding-bottom:0; height:25px; ">
        				<option value="0">Euclidean</option>
        				<option value="1">maximum</option>
        				<option value="2">Manhattan</option>
        				<option value="3">Canberra</option>
        				<option value="4">binary</option>
        				<option value="5">Minkowski</option>
        			    </select> distance.
        		    </td>
                </tr>
            </tbody>
          </table>
          <br/>

        </div>


        <div class="col-lg-4">
          <!-- Panel: upload more files -->
          <div class="panel panel-primary">
            <div class="panel-heading">
              <h3 class="panel-title"><span class="glyphicon glyphicon-upload"></span> Upload more files</h3>
            </div>
            <div class="panel-body">
              Panel content
            </div>
          </div>

          <!-- Panel: Save for later -->
          <div class="panel panel-primary">
            <div class="panel-heading">
              <h3 class="panel-title"><span class="glyphicon glyphicon-time"></span> View analysis later</h3>
            </div>
            <div class="panel-body">
              Access your results from anywhere at<br/><br/>
              <!-- <input type="text" class="input-sm" id="permalink" value="http://qb.cshl.edu/ginkgo/?q=dashboard/kAr13WPVaEOpbdsaNZeDkAr13WPVaEOpbdsaNZeD"> -->
              <textarea class="input-sm" id="permalink">#</textarea>
              <br/><br/>
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
          <p><a class="btn btn-lg btn-primary" href="?q=dashboard/kAr13WPVaEOpbdsaNZeDkAr13WPVaEOpbdsaNZeD"><span class="glyphicon glyphicon-upload"></span> Upload </a></p>
        </div>
        <div class="col-lg-4">
          <h3 style="margin-top:-5px;"><span class="badge">?</span> Help</h3>
          <h4>How to make .bed files</h4>
          <p>Open a terminal and navigate to your data folder:</p>
          <p><code>$ <input type="text" value="bowtie2 file > file.bam"></code></p>
          <p><code>$ <input type="text" value="bamToBed file.bam > file.bed"></code></p>

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


    <?php if(SHOW_DASHBOARD): ?>
    <!-- Ginkgo
    ================================================== -->
	<script language="javascript">
	var ginkgo_user_id = "<?php echo $userID; ?>";
	$(document).ready(function(){
		// Set permalink URL
		$("#permalink").val(window.location.href.replace("http://", ""));

        // Hide parameters table
        $("#params-table").hide();
	});
	</script>
    <?php endif; ?>

  </body>
</html>

