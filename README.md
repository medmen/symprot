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

## initialize and populate Database:
`php bin/console doctrine:migrations:migrate`
