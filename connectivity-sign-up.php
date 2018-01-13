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
function SignUp() {
	   
    
    if(!empty($_GET['user'])) //checking the 'user' name which is from Sign-Up.html, is it empty or have some text 

{

    
$sql ="SELECT * FROM admintb WHERE user_id = '$_GET[user]'";

//$sql = "SELECT id, name, password FROM admintb";
 global $conn;
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "id: " . $row["id"]. " - Name: " . $row["name"]. " " . $row["email"]. "<br>";
    }
} else {
    newuser();
}
   
 } 
                } 
if(isset($_GET['submit'])) {
     
     
    SignUp();

}  
function NewUser()   { $fullname = $_GET['name'];
$userName = $_GET['user'];
$email = $_GET['email'];
$password = $_GET['pass'];
echo $userName;
echo $email;
echo $password;

$sql = "INSERT INTO admintb (name,user_id,password,email) VALUES ('$fullname','$userName','$password','$email')";

  global $conn;
if ($conn->query($sql)=== TRUE) {
    echo "YOUR REGISTRATION IS COMPLETED...";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}




 } 


$conn->close();
    ?>
