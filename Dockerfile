FROM yuim/unit:1.27.0-php8.1

RUN apt-get update && apt-get -y install curl ca-certificates libicu-dev libzip-dev libpq-dev && \
    docker-php-ext-install -j$(nproc) opcache intl zip pdo_pgsql && \
    apt-get clean && rm -rf /var/lib/apt/lists/* && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY zz-custom.ini /usr/local/etc/php/conf.d/
COPY --from=composer /usr/bin/composer /usr/bin/composer

# EC-CUBE release tag to build (https://github.com/EC-CUBE/ec-cube/releases).
# "latest" is resolved to the newest published release at build time.
ARG ECCUBE_VERSION=latest

USER unit
WORKDIR /www/app
RUN set -eux; \
    if [ "$ECCUBE_VERSION" = "latest" ]; then \
        ECCUBE_VERSION=$(curl -fsSL -o /dev/null -w '%{url_effective}' https://github.com/EC-CUBE/ec-cube/releases/latest | sed 's#.*/tag/##'); \
    fi; \
    echo "Building EC-CUBE ${ECCUBE_VERSION}"; \
    curl -fsSL "https://github.com/EC-CUBE/ec-cube/archive/refs/tags/${ECCUBE_VERSION}.tar.gz" -o /tmp/eccube.tar.gz; \
    tar xzf /tmp/eccube.tar.gz -C /www/app --strip-components=1; \
    rm /tmp/eccube.tar.gz
RUN composer clearcache && composer install --no-dev --no-interaction --optimize-autoloader && rm -f .env
USER root
COPY config.json /docker-entrypoint.d/
