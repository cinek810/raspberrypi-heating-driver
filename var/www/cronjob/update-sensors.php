#!/usr/bin/php5
<?php

include("/var/www/config/db.php");



//Update Sensors State
$sensors_dir="/sys/bus/w1/devices/";
$devices=scandir($sensors_dir);

$crcokbool=-1;

foreach ($devices as $device)
{
	sleep(10);
	print("\n========= Performing actions on ".$device."\n");
	$temp=500; //If temp=500 it means that an error occured during temperature reading
	//If the device name starts with 28 we assume this is temp sensor - true?
	if(strcmp(substr($device,0,2),"28")==0 or strcmp(substr($device,0,2),"10")==0)
	{
		$filename="$sensors_dir".$device."/w1_slave";
		$handler=fopen($filename,"r");
		$content=fread($handler,filesize($filename));
		$position=strpos($content,"t=");
		$temp_int=substr($content,$position+2,2);
		$temp_float=substr($content,$position+4,3);
		$temp=(float)$temp_int + 0.001* (float)$temp_float;
		$position=strpos($content,"crc");
		$crcok=substr($content,$position+7,1);
		if(strcmp($crcok,"Y")==0)
			$crcokbool=1;
		else
			$crcokbool=0;

		print "CRCOK:'".$crcok."' and crcokbool=".$crcokbool."\n";
	
		@mysql_connect('localhost',$user,$password)  or die("Cannot connect to database");
		@mysql_select_db($database) or die("Cannot select database");	
		$result=mysql_query("SELECT SENSORID FROM sensors WHERE SENSORID ='".$device."'" );
		$numres=mysql_numrows($result);
		if($numres==0)
		{
			//We need to add SENSOR
			$query="INSERT INTO sensors(SENSORID,temp,FAIL) VALUES('".$device."',".$temp.",".$crcokbool.")";
			print ("Adding new sensor with ".$query."\n");
			mysql_query($query);
		}
		elseif ($numres ==1 )
		{
			//Update temp in database

			$query="UPDATE sensors SET temp = ".$temp.", FAIL = ".$crcokbool." WHERE SENSORID ='".$device."'";
			print ("Updating temp information sensor with ".$query."\n");
			mysql_query($query);
		}
		else
		{
			//Error in DB 
			print( "Error in database more than one sensor with ID:".$device." exist \n");
		}
			
		//Compare current and setted temp, decide output state
		$hour=date('G', mktime());
		$query="SELECT temp as t,on".$hour." as 'on',off".$hour." as off,pin,nazwa from sensors where SENSORID='".$device."'";
		print "Getting data with query:".$query."\n";
		$result=mysql_query($query);
		$temps=mysql_fetch_object($result);


		print "Temp: ".$temps->t." on:".$temps->on." off:".$temps->off."\n";
		if ($crcokbool>0) //if crc is OK
		{
			//pin=500 is special vaule meaning that pin was not defined
			if($temps->pin != 500 or $t) 
			{
				if($temps->t > $temps->off)
				{
					print "Turing off".$temps->nazwa."\n";
					system("/var/www/use-output off ".$temps->pin);
				}
				elseif ( $temps->t < $temps->on)
				{
					print "Turning on ".$temps->nazwa."\n";
					system("/var/www/use-output on ".$temps->pin);
				}
				else
				{
					print "Keeping state of ".$temps->nazwa."\n";
				}
			}
			else
			{
				print "Pin not defined for ".$temps->nazwa." If you use pin 500 you have to change this special value.\n";
			}
		}
		else
			print "Doesn't consider output - CRC error\n";

		
		//Update RRD graphs
		if($crcokbool>0)
		{
			$rrdfile_name="/var/www/rrd/".$device.".rrd";
			if(!file_exists($rrdfile_name))
			{//If rrd doesn't exist create one

				$options = array(
				 "--step", "60",            // Use a step-size of 5 minutes
				 "DS:temp:GAUGE:130:0:45",
				 "RRA:AVERAGE:0.5:360:2880",
				 "RRA:AVERAGE:0.5:30:2880",
				 "RRA:AVERAGE:0.5:7:2880",
				 "RRA:AVERAGE:0.5:1:2880",
				
				 );
				rrd_create($rrdfile_name,$options);	
				
			}		
			else
			{
				$now=mktime();
				$options=array($now.":".$temp);	
				print("Updating rrd with timestamp=".$now." temp=".$temp."\n");
				$ret=rrd_update($rrdfile_name,$options);
				if($ret==false)
					print("Failed to update ".$rrdfile_name."\n");
		
			}
		}
		else
			print "Doesn't update rrd with data - CRC error\n";
		
		
	}
}
	print("=========READING FINISHED=========");
?>
