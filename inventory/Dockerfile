FROM php:8.2-apache

# Enable mysqli extension for your DB connection
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy all project files into Apache's web root
COPY . /var/www/html/

# Set Apache document root to your project root
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Allow .htaccess overrides
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|g' \
    /etc/apache2/sites-available/000-default.conf && \
    a2enmod rewrite

EXPOSE 80
