# symprot 

## About

## Setup 
(i assume linux and a terminal)  :
## prerequisites: 
  1. A webserver (e.g. Apache, nginx), configured and ready - i assume you have that up and running :) 
  2. PHP with modules PDO, Ctype, iconv, PCRE, Session, SimpleXML, and Tokenizer installed
  3. sqlite as database (e.g. for Ubuntu `sudo apt-get install sqlite3, php-sqlite3)
  4. [composer] (https://getcomposer.org/download/)
  5. npm & yarn if you want to develp and extend
## in terminal:  
`clone repository: git clone https://github.com/medmen/symprot.git` - this will create a folder "symprot" in your working directory

`cd symprot`

`composer install` - this will install the symfony framework and and several modules used in this app

## initialize the Database:
`php bin/console doctrine:database:create` - this will create the database file (the app uses sqlite as database). 
if the command fails with an error like "The database driver is not supported by the Doctrine DBAL library", you need to install the sqlite driver for php, 
e.g. for Ubuntu `sudo apt-get install php-sqlite3`

`php bin/console doctrine:migrations:migrate` - this will create the database tables and relations, but will not populate the database with any data
if the command fails, you can try to run `php bin/console doctrine:schema:update --force` instead, which will also create the tables, but without any migrations.

### optional: populate the Database:
`php bin/console seed:load` - this will load some example data into the database, so you can test the app