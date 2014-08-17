<?php
	function make_graph($desc,$time,$colour)
	{
			$options=array("--end now", "--start end-".$time , "DEF:dzien=/var/www/rrd/".$_GET["sensorid"].".rrd:temp:AVERAGE", "LINE1:dzien".$colour.":\"Temperatura\"");
		$graph_file="/var/www/graph/".$_GET["sensorid"]."-".$time.".png";
		//For some stupid reason rrd_graph did not work for me
		$ret=exec("/usr/bin/rrdtool graph ".$graph_file." ".implode(" ",$options ));
		if($ret==false)
			print "Failed to create graph:".$graph_file."\n Trying to generate with options:".$graph_file." ".implode($options);
		else
			print("<h1>".$desc."</h1><br><img src=/graph/".$_GET["sensorid"]."-".$time.".png>");

	}
#	$_GET["sensorid"]="10-0008015579b9";
	if(!isset($_GET["sensorid"]))
	{
		print ("Sensor not defined");
	}
	else
	{
		print "<table><tr><td>";
		make_graph("Ostatnia godzina","3600s","#00ff00");
		print "</td><td>";
		make_graph("Ostatni dzien","24h","#00ff00");

		print "</td></tr><tr><td>";
		make_graph("Ostatni tydzien","7day","#00ff00");
		print "</td><td>";
		make_graph("Ostatni miesiac","30day","#00ff00");
		print "</td></tr><tr><td>";
		make_graph("Ostatni rok","360day","#00ff00");
		print "</td></tr></table>";
	}

?>
