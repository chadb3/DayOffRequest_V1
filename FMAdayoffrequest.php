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
					width:100px;
					float:left;
					padding:5px;
				}
			
		
		
	</style>
	
	<h1 id="head">Day off request form!</h1>
	<div id="side"></div>
<div id="body">
	<form>
		   Name:<input type="text" name="name"> <br>
		   Phone:<input type="text" name="phone"><br>
		   <!--Reason:<input type="dropdown" name="asdf">-->		
	</form>
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
</div>



</body>






</html>
