<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "akbar_cmp";


$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

          ?>
<html lang="en">
<head>

 <title>Gmail Style Inbox page - coderglass</title>
 <link href="css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
 <link href="css/tab.css" rel="stylesheet" id="bootstrap-css">

 <script src="js/jquery-1.10.2.min.js"></script>
 <script src="js/bootstrap.min.js"></script>
 <script type="text/javascript">
 window.alert = function(){};
 var defaultCSS = document.getElementById('bootstrap-css');
 function changeCSS(css){
 if(css) $('head > link').filter(':first').replaceWith('<link rel="stylesheet" href="'+ css +'" type="text/css" />');
 else $('head > link').filter(':first').replaceWith(defaultCSS);
 }
 $( document ).ready(function() {
 var iframe_height = parseInt($('html').height());
 window.parent.postMessage( iframe_height, 'https://coderglass.com');
 });
 </script>
</head>
<body>
<div class="container">
 <div class="row">
 <div class="col-sm-3 col-md-2">
 <div class="btn-group">
 <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
 Gmail <span class="caret"></span>
 </button>
 <ul class="dropdown-menu" role="menu">
 <li ><a style='color:red;' href="#">Mail</a></li>
 <li><a href="#">Contacts</a></li>
 <li><a href="#">Tasks</a></li>
 </ul>
 </div>
 </div>
 <div class="col-sm-9 col-md-10">
 <!-- Split button -->
 <div class="btn-group">
 <button type="button" class="btn btn-default">
 <div class="checkbox" style="margin: 0;">
 <label>
 <input type="checkbox">
 </label>
 </div>
 </button>
 <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
 <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
 </button>
 <ul class="dropdown-menu" role="menu">
 <li><a href="#">All</a></li>
 <li><a href="#">None</a></li>
 <li><a href="#">Read</a></li>
 <li><a href="#">Unread</a></li>
 <li><a href="#">Starred</a></li>
 <li><a href="#">Unstarred</a></li>
 </ul>
 </div>
 <button type="button" class="btn btn-default" data-toggle="tooltip" title="Refresh">
 &nbsp;&nbsp;&nbsp;<span class="glyphicon glyphicon-refresh"></span>&nbsp;&nbsp;&nbsp;</button>
 <!-- Single button -->
 <div class="btn-group">
 <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
 More <span class="caret"></span>
 </button>
 <ul class="dropdown-menu" role="menu">
 <li><a href="#">Mark all as read</a></li>
 <li class="divider"></li>
 <li class="text-center">
<small class="text-muted">Select messages to see more actions</small>
</li>
 </ul>
 </div>
 <div class="pull-right">
Problems
 <span class="text-muted"><b>1</b>â€“<b>50</b> of <b>277</b></span>
 <div class="btn-group btn-group-sm">
 <button type="button" class="btn btn-default">
 <span class="glyphicon glyphicon-chevron-left"></span>
 </button>
 <button type="button" class="btn btn-default">
 <span class="glyphicon glyphicon-chevron-right"></span>
 </button>
 </div>
 </div>
 </div>
 </div>
 <hr />
 <div class="row">
 <div class="col-sm-3 col-md-2">
 <a href="#" class="btn btn-danger btn-sm btn-block" role="button">COMPOSE</a>
 <hr />
 <ul class="nav nav-pills nav-stacked">
 <li class="active"><a href="#"><span class="badge pull-right">1546</span> Inbox </a>
 </li>
 <li><a style='color:black;' href="http://www.coderglass.com">Starred</a></li>
 <li><a style='color:black;' href="http://www.coderglass.com">Important</a></li>
 <li><a style='color:black;' href="http://www.coderglass.com">Sent Mail</a></li>
 <li><a style='color:black;' href="http://www.coderglass.com">
<span class="badge pull-right">445</span>Drafts</a></li>
 </ul>
 </div>
 <div class="col-sm-9 col-md-10">
 <!-- Nav tabs -->
 <ul class="nav nav-tabs">
 <li class="active"><a href="#home" data-toggle="tab">
<span class="glyphicon glyphicon-inbox">
 </span>Primary</a></li>
 <li><a style="color:black;" href="#profile" data-toggle="tab">
<span class="glyphicon glyphicon-user">
</span>Social</a></li>
 <li><a style="color:black;" href="#messages" data-toggle="tab">
<span class="glyphicon glyphicon-tags">
</span>Promotions</a></li>
 <li><a style="color:black;" href="#settings" data-toggle="tab">
<span class="glyphicon glyphicon-plus no-margin">
 </span></a></li>
 </ul>
 <!-- Tab panes -->
 <div class="tab-content">
 <div class="tab-pane fade in active" id="home">
 <div class="list-group">

<?php	
        $sql ="SELECT * FROM admintb";
$output="";
//$sql = "SELECT id, name, password FROM admintb";
 global $conn;
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
      // echo "id: " . $row["id"]. " - Name: " . $row["name"]. " " . $row["email"]. "<br>";
 
				
				
				 $output.='<a href="#" class="list-group-item">
                  <div class="checkbox">
                       <label>
                        <input type="checkbox">
                       </label>
                    </div>
               <span class="glyphicon glyphicon-star-empty"></span>'; 
				
				 $output.= '<span class="name" style="min-width: 120px; display: inline-block;">'. $row["id"]. '</span>';

	
   $output.= '<span class="" style="padding-right:180px;">&nbsp;'. $row["name"].'<br></span> ';
				
             
          $output.= '<span class="text-muted" style="font-size: 11px;">'. $row["email"].'</span> ';
				
				
				
			  
                $output.= '<span class="badge"> on '. $row["email"].'</span>';
          
                
              
				
				
        }        
        echo $output;
		?>
				
					<span class="pull-right">
					<span class="glyphicon glyphicon-paperclip">
                    </span></span>
					</a>
				<?php
}
$conn->close();

?>
					
					


					
					
					
					
					
					
					
					
					
					
					
</div>
                </div>
                
            </div>
           
        </div>
    </div>
</div>

</body>
</html>
<?php
?>