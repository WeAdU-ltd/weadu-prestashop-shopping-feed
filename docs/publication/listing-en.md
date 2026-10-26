# Module presentation (English)

## Name

WeAdU Shopping feed check

## Summary

A free module that checks, in the back office, the catalogue fields Google Shopping uses, and lists product by product what to fix before you send a feed to Merchant Center.

## Presentation

- Reads the active products of the current shop, in the back-office language (200 items by default, 500 at most, combinations included). When the limit cuts the catalogue, the page says how many items were not read.
- Flags, for each product, errors (empty title, description, link, image, availability, or price; title over 150 characters; price of zero; GTIN with an invalid length or check digit; duplicate reference) and warnings (short title, all-capitals title, promotional wording, price without currency, empty brand, empty GTIN and MPN, empty or unknown condition).
- Suggests a title that starts with the brand when the brand is missing from the title.
- Checks each combination, with the catalogue price and the sale price when the customer pays less (including a fixed specific price), and an edit link per product. Prices include tax by default. Choose tax excluded for a United States or Canada feed.
- Exports the result as CSV.
- Interface in English and French.

The module does not modify your products, calls neither Google nor any other service, and sends no data. A product with no finding is not an approval from Google.

Declared compatibility: PrestaShop 1.7.8 and later. AFL-3.0 licence. Made by WeAdU.

## Limits

- The price checked is the catalogue price of each combination (tax included by default, tax excluded when that mode is selected), with the sale price when the customer pays less. It is computed for the default customer group. Tax excluded is for a United States or Canada feed. The module does not calculate US or Canadian sales tax.
- Availability follows the stock quantity of the current shop.
- The GTIN is read from the EAN-13, then the UPC, then the ISBN.
