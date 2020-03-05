# nograd

Mini smart home system with Raspberry Pi

## Introduction

Nógrád is a small village in Hungary. 
I had inherited a weekend house here, I have renovated and since Raspberry Pi is already born, it is a must, to used it to supervising and remote control the house. 
So, this is a small smart home project based on PHP.

## Requirements

* RQ1 Check temperatures inside and outside.
* RQ2 Target inside temperature shall be able to set on user interface.
* RQ3 Control heater based on target and real temperature.
* RQ4 Control lights from mobile as a hand remote control.
* RQ5 User interface shall be accessible from anywhere far.
* RQ6 User interface shall be system independent. ( Desktop PC, Mobile, Tablet ) ( Android, iOS, Windows, Linux, ... )
* RQ7 Electric system shall be safe in case of wrong operation of smart system.
* RQ8 Electric system must provide full (non-smart) function when smart system is out of operation, without and manual back-up activity.
* RQ9 Smart system ECU shall survive usual long power cuts.
* RQ10 Only authenticated users can access to control functions of system.

## Design

### System design

The system is designed based on requirements.

### Hardware design

For temperature sensing (RQ1), the most widely used sensor is DS18B20. This can easily be read by command line, and easy to parse its value.

To control the heater (RQ3) a simple relay is used.

To be the heater control safe (RQ7), both unintended-on and unintended-off shall be considered. 
* Unintended off (should be on, but off) is dangerous, because there is no de-icer function, water system, pipes will be frozen and broken in the house. 
To handle it well, a mechanical wall thermostat is applied which will serve the de-icer function in case of smart system does not work.
* Unintended on (should be off, but on) is also dangerous, because of overheat the house, creates high electricity cost, and maybe fire dangerous too. 
To handle it well, another mechanical wall thermostat is applied which will cut the heater circuit at a high limit temperature. 
This will still allow a bit high cost, but can prevents overheat and fire. This is enough for first step.

For light control (RQ4), bistable relays were used. Such a relay can change its state by a pulse. 
The hard wired wall switch pulse and smart system pulse are connected parallel. 
When smart system does not operate, wall switch can give the pulse to control lights properly (RQ8).

Smart system is powered by a Power Bank (RQ9). Charged battery allows hours of operation for Raspberry Pi.

Heater state is checked by a 230V~ digital input. 
The cheapest solution to change signal from 230V~ to 5V DC was to order an 5V 2A power supply board. :-)

## Software design

Most widely compatible interface is web browser. (RQ6) For this purpose, web server must be installed on Raspberry Pi.
User action are handled by JavaScript on client side and PHP code on server side (RQ2).

To be accessible from anywhere (RQ5), [Free DNS](https://freedns.afraid.org/) is used to have a domain name
what always point to my Raspberry Pi behind dynamic IP address.

Temperatures are read by crontab every 5 minutes, and values are stored in MySQL database. 
Lamp states are read by a demon every 200ms, and also stores real value in MySQL database. 
New value is only stored in database, if it is changed meantime. Database structure is in index.sql.

User interface is a simple responsive web page. 
User action is handled by JavaScript, and sent to server by Ajax method when needed. 
Normal user interface consist of index.php, index.css.

User interface updates values automatically in every second. 
This updates temperatures, heater state, and lamp states, and it is done by read from SQL database. 
Statistic values are read only at page load, not every 1 sec, due to data usage and speed considerations.

User action for temperature is stored in MySQL database only. Heater control runs in temperature read task. 

User actions for lamps has immediate effect to make pulse by pulse.php.

Interface between web page and hardware controller daemon is [shared memory](https://www.php.net/manual/en/book.shmop.php).

Control is only available for authenticated users (RQ10). 

![screenshot_01.png](https://github.com/butyi/nograd/blob/master/images/screenshot_01.png)

For other web page visitors the current outside temperature and its statistic is visible.

![screenshot_02.png](https://github.com/butyi/nograd/blob/master/images/screenshot_02.png)

## Components

### Hardware components

* Raspberry Pi

![rpi.png](https://github.com/butyi/nograd/blob/master/images/rpi.png)

* Temperature sensor

![temp_sensor.png](https://github.com/butyi/nograd/blob/master/images/temp_sensor.png)

* IO module (with MCP23017)

![images/io_board.png](https://github.com/butyi/nograd/blob/master/images/io_board.png)

* Bistable relay board

![bistable_relay_board.png](https://github.com/butyi/nograd/blob/master/images/bistable_relay_board.png)

* UPS board

![ups.png](https://github.com/butyi/nograd/blob/master/images/ups.png)

* Lithium battery

* High current relay for heater

![high_current_relay.png](https://github.com/butyi/nograd/blob/master/images/high_current_relay.png)

* 5V power supply for sense heater state

![power_supply_board.png](https://github.com/butyi/nograd/blob/master/images/power_supply_board.png)

### Software components
* LAMP Web Server (including PHP, MySQL)
* crontab
* I2C engine (for MCP23017)
* One-wire engine (for temperature sensor)

## Safety

For heating under and over, two mechanical thermostats are applied as temperature limiters.

The light system is completely disabled by a main switch when nobody is in the house. 
Therefore, additionally to bistable relay, no more safety action is needed.

Web control is only available from local network (wifi pass needed) or only for authenticated users. 

Ajax requests are only executed if key matches. The key is generated by server with short expiry time. 
This is against unauthenticated direct call of Ajax requests.

In worst case, I can call the neighbor to step to my house and I can tell him what to do for safe state. :-)

## Install

- Buy a Raspberry Pi. Note, I use an old one. So some my be different for newer RPIs.
- Install Raspbian image into its SD card.
- Connect monitor to HDMI port and keyboard to once USB port
- Switch on Raspberry Pi
- Run `sudo raspi-config`, change password, extend memory card size, enable I2C and one wire interface
- Attach IO extension board, I use IOPI from [AB Electronics UK](https://www.abelectronics.co.uk/)
- Run `lsmod` to check if i2c_bcm2835 is in the list
- Run `sudo apt-get install i2c-tools`
- Run `sudo nano /boot/config.txt`
  Only these two lines are needed for i2c on old RPI:
  `dtparam=i2c0=on`
  `dtparam=i2c_arm=on`
- Run `sudo i2cdetect -y 0`
  If board is pőroperly available, you should see this:  
     0  1  2  3  4  5  6  7  8  9  a  b  c  d  e  f  
00:          -- -- -- -- -- -- -- -- -- -- -- -- --  
10: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --  
20: 20 21 -- -- -- -- -- -- -- -- -- -- -- -- -- --  
30: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --  
40: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --  
50: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --  
60: -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- --  
70: -- -- -- -- -- -- -- --  
- Run `sudo systemctl restart apache2` to restart apache
- [Install LAMP](https://pchelp.ricmedia.com/setup-lamp-server-raspberry-pi-3-complete-diy-guide/3/)
- Create RAM disk
  - Run `cd /`
  - Run `sudo mkdir ram`
  - Run `sudo nano /etc/fstab`
  - Add line `tmpfs /ram tmpfs nodev,nosuid,size=10M 0 0` and save
  - Run `sudo mount -a` for re-mount drives
  - Run `df` for check if RAM drive is mounted correctly. If mounted, you will see a line like `tmpfs             10240       0   10240   0% /ram`
  - Test if `/ram` folder is really stored in RAM. Save a file here (`nano test.txt`) reboot and check folder. Proper if folder is empty after reboot.
  - Configure out.dat file into `/ram/out.dir` in config.php
- Get my files from github (Run `git init` and `git clone http://github.com/butyi/nograd` copy them to /var/www/html)
- Copy config.php and passcheck.php to /home/pi folder and update them with your specific content (database access, real passcheck algorithm). These files are just empty templates on GitHub.
- [Install FreeDNS](https://thelastmaimou.wordpress.com/2014/03/23/find-pi-everywhere-freedns-a-free-dynamic-dns-service/)
- Setup MySQL database. If you cannot log in see [this](https://askubuntu.com/questions/763336/cannot-enter-phpmyadmin-as-root-mysql-5-7)
- Import index.sql by phpmyadmin.
- Run `crontab -e` and 
  - add line `* * * * * php /var/www/html/read_temp_sensor.php >> ~/read_temp_sensor.log` to automatic read temperatures.
  - add line `@reboot php /var/www/html/readdigin.php &` to handle relays.
- Reboot Raspberry: `sudo reboot`
- Turn off Raspberry: `sudo shutdown -h now`

## License

This is free. You can do anything you want with it.
While I am using Linux, I got so many support from free projects, I am happy if I can help for the community.

## Keywords

Raspberry PI, Smart Home, Smart house, Apache, MySQL, PHP, Webpage, Nógrád, Almáskert, Weekend house

###### 2019 Janos Bencsik

