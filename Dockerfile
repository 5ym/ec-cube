FROM dunglas/frankenphp:php8.3-alpine

RUN install-php-extensions intl zip pdo_pgsql opcache && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY zz-custom.ini $PHP_INI_DIR/conf.d/
COPY Caddyfile /etc/frankenphp/Caddyfile
COPY --from=composer /usr/bin/composer /usr/bin/composer

# EC-CUBE release tag to build (https://github.com/EC-CUBE/ec-cube/releases),
# or a full commit SHA to build an unreleased state of an upstream branch.
# "latest" is resolved to the newest published release at build time.
ARG ECCUBE_VERSION=latest

WORKDIR /app
RUN set -eux; \
    version="$ECCUBE_VERSION"; \
    if [ "$version" = "latest" ]; then \
        version=$(curl -fsSL -o /dev/null -w '%{url_effective}' https://github.com/EC-CUBE/ec-cube/releases/latest | sed 's#.*/tag/##'); \
    fi; \
    if echo "$version" | grep -Eq '^[0-9a-f]{40}$'; then ref="$version"; else ref="refs/tags/$version"; fi; \
    echo "Building EC-CUBE ${version}"; \
    curl -fsSL "https://github.com/EC-CUBE/ec-cube/archive/${ref}.tar.gz" -o /tmp/eccube.tar.gz; \
    tar xzf /tmp/eccube.tar.gz -C /app --strip-components=1; \
    rm /tmp/eccube.tar.gz
RUN composer install --no-dev --no-interaction --optimize-autoloader && rm -f .env && composer clear-cache
