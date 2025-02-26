FROM yuim/unit:1.27.0-php8.1

RUN apt-get update && apt-get -y install libicu-dev libzip-dev libpq-dev && \
    docker-php-ext-install -j$(nproc) opcache intl zip pdo_pgsql && \
    apt-get clean && rm -rf /var/lib/apt/lists/* && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY zz-custom.ini /usr/local/etc/php/conf.d/
COPY --from=composer /usr/bin/composer /usr/bin/composer
USER unit
WORKDIR /www/app
COPY --chown=unit:unit . .
RUN composer clearcache && composer install && rm -f .env
USER root
COPY config.json /docker-entrypoint.d/
