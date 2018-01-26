<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml2/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<html>
<head><title><?php echo  $this->get('title') ?></title>
<style type="text/css">

body {
    margin-left: 2em 0.5em;
    margin-right: 2em 0.5em;
    margin-top: 0px;
    font: 1em normal sans-serif;
}

dt {font-weight: bold;}

#progressBar {margin-left: auto; margin-right: auto; margin-bottom: 40px; border-top: 1px solid #999; z-index: 1;}
#progressBar ul {list-style-type: none; text-align: center; margin-top: -8px; padding: 0; position: relative; z-index: 2;}
#progressBar li {display: inline; text-align: center; margin: 0 5px; padding: 1px 7px; color: #666; background-color: #fff; border: 1px solid #ccc;}
#progressBar li#current {color: #666; border: 1px solid #666; background-color: #fafafa;font-weight: bold;}
#progressBar li a, #progressBar li a:visited {text-decoration: none; color: #666;};
#progressBar li a:hover, #progressBar li a:visited:hover {text-decoration: none; color: #ccc;};

form#wizard fieldset {width: 40em;}
legend {font-family: sans-serif; font-size:1.1em; font-weight:bold; border:1px solid #ccc; margin-bottom:5px; padding:3px;}
form#wizard label {clear:left; display:block; float:left; width:20em; text-align:right; padding-right:10px; color:#888; margin-bottom:0.5em; font-size:1em; line-height:1.5em; font-style:normal;}
form#wizard input {border:1px solid #ccc; background:#fff; padding:0.1em; margin-bottom:1em;font-size:1em; font-style:normal;}
.button {border:1px solid #ccc;background-color: #fff;}
.button:hover {background-color: #eee;}

#errorMessages {color: red;}

</style>
</head>
<body>
<img src="./templates/logo.gif" border=0 align=center>
<?php echo  $this->wizard->render(); ?>
</body>
</html>
