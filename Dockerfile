FROM composer AS builder

RUN curl -sS https://getcomposer.org/installer | php \
  && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer

#RUN apk update && apk add curl
  
WORKDIR /
COPY composer.json /
RUN composer install

FROM php:8.3.2-fpm

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug
    
COPY --from=builder /vendor /app/vendor
WORKDIR /app
COPY src/ .

#CMD ["php", "run.php"]
