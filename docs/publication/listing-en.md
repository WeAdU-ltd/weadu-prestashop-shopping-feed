# Module presentation (English)

## Name

WeAdU Shopping feed check

## Summary

A free module that checks, in the back office, the catalogue fields Google Shopping uses, and lists product by product what to fix before you send a feed to Merchant Center.

## Presentation

- Reads the active products of the current shop, in the back-office language (200 products by default, 500 at most).
- Flags, for each product, errors (empty title, description, link, image, availability, or price; title over 150 characters; price of zero; GTIN with an invalid length or check digit; duplicate reference) and warnings (short title, all-capitals title, promotional wording, price without currency, empty brand, empty GTIN and MPN, empty or unknown condition).
- Suggests a title that starts with the brand when the brand is missing from the title.
- Optionally adds your UTM parameters to the links it checks.
- Exports the result as CSV.
- Interface in English and French.

The module does not modify your products, calls neither Google nor any other service, and sends no data. A product with no finding is not an approval from Google.

Declared compatibility: PrestaShop 1.7.8 and later. AFL-3.0 licence. Made by WeAdU.

## Limits

- The price checked is the base price, tax excluded, without combinations or specific prices.
- Availability follows the stock quantity of the current shop.
- The GTIN is read from the EAN-13, then the UPC, then the ISBN.
