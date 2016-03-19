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
      echo $db->lastErrorMsg();
   } else {
      echo "Opened database successfully\n";
      
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
					background-color:#eeeeee;
					height:600px;
					width:159px;
					float:left;
					padding:1px;
					
				}
				#bottom_div{
					background-color:purple;
					color:white;
					clear:both;
					text-align:center;
					padding:5px;
					
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
			
		
		
	</style>
	
	<h1 id="head">Day off request form!</h1>
	<div id="side"> 
		<ul>
			<li>Home</li>
			<li>View Days Off</li>
		</ul>
		
	</div>
	
	
	
<!--Body of the html has all the form actions and button!-->
<div id="body">
	<form action="<?php $_PHP_var ?>" method ="GET">
		   Name:<input type="text" name="name" required> <br>
		   Phone:<input type="text" name="phone" required><br>
		   <!--Reason:<input type="dropdown" name="asdf">-->		
	
	Reason: <select name="reason">
		<option value="medical">Medical</option>
		<option value="dential">Dential</option>
		<option value="family_emergency">Family Emergency</option>
		<option value="personal">Personal</option>
		<option value="other">Other</option>
	</select>
	<br>
	<!--<form>Elaborate Reason:<input type="text" name="elab_reason"></form>-->
	<button type="button">Submit</button>
	</form>
	<?php echo $a_var; ?>
	
	
	
	
</div>
<!--Bottom div with information -->
<div id="bottom_div">
<p>FMA day off requester web-software</p>
</div>





</body>






</html>

