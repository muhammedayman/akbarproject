<?php


include_once '/class.adminaddprblm.php';  $user = new User(); // Checking for user logged in or not


 if (isset($_REQUEST['submit'])){

 extract($_REQUEST);

 $register = $user->add_prblm($system_prblm,$crs_prblm,$network_prblm);

 if ($register) {

 // Registration Success

 echo 'Added successfully <a href="login.php">Click here</a> to login';

 } else {

 // Registration Failed

 echo 'Added failed. problem already exits please try again';

 }

 }
?>
<!DOCTYPE HTML>
<html>
<head>
<link rel="stylesheet" type="text/css" href="akbar_cmp_project/css/style.css">
<title>Sign-Up</title>
</head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />

 

Register

<style><!--

 #container{width:400px; margin: 0 auto;}

--></style>

 

<script type="text/javascript" language="javascript">

 function submitreg() {

 var form = document.reg;

 if(form.system_prblm.value == "" && form.crs_prblm.value == "" && form.network_prblm.value == ""){

 alert( "Enter atleast one field." );

 return false;

 }

 

 }

</script>

<div id="container">

<h1>Adding problem to database</h1>

<form action="" method="post" name="reg">

<table>

<tbody>


<tr>

<th>Sytem problem:</th>

<td><input type="text" name="system_prblm" /></td>

</tr>


<tr>

<th>CRS PROBLEM:</th>

<td><input type="text" name="crs_prblm" /></td>

</tr>


<tr>

<th>Network PROBLEM:</th>

<td><input type="text" name="network_prblm"  /></td>

</tr>

<tr>

<td></td>

<td><input onclick="return(submitreg());" type="submit" name="submit" value="ADD" /></td>

</tr>

<tr>

<td></td>


</tr>

</tbody>

</table>

</form></div>
</body>

</html>
