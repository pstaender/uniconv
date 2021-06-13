FROM thecodingmachine/php:8.0-v4-apache
COPY config/ /var/www/html/config/
COPY app/ /var/www/html/app/
COPY converter/ /var/www/html/converter/
COPY index.php /var/www/html/
COPY .htaccess /var/www/html/
COPY composer.json /var/www/html/
COPY composer.lock /var/www/html/
RUN composer install

