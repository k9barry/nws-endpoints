FROM composer as builder
WORKDIR /
COPY . /
RUN composer install

FROM adhocore/phpfpm:8.3

COPY --from=builder /vendor /vendor

#RUN \
# setup
#apk add -U $PHPIZE_DEPS \
#
# if it is in pecl: \
#&& docker-pecl-ext-install grpc phalcon swoole \
# && apk del $PHPIZE_DEPS \
#
# if it is in php ext: \
#&& docker-php-source extract && docker-php-ext-install-if dba \
# && docker-php-source delete
