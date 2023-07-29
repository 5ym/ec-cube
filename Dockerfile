FROM alpine:3.16

RUN apk add --no-cache nginx php-fpm php-cli php-opcache php-phar php-iconv php-openssl php-intl php-tokenizer php-pdo php-xml php-curl php-dom php-zip \
    php-xmlwriter php-session php-mbstring && \
    wget https://raw.githubusercontent.com/composer/getcomposer.org/main/web/installer -O - -q | php -- --install-dir="/usr/local/bin/" --filename="composer" && \
    chown -R nginx:nginx /var/log/php8 && chown -R nginx:nginx /var/lib/nginx/html
COPY default.conf /etc/nginx/http.d/
COPY 99_custom.ini /etc/php81/conf.d/
WORKDIR /var/lib/nginx/html
COPY --chown=nginx:nginx . .
USER nginx
RUN composer clearcache && composer install
EXPOSE 80
CMD php-fpm8 && nginx -g "daemon off;"
