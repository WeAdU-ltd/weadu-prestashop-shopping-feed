#!/bin/sh
# Local checks. Shared logic tests come from WeAdU-ltd/weadu-com-webflow tools/wea-323.
set -eu
cd "$(dirname "$0")"
cmp weadushoppingfeed/FeedAudit.php test/FeedAudit.php
find . -name "*.php" -not -path "./.git/*" -print0 | xargs -0 -n1 php -l >/dev/null
node --test --test-name-pattern="php" test/logic.test.js
echo "weadu-prestashop-shopping-feed ok"
