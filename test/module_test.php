<?php
// Runs weadushoppingfeed.php against small stand-ins for the PrestaShop classes
// it calls (Module, Configuration, Tools, Db, Context, Link, StockAvailable, Image).
// The stand-ins mimic only those calls; this is not a PrestaShop install.

require __DIR__ . '/harness.php';

define('_PS_VERSION_', '8.1.7');
define('_DB_PREFIX_', 'ps_');

function pSQL($value)
{
    return addslashes((string) $value);
}

class Configuration
{
    public static $values = array();
    public static function updateValue($key, $value) { self::$values[$key] = (string) $value; return true; }
    public static function get($key) { return isset(self::$values[$key]) ? self::$values[$key] : false; }
    public static function deleteByName($key) { unset(self::$values[$key]); return true; }
}

class Tools
{
    public static function isSubmit($key) { return isset($_POST[$key]); }
    public static function getValue($key, $default = false) { return isset($_POST[$key]) ? $_POST[$key] : $default; }
}

class Db
{
    public static $instance;
    public $queries = array();
    public $rows = array();
    public $columns = array('isbn' => true, 'mpn' => true, 'condition' => true);
    public static function getInstance() { if (!self::$instance) { self::$instance = new Db(); } return self::$instance; }
    public function executeS($sql)
    {
        $this->queries[] = $sql;
        if (preg_match("/SHOW COLUMNS FROM `ps_product` LIKE '(\\w+)'/", $sql, $m)) {
            return empty($this->columns[$m[1]]) ? array() : array(array('Field' => $m[1]));
        }
        return $this->rows;
    }
}

class StockAvailable
{
    public static $qty = array();
    public static function getQuantityAvailableByProduct($id, $attr, $shop) { return isset(self::$qty[$id]) ? self::$qty[$id] : 0; }
}

class Image
{
    public static $covers = array();
    public static function getCover($id) { return isset(self::$covers[$id]) ? array('id_image' => self::$covers[$id]) : false; }
}

class FakeLink
{
    public function getProductLink($id, $rewrite, $a, $b, $lang, $shop) { return 'https://shop.example/' . $id . '-' . $rewrite . '.html'; }
    public function getImageLink($rewrite, $idImage) { return '//shop.example/' . $idImage . '-large/' . $rewrite . '.jpg'; }
    public function getAdminLink($controller, $token) { return 'index.php?controller=' . $controller . '&token=abc'; }
}

class FakeShop
{
    public $id = 1;
    public function getBaseURL($ssl) { return 'https://shop.example/'; }
}

class Context
{
    public static $ctx;
    public $language;
    public $shop;
    public $currency;
    public $link;
    public static function getContext()
    {
        if (!self::$ctx) {
            self::$ctx = new Context();
            self::$ctx->language = (object) array('id' => 1, 'iso_code' => 'en');
            self::$ctx->shop = new FakeShop();
            self::$ctx->currency = (object) array('iso_code' => 'EUR');
            self::$ctx->link = new FakeLink();
        }
        return self::$ctx;
    }
}

class Module
{
    public $context;
    public $name;
    public $tab;
    public $version;
    public $author;
    public $need_instance;
    public $bootstrap;
    public $displayName;
    public $description;
    public $ps_versions_compliancy;
    public $confirmUninstall;
    public function __construct() { $this->context = Context::getContext(); }
    public function install() { return true; }
    public function uninstall() { return true; }
}

require __DIR__ . '/../weadushoppingfeed/weadushoppingfeed.php';

function fresh()
{
    $_POST = array();
    Db::$instance = null;
    Context::$ctx = null;
    return new Weadushoppingfeed();
}

// --- config.xml and module metadata -----------------------------------------
$xml = simplexml_load_file(__DIR__ . '/../weadushoppingfeed/config.xml');
$module = fresh();
check('config.xml name matches the module', (string) $xml->name === $module->name);
check('config.xml version matches the module', (string) $xml->version === $module->version);
check('module declares PrestaShop 1.7.8 minimum', $module->ps_versions_compliancy['min'] === '1.7.8.0');
check('every module folder has an index.php guard', file_exists(__DIR__ . '/../weadushoppingfeed/index.php'));
$source = file_get_contents(__DIR__ . '/../weadushoppingfeed/weadushoppingfeed.php') . file_get_contents(__DIR__ . '/../weadushoppingfeed/FeedAudit.php');
check('module source makes no outbound HTTP call', !preg_match('/curl_|file_get_contents\(\s*[\'"]https?:|fsockopen|Tools::file_get_contents|GuzzleHttp/', $source));

// --- install and uninstall ---------------------------------------------------
Configuration::$values = array();
check('install succeeds', $module->install() === true);
check('install stores the default limit', Configuration::get('WEADU_SF_LIMIT') === '200');
check('install stores empty UTM values', Configuration::get('WEADU_SF_SOURCE') === '');
check('uninstall succeeds', $module->uninstall() === true);
check('uninstall removes every setting', Configuration::$values === array());

// --- settings form ----------------------------------------------------------
Configuration::$values = array('WEADU_SF_SOURCE' => '"><b>x', 'WEADU_SF_MEDIUM' => '', 'WEADU_SF_CAMPAIGN' => '', 'WEADU_SF_LIMIT' => '200');
$module = fresh();
$html = $module->getContent();
check('page shows the settings form', contains($html, 'name="weadu_source"') && contains($html, 'submitWeaduFeedCheck'));
check('stored values are escaped in the form', contains($html, '&quot;&gt;&lt;b&gt;x') && !contains($html, '"><b>x'));
check('page says it does not call Google', contains($html, 'It does not call Google.'));

$module = fresh();
$_POST = array('submitWeaduFeedSettings' => '1', 'weadu_source' => 'google ads', 'weadu_medium' => 'cpc', 'weadu_campaign' => 'spring', 'weadu_limit' => '50');
$html = $module->getContent();
check('invalid UTM value is refused', contains($html, 'alert-danger') || contains($html, 'alert-error'));
check('invalid UTM value is not stored', Configuration::get('WEADU_SF_SOURCE') === '"><b>x');

$module = fresh();
$_POST = array('submitWeaduFeedSettings' => '1', 'weadu_source' => ' google ', 'weadu_medium' => 'cpc', 'weadu_campaign' => 'spring', 'weadu_limit' => '9999');
$html = $module->getContent();
check('valid settings are saved', contains($html, 'Settings saved.'));
check('UTM source is trimmed', Configuration::get('WEADU_SF_SOURCE') === 'google');
check('limit is capped at 500', Configuration::get('WEADU_SF_LIMIT') === '500');

$module = fresh();
$_POST = array('submitWeaduFeedSettings' => '1', 'weadu_source' => '', 'weadu_medium' => '', 'weadu_campaign' => '', 'weadu_limit' => '0');
$module->getContent();
check('limit 0 falls back to 200', Configuration::get('WEADU_SF_LIMIT') === '200');
check('empty UTM values can be saved', Configuration::get('WEADU_SF_SOURCE') === '');

// --- run the check on two products -------------------------------------------
Configuration::$values = array('WEADU_SF_SOURCE' => 'google', 'WEADU_SF_MEDIUM' => 'cpc', 'WEADU_SF_CAMPAIGN' => 'spring', 'WEADU_SF_LIMIT' => '50');
$module = fresh();
Db::getInstance()->rows = array(
    array('id_product' => 1, 'reference' => 'SHIRT-1', 'price' => '29', 'ean13' => '4006381333931', 'upc' => '', 'isbn' => '', 'mpn' => '', 'condition' => 'new',
        'name' => 'Acme Cotton Shirt', 'description' => '<p>A cotton shirt for everyday wear.</p>', 'description_short' => '', 'link_rewrite' => 'acme-cotton-shirt', 'brand' => 'Acme'),
    array('id_product' => 2, 'reference' => '', 'price' => '0', 'ean13' => '4006381333932', 'upc' => '', 'isbn' => '', 'mpn' => '', 'condition' => 'new',
        'name' => '<script>x</script>', 'description' => '', 'description_short' => '', 'link_rewrite' => 'hat', 'brand' => ''),
);
StockAvailable::$qty = array(1 => 3, 2 => 0);
Image::$covers = array(1 => 10);
$_POST = array('submitWeaduFeedCheck' => '1');
$html = $module->getContent();
$sql = Db::getInstance()->queries[count(Db::getInstance()->queries) - 1];
check('query is limited to the saved limit', contains($sql, 'LIMIT 50'));
check('query reads the current shop and language', contains($sql, 'ps.id_shop = 1') && contains($sql, 'pl.id_lang = 1'));
check('query reads active products only', contains($sql, 'ps.active = 1'));
check('summary counts products, errors, and warnings', contains($html, '2 products, 4 errors, 1 warnings.'), strip_tags(substr($html, strpos($html, 'Result'), 120)));
check('clean product shows no finding', contains($html, '<td>SHIRT-1</td><td>Acme Cotton Shirt</td><td>No finding</td>'));
check('id falls back to the product id', contains($html, '<td>2</td>'));
check('product name is escaped', contains($html, '&lt;script&gt;x&lt;/script&gt;') && !contains($html, '<script>x</script>'));
check('findings are listed with severity', contains($html, 'error: price must be greater than zero.') && contains($html, 'error: gtin check digit or length is invalid.') && contains($html, 'error: image_link is empty.') && contains($html, 'error: description is empty.'));
check('missing brand is a warning', contains($html, 'warning: brand is empty.'));
check('CSV is offered for download', contains($html, 'href="data:text/csv;charset=utf-8,') && contains($html, 'Download CSV'));
check('CSV textarea lists codes', contains($html, 'gtin_invalid') && contains($html, 'price_not_positive'));

// mapRow details
$ref = new ReflectionClass('Weadushoppingfeed');
$map = $ref->getMethod('mapRow');
$map->setAccessible(true);
$row = $map->invoke($module, Db::getInstance()->rows[0], 'EUR', 1, 1, 'google', 'cpc', 'spring');
check('link carries the saved UTM values', $row['link'] === 'https://shop.example/1-acme-cotton-shirt.html?utm_source=google&utm_medium=cpc&utm_campaign=spring', $row['link']);
check('image link is made absolute', $row['image_link'] === 'https://shop.example/10-large/acme-cotton-shirt.jpg', $row['image_link']);
check('price is formatted with the currency', $row['price'] === '29.00 EUR');
check('stock above zero is in_stock', $row['availability'] === 'in_stock');
check('EAN13 is used as GTIN', $row['gtin'] === '4006381333931');
$row2 = $map->invoke($module, Db::getInstance()->rows[1], 'EUR', 1, 1, '', '', '');
check('no stock is out_of_stock', $row2['availability'] === 'out_of_stock');
check('link has no UTM when settings are empty', $row2['link'] === 'https://shop.example/2-hat.html');

// --- older schema without isbn, mpn, condition columns -----------------------
$module = fresh();
Db::getInstance()->columns = array();
Db::getInstance()->rows = array();
$_POST = array('submitWeaduFeedCheck' => '1');
$module->getContent();
$sql = Db::getInstance()->queries[count(Db::getInstance()->queries) - 1];
check('missing columns are replaced by empty values', contains($sql, "'' AS isbn") && contains($sql, "'' AS mpn"));
todo("fallback alias 'new' AS condition is a MySQL reserved word and needs backticks", contains($sql, "'new' AS condition"));

// --- French back office ------------------------------------------------------
$module = fresh();
Context::getContext()->language->iso_code = 'fr';
Db::getInstance()->rows = array(
    array('id_product' => 3, 'reference' => 'R3', 'price' => '19', 'ean13' => '', 'upc' => '', 'isbn' => '', 'mpn' => '', 'condition' => 'new',
        'name' => 'Chemise en coton Acme', 'description' => 'Une chemise en coton.', 'description_short' => '', 'link_rewrite' => 'chemise', 'brand' => 'Acme'),
);
Image::$covers = array(3 => 11);
StockAvailable::$qty = array(3 => 1);
$_POST = array('submitWeaduFeedCheck' => '1');
$html = $module->getContent();
check('French labels are used in a French back office', contains($html, 'Lancer le contrôle') && contains($html, 'avertissements'));
check('French finding label', contains($html, 'warning: GTIN et MPN vides.'));

finish();
