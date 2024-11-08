FROM composer AS builder

RUN curl -sS https://getcomposer.org/installer | php \
  && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer

WORKDIR /
COPY composer.json /
RUN composer install

FROM php:8.3.2-fpm

COPY --from=builder /vendor /app/vendor
WORKDIR /app
COPY src/ .

CMD ["php", "run.php"]
