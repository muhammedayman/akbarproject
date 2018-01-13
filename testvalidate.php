<!DOCTYPE HTML>  
<html>
<head>
<style>
.error {color: #FF0000;}
</style>
</head>
<body>  

<?php
// define variables and set to empty values
$nameErr = $emailErr = $genderErr = $passErr = "";
$name = $email = $gender = $userid = $pass = "";

if ($_SERVER["REQUEST_METHOD"] == "GET") {
  if (empty($_GET["name"])) {
    $nameErr = "Name is required";
  } else {
    $name = test_input($_GET["name"]);
    // check if name only contains letters and whitespace
    if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
      $nameErr = "Only letters and white space allowed"; 
    }
  }
  
  if (empty($_GET["email"])) {
    $emailErr = "Email is required";
  } else {
    $email = test_input($_GET["email"]);
    // check if e-mail address is well-formed
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $emailErr = "Invalid email format"; 
    }
  }
    
  if (empty($_GET["pass"])) {
    $pass= "";
  } else {
    $pass= test_input($_GET["pass"]);
    // check if URL address syntax is valid (this regular expression also allows dashes in the URL)
    checkPassword($pass, &$errors)
  }

  if (empty($_GET["userid"])) {
    $userid = "";
  } else {
    $userid = test_input($_GET["userid"]);
  }

  if (empty($_GET["gender"])) {
    $genderErr = "Gender is required";
  } else {
    $gender = test_input($_GET["gender"]);
  }
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
public function checkPassword($pwd, &$errors) {
    $errors_init = $errors;

    if (strlen($pwd) < 8) {
        $errors[] = "Password too short!";
    }

    if (!preg_match("#[0-9]+#", $pwd)) {
        $errors[] = "Password must include at least one number!";
    }

    if (!preg_match("#[a-zA-Z]+#", $pwd)) {
        $errors[] = "Password must include at least one letter!";
    }     

    return ($errors == $errors_init);
}
?>

<h2>PHP Form Validation Example</h2>
<p><span class="error">* required field.</span></p>
//<form method="GET" action="connectivity-sign-up.php">
<form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
  Name: <input type="text" name="name" value="<?php echo $name;?>"                         >
  <span class="error">* <?php echo $nameErr;?></span>
  <br><br>
  E-mail: <input type="text" name="email" value="<?php echo $email;?>">
  <span class="error">* <?php echo $emailErr;?></span>
  <br><br>
  pass: <input type="text" name="pass" value="<?php echo $pass;?>">
  <span class="error"><?php echo $passErr;?></span>
  <br><br>
  userid: <textarea name="userid" rows="5" cols="40"><?php echo $userid;?></textarea>
  <br><br>
  Gender:
  <input type="radio" name="gender" <?php if (isset($gender) && $gender=="female") echo "checked";?> value="female">Female
  <input type="radio" name="gender" <?php if (isset($gender) && $gender=="male") echo "checked";?> value="male">Male
  <span class="error">* <?php echo $genderErr;?></span>
  <br><br>
  <input type="submit" name="submit" value="Submit">  
</form>

<?php
echo "<h2>Your Input:</h2>";
echo $name;
echo "<br>";
echo $email;
echo "<br>";
echo $pass;
echo "<br>";
echo $userid;
echo "<br>";
echo $gender;
?>

</body>
</html>
