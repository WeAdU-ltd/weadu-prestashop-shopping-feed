# Before publication

Nothing in this folder may be posted before every box below is ticked.

- [ ] Pull requests #1 and #2 reviewed independently and merged.
- [ ] `sh test.sh` green on `main` (and the GitHub Actions workflow enabled: copy `ci/github-actions-test.yml` to `.github/workflows/test.yml` with a token that has the `workflow` scope; it runs the tests on PHP 7.4, 8.1, and 8.3).
- [ ] End-to-end test on a real PrestaShop shop (1.7.8 and 8.x) with real products: install, configure, run the check, download the CSV, uninstall. The current tests use stand-ins for the PrestaShop classes.
- [ ] Known issues fixed or accepted (both reported by `test/module_test.php`):
  - on PHP 8.4, `fputcsv()` in `FeedAudit.php` raises a deprecation notice because the `$escape` argument is not passed;
  - the fallback `'new' AS condition` (used only when the `condition` column is missing) uses a MySQL reserved word without backticks.
- [ ] Optional: the interface strings use a custom `t()` helper instead of PrestaShop's `$this->l()` / translation system. Decide whether that is acceptable for the forum audience.
- [ ] Release ZIP built with the `weadushoppingfeed` folder at its root and attached to a GitHub release.
- [ ] ZIP and GitHub link sent to the person who posts.
