# Set the base image for subsequent instructions
FROM php:7.2

# Update packages
RUN apt-get update

# Install PHP and composer dependencies
RUN apt-get install -qq git curl libmcrypt-dev libjpeg-dev libpng-dev libfreetype6-dev libbz2-dev

# Clear out the local repository of retrieved package files
RUN apt-get clean

# add url php extension installer
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install needed extensions
# Here you can install any other extension that you need during the test and deployment process
RUN pecl install mcrypt-1.0.2
RUN docker-php-ext-enable mcrypt
RUN docker-php-ext-install zip
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions gd xdebug
RUN pecl install mongodb
RUN docker-php-ext-enable mongodb

# Install Composer
RUN curl --silent --show-error https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Laravel Envoy
RUN composer global require "laravel/envoy=~2.7"
