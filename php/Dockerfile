FROM php:8.2-apache

ARG APP_VERSION=1.0.0
LABEL org.opencontainers.image.version=$APP_VERSION
LABEL org.opencontainers.image.title="Renforcement D-veloppement Web - App"

# Install system packages and PHP extensions
RUN apt-get update \
	&& apt-get install -y --no-install-recommends libzip-dev unzip git zlib1g-dev libpng-dev libjpeg-dev libonig-dev \
	&& docker-php-ext-install mysqli pdo_mysql zip gd \
	&& a2enmod rewrite \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy application code
COPY www/ /var/www/html/

# Ensure uploads directory exists and is writable
RUN mkdir -p /var/www/html/uploads \
	&& chown -R www-data:www-data /var/www/html/uploads \
	&& chmod -R 775 /var/www/html/uploads

EXPOSE 80

CMD ["apache2-foreground"]
