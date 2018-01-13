<?php
$page1="computer.php";
$page2="crs.php";
$page3="network.php";
?>

<!Doctype html>
<html>
<style>
.btn-group button {
    background-color: #4CAF50; /* Green background */
    border: 1px solid green; /* Green border */
    color: white; /* White text */
    padding: 10px 24px; /* Some padding */
    cursor: pointer; /* Pointer/hand icon */
    float: left; /* Float the buttons side by side */
}
.wrapperp {
    text-align: center;
}

.buttonp {
    position: absolute;
    top: 50%;
}

/* Clear floats (clearfix hack) */
.btn-group:after {
    content: "";
    clear: both;
    display: table;
}

.btn-group button:not(:last-child) {
    border-right: none; /* Prevent double borders */
}

/* Add a background color on hover */
.btn-group button:hover {
    background-color: #3e8e41;
}
</style>
<body>


<img src="img_src/it_logo.png" alt="Flowers in Chania" height="100px" width="500px">
<div class="wrapperp">
<div class="buttonp">
<div class="btn-group">
  <button id="btn_comp">Computer</button>
  <button id="btn_crs">CRS</button>
  <button id="btn_nw">Networking</button>
</div></div></div>

  <script>
  
    var btn1 = document.getElementById('btn_comp');
	var btn2 = document.getElementById('btn_crs');
	var btn3 = document.getElementById('btn_nw');
    btn1.addEventListener('click', function() {
      document.location.href = '<?php echo $page1; ?>';
    });
	btn2.addEventListener('click', function() {
      document.location.href = '<?php echo $page2; ?>';
    });
	btn3.addEventListener('click', function() {
      document.location.href = '<?php echo $page3; ?>';
    });
  </script>
<h1>
We are here to help you. 
</h1>

</body>
</html>
