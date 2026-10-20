# WeAdU Shopping feed check (PrestaShop module)

A free PrestaShop module that checks the catalogue fields Google Shopping uses (title, description, link, image, availability, price, brand, GTIN, condition) and lists, product by product, what to fix before you send a feed to Merchant Center.

The check runs in your back office. The module does not call Google or any other service.

## Features

- Reads the active products of the current shop, in the back-office language, up to a limit you choose (200 by default, 500 at most).
- Lists each product with its findings (errors and warnings) and a suggested title when the brand is missing from the title.
- Optional UTM parameters: when `utm_source`, `utm_medium`, and `utm_campaign` are all set, they are added to the product links that are checked.
- Exports the result as a CSV file (download button, and a text area you can copy).
- Interface in English and French (follows the back-office language).

### How product fields are read

| Feed field | Source in PrestaShop |
| --- | --- |
| `id` | Product reference, or the product id when the reference is empty |
| `title` | Product name |
| `description` | Description, or the short description when the description is empty |
| `link` | Product URL (with UTM parameters if they are set) |
| `image_link` | Cover image |
| `availability` | `in_stock` when the current shop quantity is above zero, otherwise `out_of_stock` |
| `price` | Base price, tax excluded, without combinations or specific prices, with the currency of the context |
| `brand` | Brand (manufacturer) |
| `gtin` | EAN-13, then UPC, then ISBN |
| `mpn` | MPN, when the column exists |
| `condition` | Product condition |

### What is checked

Errors: empty id, title, description, link, image, availability, or price; title over 150 characters; description over 5,000 characters; link or image not starting with `http://` or `https://`; price not greater than zero; GTIN with an invalid length or check digit; the same id used twice.

Warnings: title under 15 characters, all in capitals, or with promotional wording (for example "free shipping", "soldes", "promo"); price without a currency code; empty brand; empty GTIN and MPN; empty or unknown condition.

A product with no finding is not an approval from Google: Merchant Center applies many more rules.

## Installation

1. Download the module ZIP from the latest release of this repository, or build it yourself: `zip -r weadushoppingfeed.zip weadushoppingfeed`. The ZIP must contain the `weadushoppingfeed` folder at its root.
2. In the back office, open **Modules → Module Manager → Upload a module** and select the ZIP.
3. Click **Configure** on "WeAdU Shopping feed check".

Compatibility declared by the module: PrestaShop 1.7.8 or later.

## Usage

1. Optional: enter `utm_source`, `utm_medium`, `utm_campaign`, and a product limit, then click **Save settings**. UTM values may contain letters, digits, and `. _ ~ -`, with no spaces.
2. Click **Run check**.
3. Read the table, or download the CSV.

## Settings

| Setting | Default | Notes |
| --- | --- | --- |
| `utm_source`, `utm_medium`, `utm_campaign` | empty | Added to the checked links only when all three are set. |
| Product limit | 200 | Between 1 and 500. |

Uninstalling the module deletes these settings.

## Privacy

The module reads your catalogue in your own back office and displays the result there. It sends no data anywhere, calls no external service, and has no telemetry. It does not modify your products.

## Development

```sh
sh test.sh   # PHP syntax check, shared logic tests, and module tests (PHP and Node.js 20 or later)
```

`test/module_test.php` runs the module against small stand-ins for the PrestaShop classes it uses. It does not replace a test on a real shop.

## License

[MIT](LICENSE)

## About

Made by [WeAdU](https://weadu.com). WeAdU is not affiliated with PrestaShop or Google.
