FROM phpmyadmin:5.2.3-apache

# Pin the embedded runtime independently of the phpMyAdmin application tag.
ENV PHP_VERSION=8.3.36
COPY docker/php-security/rebuild.sh /usr/local/bin/wiroo-rebuild-php
RUN PHP_SECURITY_VERSION=8.3.36 \
    PHP_SECURITY_SHA256=dd54693aa334c5c4d72e9aee96b082c2473750a18a6c1d105789c93a3b1692fd \
    sh /usr/local/bin/wiroo-rebuild-php
COPY docker/php-security/extensions.sh /usr/local/bin/wiroo-rebuild-extensions
RUN sh /usr/local/bin/wiroo-rebuild-extensions
COPY docker/php-security/finalize.sh /usr/local/bin/wiroo-finalize-php
RUN PHP_SECURITY_VERSION="$PHP_VERSION" sh /usr/local/bin/wiroo-finalize-php \
    && rm /usr/local/bin/wiroo-rebuild-php /usr/local/bin/wiroo-rebuild-extensions /usr/local/bin/wiroo-finalize-php
