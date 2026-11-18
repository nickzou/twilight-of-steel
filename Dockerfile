# syntax=docker/dockerfile:1

# Stage 1: resolve Composer-managed plugins (WPackagist) into plugins/
FROM composer:2 AS plugins
WORKDIR /app
COPY composer.json composer.lock ./
COPY plugins/ plugins/
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Stage 2: WordPress with plugins and theme baked in
FROM wordpress:7.1-php8.4-apache
COPY --from=plugins --chown=www-data:www-data /app/plugins/ /var/www/html/wp-content/plugins/
COPY --chown=www-data:www-data twilight-of-steel/ /var/www/html/wp-content/themes/twilight-of-steel/
