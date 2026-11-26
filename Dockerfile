# syntax=docker/dockerfile:1

# Stage 1: resolve Composer-managed plugins (WPackagist) into plugins/, and
# libraries (Carbon Fields) into the core plugin's vendor/ (see composer.json).
FROM composer:2 AS plugins
WORKDIR /app
COPY composer.json composer.lock ./
COPY plugins/ plugins/
RUN composer install --no-dev --no-scripts --optimize-autoloader --prefer-dist --no-interaction

# Stage 2: build the core plugin's custom blocks (src/blocks → build/).
# Dependencies' install scripts are skipped: the project's own postinstall
# copies front-end files into the theme, which this stage doesn't ship.
FROM node:22-alpine AS blocks
WORKDIR /app
RUN corepack enable
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile --ignore-scripts
COPY plugins/twilight-of-steel-core/src/ plugins/twilight-of-steel-core/src/
# mkdir: with no blocks yet, the build writes nothing, and the COPY below needs the folder.
RUN pnpm build && mkdir -p plugins/twilight-of-steel-core/build

# Stage 3: WordPress with plugins and theme baked in
FROM wordpress:7.1-php8.4-apache
COPY --from=plugins --chown=www-data:www-data /app/plugins/ /var/www/html/wp-content/plugins/
COPY --from=blocks --chown=www-data:www-data /app/plugins/twilight-of-steel-core/build/ /var/www/html/wp-content/plugins/twilight-of-steel-core/build/
COPY --chown=www-data:www-data twilight-of-steel/ /var/www/html/wp-content/themes/twilight-of-steel/

# The uploads volume takes its ownership from this directory when first created.
# Without it Docker creates the mount point as root and WordPress can't write.
RUN install -d -o www-data -g www-data /var/www/html/wp-content/uploads
