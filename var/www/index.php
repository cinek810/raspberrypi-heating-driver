<?php

	include("./config/db.php");

	@mysql_connect('localhost',$user,$password) or die("Nie udalo sie polaczyc z baza danych");
	@mysql_select_db($database) or die("Nie udalo sie wybrac bazy danych, sprawdz konfiguracje");


	print ("<HTML><head> <meta http-equiv=refresh content=60> </head> ");
	$decision_file="/var/www/decision.log";
	$decision_stat=stat($decision_file);
	$decision_last_line=exec("tail -n 1 ".$decision_file);

	if($decision_last_line==="=========READING FINISHED=========")
	{
		echo "Godzina ostatniego odswiezenia:".date('G:i:s', mktime())."<BR> Ostatnie pomiary:".date("G:i:s",$decision_stat["mtime"])." <a href=\"decision.log\">Zapis z ostatniej iteracji</a><HR>";
	}
	else
	{
		echo "Godzina ostatniego odswiezenia:".date('G:i:s', mktime())."<BR> Pomiary w trakcie odczytu z czujnikow<HR>";
	}
	$query="SHOW TABLES";
	$result=mysql_query($query);
	$numrows=mysql_numrows($result);
	
	//Assumming that we have empty database if this is first run
	if($numrows==0)
	{
			print ("Creating new sensors table");
			mysql_query("CREATE TABLE sensors ( ID INT AUTO_INCREMENT, SENSORID BLOB not NULL, pin INT DEFAULT 500, FAIL BOOL, nazwa BLOB , temp float DEFAULT 0,on0 FLOAT DEFAULT 19, off0 FLOAT DEFAULT 20,on1 FLOAT DEFAULT 19, off1 FLOAT DEFAULT 20,on2 FLOAT DEFAULT 19, off2 FLOAT DEFAULT 20,on3 FLOAT DEFAULT 19, off3 FLOAT DEFAULT 20,on4 FLOAT DEFAULT 19, off4 FLOAT DEFAULT 20,on5 FLOAT DEFAULT 19, off5 FLOAT DEFAULT 20,on6 FLOAT DEFAULT 19, off6 FLOAT DEFAULT 20,on7 FLOAT DEFAULT 19, off7 FLOAT DEFAULT 20,on8 FLOAT DEFAULT 19, off8 FLOAT DEFAULT 20,on9 FLOAT DEFAULT 19, off9 FLOAT DEFAULT 20,on10 FLOAT DEFAULT 19, off10 FLOAT DEFAULT 20,on11 FLOAT DEFAULT 19, off11 FLOAT DEFAULT 20,on12 FLOAT DEFAULT 19, off12 FLOAT DEFAULT 20,on13 FLOAT DEFAULT 19, off13 FLOAT DEFAULT 20,on14 FLOAT DEFAULT 19, off14 FLOAT DEFAULT 20,on15 FLOAT DEFAULT 19, off15 FLOAT DEFAULT 20,on16 FLOAT DEFAULT 19, off16 FLOAT DEFAULT 20,on17 FLOAT DEFAULT 19, off17 FLOAT DEFAULT 20,on18 FLOAT DEFAULT 19, off18 FLOAT DEFAULT 20,on19 FLOAT DEFAULT 19, off19 FLOAT DEFAULT 20,on20 FLOAT DEFAULT 19, off20 FLOAT DEFAULT 20,on21 FLOAT DEFAULT 19, off21 FLOAT DEFAULT 20,on22 FLOAT DEFAULT 19, off22 FLOAT DEFAULT 20,on23 FLOAT DEFAULT 19, off23 FLOAT DEFAULT 20,PRIMARY KEY(id) ) " );
	}
	

	//UPDATE  if needed
	if (isset($_GET["sensorid"]))
	{
		$validtemps=1;
		//Validate if temps areok
		for ($i=0;$i<=23;$i++)
		{
			//print 'if($_GET["off'.$i.'"]<=$_GET["on'.$i.'"]) $validtemps=0;';
			eval('if($_GET["off'.$i.'"]<=$_GET["on'.$i.'"]) $validtemps=0;');
		}
		
	
		$query="UPDATE sensors  SET ";
		for ($i=0;$i<=23;$i++)
			$query=$query." off".$i."=".$_GET["off".$i].",";

		for ($i=0;$i<=23;$i++)
			$query=$query." on".$i."=".$_GET["on".$i].",";

		$query= $query." pin = ".$_GET["pin"].",  nazwa = '".$_GET["nazwa"]."' where SENSORID = '".$_GET["sensorid"]."'";
	
	
		if($validtemps==1)
			mysql_query( $query);
		else
			print "<h2>You were trying to set invalid temps, OFF_TEMP have to be larger than ON_TEMP</h2>";

		unset($_GET["sensorid"]);
	}

	//Print table with temp information
  	$result=mysql_query("SELECT * FROM sensors");	
  	$numrow=mysql_numrows($result);
  	if($numrow>0)
  	{	
		print "<table><thead>";
		print "<tr><th>Nazwa<BR>Pin sterujacy</th><th>Temp</th>";
		for ($i=0;$i<=23;$i++)
		{
			print "<th style=\"width:3.5em\">".$i."</th>";
		}
		print "</thead><tbody>";
		
		$firstcolour="grey";
  		while ( $sensor=mysql_fetch_object($result))
  		{
			if($sensor->nazwa=="")
				$sensor->nazwa="NOT NAMED";
				
				print "\n<form><tr ><th style=\"background:".$firstcolour."\" rowspan=2><input type=submit value=Aktualizuj><input type=hidden name=sensorid value=".$sensor->SENSORID."><input name=nazwa size=10 type=text value='".$sensor->nazwa."'><input size=2 name=pin value=$sensor->pin></th>";
				if($firstcolour==="grey")
					$firstcolour="lightgrey";
				else
					$firstcolour="grey";

				
				$hour=date('G', mktime());
				eval('$ontemp=$sensor->on'.$hour.';');
				eval('$offtemp=$sensor->off'.$hour.';');

				if($sensor->FAIL==1)
				{	
					if($sensor->temp<$ontemp)
						$tempcol="lightblue";
					elseif($sensor->temp>$offtemp)
						$tempcol="orange";
					else
						$tempcol="lightgreen";
				}
				else
					$tempcol="red";

				print("	<th style=\"background:".$tempcol.";\" rowspan=2><a href=\"./plot.php?sensorid=".$sensor->SENSORID."\">".$sensor->temp);
				if($sensor->FAIL==false)
					print ("?");
				print("</a></th>");
			
				for ($i=0;$i<=23;$i++)
				{
					print "\n<td style=\"background:lightgrey;text-align:center\">";
					eval('print("<input align=center type=text maxlength=2 style=\"width:1.6em\"  size=2 name=off'.$i.' value=\"$sensor->off'.$i.'\">");');
					print"</td>";
				}
				print "</tr><tr>";
				for ($i=0;$i<=23;$i++)
				{
					print "\n<td style=\"background:grey;text-align:center\">";
					eval('print("<input type=text maxlength=2 style=\"width:1.6em\" size=2 name=on'.$i.' value=\"$sensor->on'.$i.'\">");');
					print"</td>";
				}
				print "</tr> ";
						
				print "</form>";
  		}	
		print "</table>";
		
  	}
	else
	{
		print "No sensors found in system";	
	}



	

	
	mysql_close();

?>
