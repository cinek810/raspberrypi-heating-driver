<?php
	system("sudo bash -c ' /bin/echo 5 > /sys/bus/w1/devices/w1_bus_master1/w1_master_search'");
	print("<HTML><head> <meta http-equiv=refresh content='0; index.php'> </head></html>");
?>
