
FROM composer:2.8.2 AS builder

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

RUN curl -sS https://getcomposer.org/installer | php \
  && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer

WORKDIR /
COPY /src/composer.json /
RUN composer install

FROM php:8.3.2-fpm

# Install required packages and PHP extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

#RUN useradd -m appuser
#USER appuser

COPY --from=builder /vendor /app/vendor


WORKDIR /app
COPY src/ .
