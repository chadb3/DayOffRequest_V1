<?php
//phpinfo(); 
$a_var="Hello World!";
//function sqlite_open($location,$mode)

//{
            $location="Db3.db";
	    $db = new SQLite3($location);
	    $result=$db->querySingle("select NAME from EMPDAYOFFREQ WHERE NUM=1");
	    //$result=array(1,2,3);
	    
	    //$result="Cheese";
		//echo $result;
	    // $row=$result->fetchArray();
	       // return $handle;

//}
/*   class MyDB extends SQLite
   {
      function __construct()
      {
         $this->open('Db3.db');
      }
   }
   $db = new MyDB();
   if(!$db){
      $a_var= $db->lastErrorMsg();
   } else {
      $a_var= "Database Connecton status!";
   }*/


?>

<!DOCTYPE html>
<html>
<head>
<title>Day off request!</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<!--	<style>
		#head{ 
			background-color:purple;
			color:white;
			text-align:center;
			padding:5.5px;			
		}
		 #side 	{
					line-height:30px;
					background-color:#e6e6e6;
					height:600px;
					width:159px;
					float:left;
					padding:1px;
					padding-bottom:50px;
					
				}
				#bottom_div{
					background-color:purple;
					color:white;
					clear:both;
					text-align:center;
					height:50px;

					
					
				}
				#req
				{
					color:red;
				}
				ul
				{
				
					color:blue;
					list-style-type:none;
					float:left;
					text-align:left;
					padding-left:0px;
				}
				
				
				
				#body
				{
					float: left;
					padding-left:5px;
					
				}
				#special
				{
					padding-top: 550px;
					color:green;
					font-size:10px;
					position:fixed;
					
					
					
				}
				
			
		
		
	</style>-->
	
	<h1 id="head">Day off request form!</h1>
	<div id="side"> 
		<ul>
			<a href="dayOffRequest.php"><li>Home</li></a>
			<a href="manageDays.php"><li>View Days Off</li></a>
			<li id='special' ><?php /*echo*/var_dump($result)/*$a_var*/  ?></li>
		</ul>
		
	</div>
	
	
	
<!--Body of the html has all the form actions and button!-->
<div id="body">

	<form method='POST'>
		Name:<input type="text" name="name"><br>
		Phone:<input type="text" name="phone"><br>
		Shifts:<input type="text" name=shifts"><br>
		Sub:<input type="text" name ="sub"><br>
		Reason:<select name="reason">
				  <option value="Medical">Medical</option>
			      <option value="Dential">Dential</option>
			      <option value="Family Emergency">Family Emergency</option>
			      <option value="Personal">Personal</option>
			      <option value="Other">Other</option>
			  </select><br>
	<input type="submit" name = "submit" value="Send">
	</form>
	
	
	
	

	
	
	<?php
		if(isset($_POST['submit']))
		{
			$name=$_POST["name"];
			$phone=$_POST["phone"];
			$reason=$_POST["reason"];
			

			
			
			
			
			$insert_statment= "INSERT INTO EMPDAYOFFREQ 
			VALUES (null,'$name','$phone','$reason');";
			$db = new MyDB();
			
			
			
			
			
			$db->exec($insert_statment);
			echo "It worked!";
			$db->close();
		}
	
	
	 ?>
	
	
	

	
	
	
</div>
<!--Bottom div with information -->
<div id="bottom_div">
<p>Day off requester web-software</p>
</div>





</body>






</html>

