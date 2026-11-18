# Imagem multi-arch (amd64 e arm64). Tres etapas: frontend, dependencias PHP e imagem final.
FROM node:22-alpine AS front
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY public ./public
COPY resources ./resources
RUN npm run build

FROM composer:2 AS dependencias
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction

FROM serversideup/php:8.4-fpm-nginx
ENV AUTORUN_ENABLED=true \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/storage/dados/database.sqlite \
    SESSION_DRIVER=database \
    QUEUE_CONNECTION=database
COPY --chown=www-data:www-data --from=dependencias /app /var/www/html
COPY --chown=www-data:www-data --from=front /app/public/build /var/www/html/public/build
USER root
RUN mkdir -p /var/www/html/storage/dados && touch /var/www/html/storage/dados/database.sqlite \
 && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
USER www-data
VOLUME ["/var/www/html/storage/dados"]
