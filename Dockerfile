
FROM composer:2.8.2 AS builder

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

RUN curl -sS https://getcomposer.org/installer | php \
  && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer

WORKDIR /
COPY /src/composer.json /
RUN composer install

FROM php:8.3.2-fpm

#RUN pecl install xdebug && docker-php-ext-enable xdebug

#RUN useradd -m appuser
#USER appuser

COPY --from=builder /vendor /app/vendor


WORKDIR /app
COPY src/ .
