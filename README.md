![alt text](doc/gridmap-small.jpg "gridpam") 
![alt text](doc/gridstat-small.jpg "gridstat")

[full size map](doc/gridmap.jpg)
[full size stats](doc/gridstats.jpg)

### Sparse collection of utilities for opensimulator.


##### gridmap.php : Show a map of your grid.

This script queries ROBUST or the database to obtain the list of regions and their parameters. It then computes grid bounds and prints a HTML table with images pointing to ROBUST's maptiles. 


##### gridstat.php : Show all statistics for your grid. 

This script queries ROBUST or the database to obtain the list of regions and their parameters. It then queries all simulators' monitoring module (/monitorstats/<region-uuid>) and prints a HTML table with statistics.

Links to scripts.php.

#### scripts.php : List region scripts.

This script queries the database for a list of all scripts contained in a region. It generates a table with script name, link name, root name and position.

Links to getasset.php.

#### getasset.php : Dump a raw asset. 

Queries tha database for a given UUID and returns content. Used to retrieve scripts. Do not use for binary assets.

#### db_access.php : MySQL credentials.

Common to all scripts.

All together, these scripts allow you to dive into each and every script source code in your grid. Access should be granted to grid managers only (use .htaccess).