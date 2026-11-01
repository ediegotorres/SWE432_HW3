FROM php:8.3-apache

ENV PORT=10000

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -ri "s!Listen 80!Listen ${PORT}!g" /etc/apache2/ports.conf \
    && sed -ri "s!<VirtualHost \*:80>!<VirtualHost *:${PORT}>!g" /etc/apache2/sites-available/000-default.conf \
    && printf 'ServerName localhost\n' > /etc/apache2/conf-available/server-name.conf \
    && a2enconf server-name

WORKDIR /var/www/html
COPY . .

EXPOSE 10000
