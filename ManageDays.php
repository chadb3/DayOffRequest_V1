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

<!DOCTYPE HTML>
<html>
<head>
	<title>Manage Days Off!</title>
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
			ul
				{
				
					color:blue;
					list-style-type:none;
					float:left;
					text-align:left;
					padding-left:0px;
				}
					#special
				{
					padding-top: 550px;
					color:green;
					font-size:10px;
					position:fixed;
					
					
					
				}
				#body
				{
					background-color:red;
				
				}
				
				
				
 </style>
 <h1 id="head">Manage Days!</h1>
 	<div id="side"> 
		<ul>
			<a href="FMAdayoffrequest.php"><li>Home</li></a>
			<a href="ManageDays.php"><li>View Days Off</li></a>
			<li id='special' ><?php echo $a_var  ?></li>
		</ul>
		
	</div>
	
<div id="body">
	<?php
	
	
	?>
	
	<!--<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>
	<p>test</p>-->

	</div>
	
	
	
	<div id="bottom_div">
<p>FMA day off requester web-software</p>
</div>
 

</body>



</html>
