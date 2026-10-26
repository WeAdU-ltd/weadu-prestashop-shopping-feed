# WeAdU Shopping feed check (PrestaShop module)

A free PrestaShop module that checks the catalogue fields Google Shopping uses (title, description, link, image, availability, price, brand, GTIN, condition) and lists, product by product, what to fix before you send a feed to Merchant Center.

The check runs in your back office. The module does not call Google or any other service.

## Features

- Reads the active products of the current shop, in the back-office language, up to a limit you choose (200 items by default, 500 at most, combinations included).
- If the catalogue has more items than the limit, the result says how many were not read. The table and the CSV then cover only the rows that were read.
- Lists each product with its findings (errors and warnings) and a suggested title when the brand is missing from the title.
- Checks each combination as its own item (title with its attributes, its own reference, GTIN, stock, and link), grouped by product.
- Prices include tax by default, as Google expects in most countries. The regular price is the catalogue price. The sale price is shown when the customer pays less. A specific price that sets a fixed amount no longer replaces the catalogue price in both columns. Choose **Tax excluded** for a United States or Canada feed: Merchant Center requires tax-excluded prices there, and this check does not calculate US or Canadian sales tax.
- Specific prices are computed in the back-office context, that is for the default customer group (and the default country and currency): a price reserved to another customer group is not shown. For a fixed specific price, the catalogue price is the shop price plus the combination impact (and ecotax), with the product tax rate. It does not apply a customer-group reduction.
- Each row has an **Edit** link to the product page of the back office.
- No UTM parameters: Google advises against them in feed links. To track Google Ads clicks, keep auto-tagging on and use a tracking template in Google Ads.
- Exports the result as a CSV file (download button, and a text area you can copy).
- Interface in English and French, with native PrestaShop translation files (`translations/fr.php`), editable in **International → Translations**.

### How product fields are read

| Feed field | Source in PrestaShop |
| --- | --- |
| `id` | Combination reference, or product reference (plus `-<combination id>` for a combination), or the product id |
| `item_group_id` | Product id, for combinations |
| `title` | Product name, followed by the combination attributes (for example `Acme Tee - S, Blue`) |
| `description` | Description, or the short description when the description is empty |
| `link` | Product URL, or the combination URL |
| `image_link` | First image of the combination, or the product cover image |
| `availability` | `in_stock` when the current shop quantity (of the combination) is above zero, otherwise `out_of_stock` |
| `price` | Catalogue price (tax included by default, or tax excluded when that mode is selected). When the customer pays the same or more, this is the price PrestaShop returns. |
| `sale_price` | Price the customer pays, when it is lower than the catalogue price, including a fixed specific price (shown in the table) |
| `brand` | Brand (manufacturer) |
| `gtin` | EAN-13, then UPC, then ISBN (of the combination first, then of the product) |
| `mpn` | MPN of the combination, or of the product, when the column exists |
| `condition` | Product condition |

### What is checked

Errors: empty id, title, description, link, image, availability, or price; title over 150 characters; description over 5,000 characters; link or image not starting with `http://` or `https://`; price not greater than zero; GTIN with an invalid length or check digit; the same id used twice.

Warnings: title under 15 characters, all in capitals, or with promotional wording (for example "free shipping", "soldes", "promo"); price without a currency code; empty brand; empty GTIN and MPN; empty or unknown condition.

A product with no finding is not an approval from Google: Merchant Center applies many more rules.

## En français

Module PrestaShop gratuit qui contrôle les champs du catalogue utilisés par Google Shopping, déclinaison par déclinaison, dans votre back-office, sans appel à Google. Les prix sont TTC par défaut : le prix catalogue, et le prix soldé quand le client paie moins, y compris si un prix spécifique fixe remplace le prix catalogue. Pour un flux États-Unis ou Canada, choisir le mode hors taxe. Si la limite est atteinte, la page indique combien d'articles n'ont pas été lus. Les prix spécifiques sont calculés dans le contexte du back-office, c'est-à-dire pour le groupe client par défaut (et le pays et la devise par défaut) : un prix réservé à un autre groupe client n'apparaît pas. L'image vérifiée est la première image de la déclinaison, ou à défaut l'image de couverture du produit. Chaque ligne a un lien « Modifier ». Le module n'ajoute pas de paramètres UTM : pour suivre les clics Google Ads, utilisez le marquage automatique et un modèle de suivi dans Google Ads.

## Installation

1. Download the module ZIP from the latest release of this repository, or build it yourself: `zip -r weadushoppingfeed.zip weadushoppingfeed`. The ZIP must contain the `weadushoppingfeed` folder at its root.
2. In the back office, open **Modules → Module Manager → Upload a module** and select the ZIP.
3. Click **Configure** on "WeAdU Shopping feed check".

Compatibility declared by the module: PrestaShop 1.7.8 or later.

## Usage

1. Optional: enter a limit (number of items, combinations included) and choose tax included or tax excluded, then click **Save settings**.
2. Click **Run check**.
3. Read the table, including the line that says how many items were not read, or download the CSV. The CSV does not repeat that line.

## Settings

| Setting | Default | Notes |
| --- | --- | --- |
| Item limit | 200 | Between 1 and 500. Combinations count as items. Above the limit, the page states how many items were not read. |
| Price tax | Tax included | Tax excluded is for a United States or Canada feed. |

Uninstalling the module deletes this setting (and the UTM settings of version 1.0.0, if any).

## Privacy

The module reads your catalogue in your own back office and displays the result there. It sends no data anywhere, calls no external service, and has no telemetry. It does not modify your products.

## Development

```sh
sh test.sh   # PHP syntax check, shared logic tests, and module tests (PHP and Node.js 20 or later)
```

`test/module_test.php` runs the module against small stand-ins for the PrestaShop classes it uses. It does not replace a test on a real shop.

## License

[AFL-3.0](LICENSE)

## About

Made by [WeAdU](https://weadu.com). WeAdU is not affiliated with PrestaShop or Google.
