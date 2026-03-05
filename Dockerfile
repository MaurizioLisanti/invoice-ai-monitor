# ─── Stage 1: Crea lo skeleton Laravel 11 completo ────────────────
# Produce: artisan, public/, bootstrap/, config/, app/Http/Controllers/Controller.php,
#          tests/TestCase.php, phpunit.xml, storage/, .gitignore, ecc.
# [A-SC1] Richiede accesso internet durante il build (composer create-project)
FROM php:8.4-cli-alpine AS laravel-scaffold

RUN apk add --no-cache git zip unzip
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer
WORKDIR /laravel

RUN composer create-project "laravel/laravel:^11.0" . \
    --prefer-dist \
    --no-interaction \
    --no-scripts \
    --no-plugins

# ─── Stage 2: Immagine runtime PHP-FPM ────────────────────────────
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
        curl libpng-dev libxml2-dev zip unzip git bash shadow \
    && docker-php-ext-install pdo pdo_mysql opcache

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www

# Passo 1: Copia il full Laravel skeleton (artisan, public/, bootstrap/, config/, storage/, ecc.)
COPY --from=laravel-scaffold /laravel .

# Passo 2: Override con il nostro composer.json (dipendenze di progetto)
COPY composer.json .

# Passo 3: Installa dipendenze (layer cached se composer.json non cambia)
RUN composer install \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# Passo 4: Overlay dei file applicativi del progetto
#          (sovrascrive defaults Laravel con i nostri: routes/, app/, database/, resources/, config/invoice.php, tests/)
COPY . .

# Passo 5: Ottimizzazione finale
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
