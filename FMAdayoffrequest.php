<?php 
$a_var="asd";

   class MyDB extends SQLite3
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
   }


?>

<!DOCTYPE html>
<html>
<head>
<title>Day off request!</title>

</head>
<body>
	<style>
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
				
			
		
		
	</style>
	
	<h1 id="head">Day off request form!</h1>
	<div id="side"> 
		<ul>
			<a href="FMAdayoffrequest.php"><li>Home</li></a>
			<a href="ManageDays.php"><li>View Days Off</li></a>
			<li id='special' ><?php echo $a_var  ?></li>
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
<p>FMA day off requester web-software</p>
</div>





</body>






</html>

