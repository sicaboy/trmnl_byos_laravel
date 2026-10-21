########################
# Base Image
########################
FROM serversideup/php:8.3-fpm-nginx-alpine@sha256:e68187a9528acae5e9002149496fa118a4ca6f96261f8c60ac8fbfb58147f752 AS base

LABEL org.opencontainers.image.source=https://github.com/usetrmnl/byos_laravel
LABEL org.opencontainers.image.description="Smart Display"
LABEL org.opencontainers.image.licenses=MIT

ARG APP_VERSION
ENV APP_VERSION=${APP_VERSION}

ENV AUTORUN_ENABLED="true"

# Switch to the root user so we can do root things
USER root

ENV PHP_MEMORY_LIMIT=-1
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-runtime-policy.ini

RUN install-php-extensions imagick
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true

# Install Chinese fonts for better UTF-8 support
RUN apk add --no-cache chromium \
    ttf-dejavu \
    ttf-liberation \
    ttf-linux-libertine \
    font-noto-cjk

# Set the working directory
WORKDIR /var/www/html

# Copy the application files
COPY --chown=www-data:www-data . /var/www/html
COPY --chown=www-data:www-data .env.example .env

# Install the composer dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

########################
# Assets Image
########################
FROM node:22-alpine AS assets

# Copy the application
COPY --from=base /var/www/html /app

# Set the working directory
WORKDIR /app

# Install the node dependencies and build the assets
RUN npm ci --no-audit \
    && npm run build

########################
# Production Image
########################
FROM base AS production

# Copy the assets from the assets image
COPY --chown=www-data:www-data --from=assets /app/public/build /var/www/html/public/build
COPY --chown=www-data:www-data --from=assets /app/node_modules /var/www/html/node_modules

# Drop back to the www-data user
USER www-data
