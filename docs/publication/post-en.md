# First message of the topic (English)

Draft. Do not post, do not attach a ZIP, and do not replace the 1.0.0 release: Jeff validates each release.

Section: General forum → "Free Modules & Themes". Attach `weadushoppingfeed.zip` to the message.

---

**Title:** [FREE MODULE] WeAdU Shopping feed check: check your catalogue before Google Shopping (1.7.8+)

Hello,

I'd like to share a free module that checks, in the back office, the catalogue fields Google Shopping uses.

**What it does**
- Reads the active products of the shop (200 items by default, 500 at most, combinations included). If the catalogue is larger, the page says how many items were not read.
- Flags per product: empty title, description, link, image, availability, or price; title over 150 characters; price of zero; GTIN with a wrong check digit; duplicate reference. As warnings: short or all-capitals title, promotional wording, price without currency, empty brand, empty GTIN and MPN.
- Suggests a title that starts with the brand when the brand is missing.
- Checks each combination, with tax-included and sale prices, and an edit link per product.
- Exports the result as CSV.

**What it does not do**
- It does not modify your products and sends nothing to Google or anywhere else.
- Prices include tax by default. The regular price is the catalogue price, and the sale price is what the customer pays when it is lower, including a fixed specific price. For a United States or Canada feed, choose tax excluded. This module does not calculate US or Canadian sales tax. Prices are computed for the default customer group.
- A product with no finding is not an approval from Google.

**Installation**: Modules → Module Manager → Upload a module → select the attached ZIP, then "Configure".

Declared compatibility: PrestaShop 1.7.8 and later. AFL-3.0 licence.

Disclosure: I work at WeAdU, the agency that made this module. It is free.

Questions and feedback are welcome in this topic.
