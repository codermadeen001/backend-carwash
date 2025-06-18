# Use official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpq-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql zip gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory to Laravel root
WORKDIR /var/www/html

# Copy only minimum required files for composer first
COPY composer.json composer.lock artisan ./

# Install Composer (without running scripts yet)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader

# Copy the rest of the application
COPY . .

# Set all environment variables directly
ENV APP_NAME=Laravel
ENV APP_ENV=production
ENV APP_KEY=base64:2sYKezv85mSU5iU/q1Ei2bQDXfQjVYjSdvdGwqys2OI=
ENV APP_DEBUG=true
ENV APP_URL=https://backend-carwash-mx6p.onrender.com
ENV LOG_CHANNEL=stderr
ENV LOG_DEPRECATIONS_CHANNEL=null
ENV LOG_LEVEL=error
ENV DB_CONNECTION=pgsql
ENV DB_HOST=dpg-d198hoffte5s73c4k5eg-a.frankfurt-postgres.render.com
ENV DB_PORT=5432
ENV DB_DATABASE=car_wash_pmdq
ENV DB_USERNAME=inno
ENV DB_PASSWORD=DMD0EffOkiI5DhaIKHtWqNQPEOrLBQlA
ENV DB_SSL_MODE=require
ENV SESSION_DRIVER=file
ENV SESSION_LIFETIME=120
ENV SESSION_DOMAIN=.onrender.com
ENV SANCTUM_STATEFUL_DOMAINS=https://backend-carwash-mx6p.onrender.com,localhost
ENV MAIL_MAILER=smtp
ENV MAIL_HOST=smtp.gmail.com
ENV MAIL_PORT=587
ENV MAIL_USERNAME=syeundainnocent@gmail.com
ENV MAIL_PASSWORD=vwuergurzyjucjmc
ENV MAIL_ENCRYPTION=tls
ENV MAIL_FROM_ADDRESS=noreply@yourcarwash.com
ENV MAIL_FROM_NAME="Auto Clean"
ENV JWT_SECRET=JYXM3dcQQmmXZhONKMpQ9oLjK65LENpRKyJ3OpjYHVhLgZWebyoE3iG7Wu9Txsau
ENV CLOUDINARY_URL=cloudinary://769447669581899:SMXcoOapJt4KElCoVzbCJ_SzIqM@dadcnkqbg

# Set correct permissions (non-root user)
RUN useradd -u 1000 -d /var/www/html -s /bin/bash www-user && \
    chown -R www-user:www-user /var/www/html && \
    chmod -R 775 storage bootstrap/cache

# Run composer scripts as non-root user
USER www-user
RUN composer dump-autoload --optimize && \
    composer run-script post-autoload-dump

# Switch back to root for Apache
USER root

# Update Apache DocumentRoot to point to Laravel's /public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]