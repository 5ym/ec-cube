FROM alpine:3.17

RUN apk add --no-cache nginx php-fpm php-cli php-opcache php-phar php-iconv php-openssl php-curl php-intl php-zip php-tokenizer php-xml php-dom php-xmlwriter \
    php-mbstring php-pdo_pgsql php-session && \
    wget https://raw.githubusercontent.com/composer/getcomposer.org/main/web/installer -O - -q | php -- --install-dir="/usr/local/bin/" --filename="composer" && \
    chown -R nginx:nginx /var/log/php81 && chown -R nginx:nginx /var/lib/nginx/html
COPY default.conf /etc/nginx/http.d/
COPY zz-custom.conf /etc/php81/php-fpm.d/
COPY zz-custom.ini /etc/php81/conf.d/
WORKDIR /var/lib/nginx/html
COPY --chown=nginx:nginx . .
RUN composer clearcache && composer install
EXPOSE 80
CMD php-fpm81 && nginx -g "daemon off;"