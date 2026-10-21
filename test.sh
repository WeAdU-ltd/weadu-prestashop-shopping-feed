#!/bin/sh
# Local checks: PHP lint, shared fixtures through the PHP port, CSV export, license headers, logos.
set -eu
cd "$(dirname "$0")"
find . -name "*.php" -not -path "./.git/*" -print0 | xargs -0 -n1 php -l >/dev/null
node --test test/
php test/csv_test.php
for f in $(find weadushoppingfeed -name "*.php"); do grep -q "@license   https://opensource.org/licenses/AFL-3.0" "$f" || { echo "missing license header: $f"; exit 1; }; done
php -r '$s=getimagesize("weadushoppingfeed/logo.png"); exit($s[0]==32&&$s[1]==32?0:1);'
php -r '$s=getimagesize("weadushoppingfeed/views/img/logo-57.png"); exit($s[0]==57&&$s[1]==57?0:1);'
php -r '$x=simplexml_load_file("weadushoppingfeed/config.xml"); $m=file_get_contents("weadushoppingfeed/weadushoppingfeed.php"); exit(strpos($m, (string) $x->description)!==false?0:1);'
echo "weadu-prestashop-shopping-feed ok"
