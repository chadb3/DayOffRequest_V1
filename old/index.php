<?php
clearstatcache();
//phpinfo(); 
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

	<h1 id="head">Day off request form!</h1>
	<div id="side"> 
		<ul>
			<a href="index.php"><li>Home</li></a>
			<a href="dayOffRequest.php"><li>Request Day Off</li></a>
			<a href="manageDays.php"><li>View Days Off</li></a>
			<!--<li id='special' ><?php /*echo var_dump($result)/*$a_var  */?></li>-->
		</ul>
		
	</div>
	
	
	
<!--Body of the html has all the form actions and button!-->
<div id="body">
	<h1 id="index_hello">Hello World</h1>
	<p id="intro">

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque imperdiet, massa ac sodales posuere, orci mauris rhoncus nunc, id consequat purus neque sit amet augue. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras tortor ipsum, molestie eget aliquet ut, varius sed dui. Morbi eleifend ornare sapien. Nulla rutrum, odio non gravida scelerisque, nisi sem lacinia augue, at imperdiet sem augue ut massa. Proin leo sapien, venenatis gravida turpis ut, fermentum finibus nibh. Sed a nisl at sem blandit vestibulum. Aenean lorem mi, rutrum sit amet mollis eget, tristique nec nunc. Mauris ac nisi enim. Aenean gravida nibh pellentesque hendrerit elementum. Mauris nec est non diam fermentum egestas in eu sem. Proin lorem velit, imperdiet vitae condimentum quis, sagittis vehicula enim. Phasellus iaculis ex eget mattis euismod.

Nunc a placerat diam. Maecenas pharetra lacus eu purus eleifend, vitae lobortis ante cursus. Duis molestie sollicitudin pulvinar. Cras imperdiet, nisl mollis sagittis posuere, sapien purus ullamcorper urna, nec congue mi nibh eget neque. Donec at urna magna. Quisque vitae augue vel lectus varius tincidunt et ut risus. Mauris ut mollis justo, sed aliquam velit. Sed tincidunt sem tortor, non cursus mauris fermentum quis. Donec vitae augue ac ante sollicitudin consectetur.

Integer facilisis lorem vitae turpis congue, a finibus sem imperdiet. Phasellus tellus leo, facilisis in nulla at, dignissim mollis mi. Vivamus imperdiet justo eu ante laoreet, hendrerit commodo sem ornare. Mauris malesuada congue diam. Aenean consequat ligula a orci dictum, id interdum arcu viverra. Nunc dictum ligula leo, a tristique odio maximus vel. Proin convallis tristique leo, quis rhoncus massa finibus sed. Aliquam porttitor augue velit, sagittis volutpat lacus iaculis vel. Proin lacinia sit amet purus ut dignissim. Sed sapien nulla, imperdiet ut ultrices id, finibus non est. Nulla gravida rutrum molestie. Maecenas cursus posuere sapien a porta. Nam at cursus turpis. Praesent iaculis, elit eget imperdiet luctus, nibh urna maximus neque, et condimentum arcu nibh non massa. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc a libero rhoncus erat venenatis pretium nec non mauris.

Quisque sollicitudin mauris a eros sollicitudin volutpat. Vivamus cursus aliquet tristique. Suspendisse malesuada, nisi et porttitor lobortis, nunc nisl tempus augue, in semper mi urna ac orci. Donec nec neque tempor, facilisis orci quis, sodales mauris. Sed lobortis a ligula nec pulvinar. Nunc suscipit justo porttitor justo volutpat tincidunt. Aliquam ultrices porttitor diam vitae vehicula. Mauris viverra aliquet lorem, quis venenatis mi.

Donec nec venenatis elit. Nulla consequat, eros ac mattis finibus, tortor dolor aliquam mi, id pellentesque diam nibh sed justo. Morbi fermentum enim odio, eu interdum risus auctor non. Maecenas congue porta mollis. Nam ut posuere ligula. Aenean rhoncus orci a lacinia euismod. Cras eu augue dictum, dapibus neque id, iaculis velit. Nam convallis odio et arcu gravida, vel mattis augue malesuada. Donec id augue sed felis rutrum fringilla nec quis libero. Vivamus sit amet pellentesque nisl. 
</p>
	
</div>

<!--Bottom div with information -->
<div id="bottom_div">
<p>Day off requester web-software</p>
</div>



</html>

