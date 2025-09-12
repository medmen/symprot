# symprot 

## About
This app helps inspect and document Siemens CT/MRT exam protocols.

### Why? 
I have been working with Siemens (now healthineers) CT and MRI machines for many years now.
When it comes to getting info about examination protocols out of these machines, it's been long and hard work all the time.

There are good reasons to get those protocols documented:
- documenting standard protocols and sync them between machines (if you have more than 1 of course)
- get a printout for SOPs
- describe your parameters when doing scientific studies
- compare and enhance your exam protocols

The Problem:
Siemens machines i know can export their exam protocols as database files in a proprietary format which 
seem unreadable to me.
There is however a way to "print" protocols in PDF or (newer machines) xml format. 
These printouts contain all possible settings and parameters for every single step and sequence and are therefor 
quite large and in esence useless for above tasks.

The Solution:
Symprot 
- reads those printouts 
- tries to extract the protocols and parameters you need to know 
- displays the results in different formatting according to your needs

You can edit the protocol parameters to be shown. 
You can switch output formats
If you are familiar with PHP programming, you can easily 
- add new converters for Phillips, GE, Samsung or Toshiba (if they do printouts as Siemens does)
- add output formatters as you like (JSON, XML, braille, you name it)



### Recent enhancements:
- Stable per-device (Geraet) sorting for Parameters with move Top/Up/Down/Bottom actions that keep a contiguous sort_position. Lists now sort predictably, also when filtered by modality (Geraet).
- Visual feedback when moving a parameter: after a move, the target row is highlighted and smoothly scrolled into view.
- Content Security Policy (CSP): inline JS/CSS disabled by default; per-request nonces are used for required inline blocks (e.g., importmap). The highlight assets were moved to external files and are loaded without 'unsafe-inline'.

## Setup 
(i assume linux and a terminal)  :
### prerequisites: 
  1. A webserver (e.g. Apache, nginx), configured and ready - i assume you have that up and running :) 
  2. PHP with modules PDO, Ctype, iconv, PCRE, Session, SimpleXML, and Tokenizer installed
  3. sqlite as database (e.g. for Ubuntu `sudo apt-get install sqlite3, php-sqlite3)
  4. [composer] (https://getcomposer.org/download/)
  5. npm & yarn if you want to develp and extend
### in terminal:  
`clone repository: git clone https://github.com/medmen/symprot.git` - this will create a folder "symprot" in your working directory

`cd symprot`

`composer install` - this will install the symfony framework and and several modules used in this app

### initialize the Database:
`php bin/console doctrine:database:create` - this will create the database file (the app uses sqlite as database). 
if the command fails with an error like "The database driver is not supported by the Doctrine DBAL library", you need to install the sqlite driver for php, 
e.g. for Ubuntu `sudo apt-get install php-sqlite3`

`php bin/console doctrine:migrations:migrate` - this will create the database tables and relations, but will not populate the database with any data
if the command fails, you can try to run `php bin/console doctrine:schema:update --force` instead, which will also create the tables, but without any migrations.

### optional: populate the Database:
`php bin/console seed:load` - this will load some example data into the database, so you can test the app


## Security
Symprot is a web-app, you need a webserver to run it. 
Web things are by design accessible from "everywhere", so security problems may lead to data loss, 
malfunction and potentially infecting your webserver with malware. 
Symprot was built to work, security is not a primary goal! 
But it is build on a solid foundation (Symfony framework) and comes with a CSP, so basic security measures are taken.
If you happen to find an issue, please file a bug report (ideally with a bugfix pull request).

## Help?
This is a one-man hobby project, please don't expect professional support.
If things don't work, feel free to file a report at https://github.com/medmen/symprot/issues
I would love to see enhancements and pull requests too, feel free to contribute :)