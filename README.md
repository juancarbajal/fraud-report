Sistema de detección de fraudes en una base de datos de compras. 

# Docker 
docker pull php

## Dockerfile
FROM php:7.4-cli
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp
RUN docker-php-source extract 
RUN apt-get update \
    && yes | apt-get install unixodbc\
    && yes | apt-get install unixodbc-dev
RUN pecl install sqlsrv \
    && pecl install pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv
CMD [ "php", "./src/public/index.php" ]


docker build -t sugo-fraudes-app .
#docker run -it --rm --name my-sugo-fraudes-app sugo-fraudes-app
docker run -d --name my-sugo-fraudes-app sugo-fraudes-app

docker run -d -p 80:80 --name my-apache-php-app -v "$PWD":/var/www/html php:7.2-apache

## new version

docker build -t my-php-web-app .
docker run -p 8080:80 --rm -it --name my-sugo-fraudes-app my-php-web-app:latest


## Create 
docker build -t sugo-fraudes-app .


## Mac 
brew install autoconf

## Validate instalation
ls /Applications/MAMP/bin/php/php7.2.22/include/php/ext

## Install 
 install sqlsrv pdo_sqlsrv
 /Applications/MAMP/bin/php/php7.2.22/bin/pecl install sqlsrv pdo_sqlsrv


## Configure 
