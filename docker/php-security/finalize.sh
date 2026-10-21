#!/bin/sh
set -eu
saved_manual="$(cat /tmp/php-security-saved-manual 2>/dev/null || true)"
if command -v apk >/dev/null 2>&1; then
  for package in .wiroo-security-build .wiroo-security-ext-build; do
    if apk info -e "$package" >/dev/null; then apk del "$package"; fi
  done
else
  apt-mark auto '.*' >/dev/null
  apt-mark manual $saved_manual >/dev/null
  find /usr/local -type f -executable -exec ldd '{}' ';' 2>/dev/null \
    | awk '/=>/ { print $(NF-1) }' | sort -u \
    | xargs -r dpkg-query --search 2>/dev/null \
    | awk -F ': ' 'NF == 2 && $1 !~ / / { print $1 }' \
    | sort -u | xargs -r apt-mark manual
  apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false
  rm -rf /var/lib/apt/lists/*
fi
test "$(php -r 'echo PHP_VERSION;')" = "$PHP_SECURITY_VERSION"
php -r 'echo implode("\n", get_loaded_extensions());' | sort > /tmp/php-modules-after
diff -u /tmp/php-modules-before /tmp/php-modules-after
rm -f /tmp/php-modules-before /tmp/php-modules-after /tmp/php-security-saved-manual
