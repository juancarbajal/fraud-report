FROM php:7.4-apache
COPY src/ /var/www/html/
EXPOSE 80

RUN docker-php-source extract 
RUN apt-get update \
    && yes | apt-get install unixodbc\
    && yes | apt-get install unixodbc-dev
RUN pecl install sqlsrv \
    && pecl install pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv
#CMD [ "php", "./src/public/index.php" ]