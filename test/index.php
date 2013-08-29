<!DOCTYPE html>
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
    #permalink  { border:none; width:350px; text-align:center; color:#999; background:transparent; 	}
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

          <ul class="nav navbar-nav navbar-right">
           <li class="dropdown" id="menu1">
             <a class="dropdown-toggle" data-toggle="dropdown" href="#menu1">
               Access your results from anywhere
                <b class="caret"></b>
             </a>
             <div class="dropdown-menu">
               <form style="margin: 0px" >
                <input type="text" class="input-sm" id="permalink" value="qb.cshl.edu/ginkgo/#!/kAr13WPVaEOpbdsaNZeDkAr13WPVaEOpbdsaNZeD">
               </form>
             </div>
           </li>
          </ul>
          
        </div><!--/.navbar-collapse -->
      </div>
    </div>

    <!-- Welcome message -->
    <div class="jumbotron">
      <div class="container">
        <h1>Ginkgo</h1>
        <p style="margin-top:20px;">A web tool for analyzing single-cell sequencing data.</p>
      </div>
    </div>

    <div class="container">
      <!-- Example row of columns -->
      <div class="row">
        <div class="col-lg-8">
          <h3><span class="badge">STEP 1</span> Upload your .bed files</h3>
          <p></p>
          <p><a class="btn btn-lg btn-primary" href=""><span class="glyphicon glyphicon-upload"></span> Upload </a></p>
        </div>
        <div class="col-lg-4">
          <h3><span class="badge">?</span> Help</h3>
          <h4>How to make .bed files</h4>
          <p>Open a terminal to the directory containing your files:</p>
          <p><code>$ echo "hello";</code></p>
          <p><code>$ echo "world";</code></p>

          <br/>
          <h4>What a .bed file should look like</h4>
          <p>
              <table class="table">
                <thead>
                  <tr>
                    <th>chrom</th>
                    <th>chromStart</th>
                    <th>chromEnd</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>chr1</td>
                    <td>555485</td>
                    <td>555533</td>
                  </tr>
                  <tr>
                    <td>chr1</td>
                    <td>676584</td>
                    <td>676632</td>
                  </tr>
                  <tr>
                    <td>chr1</td>
                    <td>745136</td>
                    <td>745184</td>
                  </tr>
                </tbody>
              </table>
          </p>
        </div>
      </div>

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
	$('.dropdown-menu').find('form').click(function (e) {
	    e.stopPropagation();
	  });
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
		$("#permalink").focus(function() { $(this).select(); } );

	});*/
	</script>


  </body>
</html>

