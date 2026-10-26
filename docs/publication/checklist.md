# Before publication

Nothing in this folder may be posted before Jeff validates the release. Do not open a forum topic, do not attach a ZIP, and do not replace the public 1.0.0 release from this branch.

- [x] Module version in code is 1.2.0 (not a GitHub release).
- [x] `sh test.sh` covers the fixed specific price, the unread count, and the United States / Canada tax warning.
- [ ] GitHub Actions is green on this pull request (workflow: `.github/workflows/test.yml`, PHP 7.4, 8.1, and 8.3).
- [ ] End-to-end test on a real PrestaShop shop (1.7.8, 8.x, and 9.x) with real products: install, configure, run the check, download the CSV, uninstall. The current tests use stand-ins for the PrestaShop classes.
- [ ] On that shop, confirm a fixed specific price still shows the catalogue price, a catalogue larger than the limit shows the unread count, and tax-excluded mode matches a United States or Canada feed.
- [ ] Jeff validates this release.
- [ ] Release ZIP built with the `weadushoppingfeed` folder at its root and attached to a new GitHub release. Do not replace or delete the 1.0.0 release.
- [ ] ZIP and GitHub link sent to the person who posts, with the French draft in `post-fr.md`.
