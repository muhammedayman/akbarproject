<?php
include "db_config.php";



 class User{



     public $db;



     public function __construct(){

         $this->db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);



         if(mysqli_connect_errno()) {

             echo "Error: Could not connect to database.";

                 exit;

         }

     }



     /*** for registration process ***/

     public function add_prblm($system_prblm,$crs_prblm,$network_prblm){



        

         $sql="SELECT * FROM system_tb WHERE system_prblm='$system_prblm'";



         //checking if the username or email is available in db

         $check =  $this->db->query($sql) ;

         $count_row = $check->num_rows;



         //if the username is not in db then insert to the table

         if ($count_row == 0){
 echo $system_prblm;
             $sql1="INSERT INTO system_tb SET system_prblm='$system_prblm'";

             $result = mysqli_query($this->db,$sql1) or die(mysqli_connect_errno()."Data cannot inserted");

             return $result;

         }

         else { 
		 return false;}

     }



     /*** for login process ***/

     public function check_data($system_prblm){



        

         $sql2="SELECT * from system_tb";



         //checking if the username is available in the table

         $result = mysqli_query($this->db,$sql2);

         $user_data = mysqli_fetch_array($result);

         $count_row = $result->num_rows;
           echo $count_row;
		   echo $user_data['system_prblm'];


         


     }



     /*** for showing the username or name ***/

     public function get_name($user_id){

         $sql3="SELECT name FROM admintb WHERE user_id = $user_id";

         $result = mysqli_query($this->db,$sql3);

         $user_data = mysqli_fetch_array($result);

         echo $user_data['name'];

     }



     /*** starting the session ***/

     public function get_session(){

         return $_SESSION['login'];

     }



     public function user_logout() {

         $_SESSION['login'] = FALSE;

         session_destroy();

     }



 }
?>