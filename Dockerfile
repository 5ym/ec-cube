FROM dunglas/frankenphp:php8.3-alpine

RUN install-php-extensions intl zip pdo_pgsql opcache && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY zz-custom.ini $PHP_INI_DIR/conf.d/
COPY Caddyfile /etc/frankenphp/Caddyfile
COPY --from=composer /usr/bin/composer /usr/bin/composer

# EC-CUBE release tag to build (https://github.com/EC-CUBE/ec-cube/releases).
# "latest" is resolved to the newest published release at build time.
ARG ECCUBE_VERSION=latest

WORKDIR /app
RUN set -eux; \
    if [ "$ECCUBE_VERSION" = "latest" ]; then \
        ECCUBE_VERSION=$(curl -fsSL -o /dev/null -w '%{url_effective}' https://github.com/EC-CUBE/ec-cube/releases/latest | sed 's#.*/tag/##'); \
    fi; \
    echo "Building EC-CUBE ${ECCUBE_VERSION}"; \
    curl -fsSL "https://github.com/EC-CUBE/ec-cube/archive/refs/tags/${ECCUBE_VERSION}.tar.gz" -o /tmp/eccube.tar.gz; \
    tar xzf /tmp/eccube.tar.gz -C /app --strip-components=1; \
    rm /tmp/eccube.tar.gz
RUN composer install --no-dev --no-interaction --optimize-autoloader && rm -f .env && composer clear-cache
