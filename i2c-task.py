#!/usr/bin/python
# daemon to access MCP23017 I2C Port Expander through I2C bus. www.butyi.hu  2020.03.08.

import os, sys
import smbus #sudo apt-get install python-smbus
import time

in_dat = "/ram/in.dat"
out_dat = "/ram/out"

# Wait 30s for system initialisation
time.sleep(30)
sys.stdout.write("30s delay is elapsed. Daemon starts. " + os.linesep)
sys.stdout.flush()

# Open I2C bus
bus = smbus.SMBus(0)

# Device address
DEVICE = 0x20

# Register addresses
IODIRA = 0x00 # data direction for outputs
IODIRB = 0x01 # data direction for inputs
GPIOB  = 0x13 # port of inputs
OLATA  = 0x14 # latch of outputs

# Init outputs
bus.write_byte_data(DEVICE,OLATA,0x00) # switch off all 8 bits
bus.write_byte_data(DEVICE,IODIRA,0x00) # set all 8 bits as output

# Init inputs
bus.write_byte_data(DEVICE,IODIRB,0xFF) # set all 8 bits as input
in_byte_prev = 999 # ensure not equal for first write

# Endless task to read/write I2C bus
while True:
  time.sleep(0.05) # leave time for other tasks/threads/daemons

  # INPUTS
  in_byte = bus.read_byte_data(DEVICE,GPIOB) # read from HW
  if in_byte_prev != in_byte: # if new value is different from previous
    fw = open(in_dat,'w')
    fw.write('%02X'%in_byte) # write the string with format (from eg. dec 255 to string "FF")
    fw.close()
  in_byte_prev = in_byte # save previous value for change detection in the next cycle

  # OUTPUTS
  out_byte = 0;
  for x in range(8): # read 8 files
    if os.path.exists(out_dat + str(x)): # which file is not found, keep 0 (off) in the bit
      fr = open(out_dat + str(x))
      content = fr.read(1)
      fr.close()
      if 0 < len(content): # when read was on not completely written file
        if content == '1': # if first character of file is '1'
          out_byte |= (1 << x) # set the bit to switch on the output port
  bus.write_byte_data(DEVICE,OLATA,out_byte) # write byte to outputs
