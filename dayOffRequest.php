<?php

$a_var="Hello World!";

            $location="Db3.db";
	    $db = new SQLite3($location);
	    $result=$db->querySingle("select NAME from EMPDAYOFFREQ WHERE NUM=1");

?>

<!DOCTYPE html>
<html>
<head>
<title>Day off request!</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

	
	<h1 id="head">Day off request form!</h1>
	<div id="side"> 
		<ul>
			<a href="index.php"><li>Home</li></a>
			<a href="dayOffRequest.php"><li>Request Day Off</li></a>
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
