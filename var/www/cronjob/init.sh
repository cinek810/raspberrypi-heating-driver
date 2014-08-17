#!/bin/bash
rmmod w1_gpio
rmmod w1_therm

modprobe w1_gpio
modprobe w1_therm
echo 1 > /sys/bus/w1/devices/w1_bus_master1/w1_master_search
cat /var/www/config/urzadzenia  | xargs -n1 -I DEV -t   echo  DEV > /sys/bus/w1/devices/w1_bus_master1/w1_master_add

