# Webcars — PHP 8.3 + Apache (used by docker-compose.yml)
FROM php:8.3-apache

# PDO MySQL for the database, GD + EXIF for re-saving uploaded photos without their metadata
RUN apt-get update \
    && apt-get install -y --no-install-recommends libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo_mysql gd exif \
    && rm -rf /var/lib/apt/lists/*

# Let the .htaccess files (uploads/, includes/, database/) take effect
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n</Directory>\n' > /etc/apache2/conf-available/webcars.conf \
    && a2enconf webcars

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/uploads
