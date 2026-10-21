#!/bin/sh
set -eu
# The security release patches bundled extensions too (GD, Intl, SOAP, ...).
# Rebuild every installed bundled shared object, including currently disabled
# ones, so re-enabling one cannot restore an older vulnerable implementation.
test -f /tmp/php-modules-before || php -r 'echo implode("\n", get_loaded_extensions());' | sort > /tmp/php-modules-before
php -r 'if (extension_loaded("gd")) echo json_encode(gd_info());' > /tmp/php-security-gd-before
if command -v apk >/dev/null 2>&1; then
  apk add --no-cache --virtual .wiroo-security-ext-build $PHPIZE_DEPS freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev libxpm-dev libavif-dev libzip-dev icu-dev bzip2-dev libxslt-dev gmp-dev libxml2-dev libsodium-dev openssl-dev curl-dev oniguruma-dev
else
  test -f /tmp/php-security-saved-manual || apt-mark showmanual > /tmp/php-security-saved-manual
  apt-get update
  apt-get install -y --no-install-recommends $PHPIZE_DEPS libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libxpm-dev libavif-dev libzip-dev libicu-dev libbz2-dev libxslt1-dev libgmp-dev libxml2-dev libsodium-dev libssl-dev libcurl4-openssl-dev libonig-dev
fi
docker-php-source extract
extensions=''
for module in "$(php-config --extension-dir)"/*.so; do
  test -f "$module" || continue
  name="${module##*/}"
  name="${name%.so}"
  if [ -f "/usr/src/php/ext/$name/config.m4" ]; then
    extensions="$extensions $name"
  elif [ "$name" = uploadprogress ]; then
    # PECL uploadprogress is separate from PHP's bundled session extension.
    echo "Retaining third-party PECL extension uploadprogress"
  else
    echo "Cannot rebuild non-bundled extension: $name" >&2
    exit 1
  fi
done
case " $extensions " in
  *' gd '*)
    gd_options="$(php -r '$g=json_decode(file_get_contents("/tmp/php-security-gd-before"),true); foreach(array("FreeType Support"=>"--with-freetype","JPEG Support"=>"--with-jpeg","WebP Support"=>"--with-webp","XPM Support"=>"--with-xpm","AVIF Support"=>"--with-avif") as $k=>$v) if(!empty($g[$k])) echo $v," ";')"
    docker-php-ext-configure gd $gd_options
    ;;
esac
if [ -n "$extensions" ]; then docker-php-ext-install -j2 $extensions; fi
docker-php-source delete
php -r 'if (extension_loaded("gd")) echo json_encode(gd_info());' > /tmp/php-security-gd-after
diff -u /tmp/php-security-gd-before /tmp/php-security-gd-after
rm /tmp/php-security-gd-before /tmp/php-security-gd-after
