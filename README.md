<img align="top" width="250" src="doc/gridmap.png"><img align="top" width="250" src="doc/scripts.png"><img align="top" width="250" src="doc/gridstats.png">

### A collection of PHP utilities for opensimulator.

All together, these scripts allow you to dive into each and every script/texture of your grid. Access should be restricted to grid managers (use .htaccess).

##### gridmap.php : Show a map of your grid.

This script queries ROBUST or the database to get the list of regions and their parameters. It then computes grid bounds and prints a HTML table with links to ROBUST's maptiles.
 
[full size image](doc/gridmap.png)

##### gridstat.php : Show all statistics for your grid. 

This script queries ROBUST or the database to get the list of regions and their parameters. It then queries all simulators' monitoring module (/monitorstats/<region-uuid>) and prints a HTML table with statistics.

Links to scripts.php.

[full size image](doc/gridstats.png)

##### scripts.php : List region scripts.

This script queries the database for a list of all scripts contained in a region. It generates a table with script name, link name, root name and position.

Links to getasset.php.

[full size image](doc/scripts.png)

##### getasset.php : Dump a raw asset. 

Query the database for a given UUID and returns content. Used to retrieve scripts. Do not use for binary assets.

##### gettexture.php /  viewtexture.html : Display a texture.

Fetch a texture in database, convert it to a JPEG file, display it on a web page. Use either imagick or gmagick. Create manually the cache folder and chown it to the www user. viewtexture.html calls gettexture.php via XMLHttpRequest.

##### db_access.php : MySQL credentials.

Common to all scripts.

##### Dependencies :

- mysqlnd (apt install php-mysqlnd)- php-curl (apt install php-curl)
- php-xml (apt install php-xml)
- imagick (apt install php-imagick) or
- gmagick (apt install php-gmagick)
