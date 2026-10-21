#!/bin/sh
set -eu
case "$PHP_SECURITY_VERSION:$PHP_SECURITY_SHA256" in
  8.5.12:08bc2eba2a588654ade1357174aa646b761556c862dda0678345ee29ed481209|8.3.36:dd54693aa334c5c4d72e9aee96b082c2473750a18a6c1d105789c93a3b1692fd|8.2.35:5737b98601fe5ab1b8c5db0c82ed3158acb9283ed3081ffe2faffb9ac81455e6) ;;
  *) echo 'Unapproved PHP security release' >&2; exit 1 ;;
esac
test "$(php -r 'echo PHP_MAJOR_VERSION,".",PHP_MINOR_VERSION;')" = "${PHP_SECURITY_VERSION%.*}"
php -r 'echo implode("\n", get_loaded_extensions());' | sort > /tmp/php-modules-before
configure_options="$(php-config --configure-options)"
configure_command="$(php -i | sed -n 's/^Configure Command => //p')"
test -n "$configure_command"
if command -v apk >/dev/null 2>&1; then
  apk add --no-cache --virtual .wiroo-security-build $PHPIZE_DEPS argon2-dev coreutils curl-dev gnu-libiconv-dev libsodium-dev libxml2-dev linux-headers oniguruma-dev openssl-dev readline-dev sqlite-dev
  rm -f /usr/include/iconv.h
else
  apt-mark showmanual > /tmp/php-security-saved-manual
  apt-get update
  apache_deps=''
  case "$configure_options" in *--with-apxs2*) apache_deps='apache2-dev' ;; esac
  apt-get install -y --no-install-recommends $PHPIZE_DEPS $apache_deps libargon2-dev libcurl4-openssl-dev libonig-dev libreadline-dev libsodium-dev libsqlite3-dev libssl-dev libxml2-dev zlib1g-dev
fi
curl -fSL --retry 3 "https://www.php.net/distributions/php-$PHP_SECURITY_VERSION.tar.xz" -o /usr/src/php.tar.xz
echo "$PHP_SECURITY_SHA256  /usr/src/php.tar.xz" | sha256sum -c -
docker-php-source delete
docker-php-source extract
cd /usr/src/php
export CFLAGS="$PHP_CFLAGS" CPPFLAGS="$PHP_CPPFLAGS" LDFLAGS="$PHP_LDFLAGS"
# Options originate from the installed trusted image's php-config, not input.
eval "$configure_command"
make -j2
make install
docker-php-source delete
