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
    public $combos = array();
    public $comboQueries = array();
    public $columns = array('isbn' => true, 'mpn' => true, 'condition' => true);
    public static function getInstance() { if (!self::$instance) { self::$instance = new Db(); } return self::$instance; }
    public function executeS($sql)
    {
        $this->queries[] = $sql;
        if (preg_match("/SHOW COLUMNS FROM `ps_(\\w+)` LIKE '(\\w+)'/", $sql, $m)) {
            return empty($this->columns[$m[2]]) ? array() : array(array('Field' => $m[2]));
        }
        if (preg_match('/FROM `ps_product_attribute` pa.*pa.id_product = (\\d+)/s', $sql, $m)) {
            $this->comboQueries[] = $sql;
            return isset($this->combos[(int) $m[1]]) ? $this->combos[(int) $m[1]] : array();
        }
        return $this->rows;
    }
}

class StockAvailable
{
    public static $qty = array();
    public static function getQuantityAvailableByProduct($id, $attr, $shop) { $k = $attr ? $id . '-' . $attr : $id; return isset(self::$qty[$k]) ? self::$qty[$k] : 0; }
}

class Image
{
    public static $covers = array();
    public static function getCover($id) { return isset(self::$covers[$id]) ? array('id_image' => self::$covers[$id]) : false; }
}

class FakeLink
{
    public function getProductLink($id, $rewrite, $a, $b, $lang, $shop, $ipa = 0) { return 'https://shop.example/' . $id . ($ipa ? '-' . $ipa : '') . '-' . $rewrite . '.html'; }
    public function getImageLink($rewrite, $idImage) { return '//shop.example/' . $idImage . '-large/' . $rewrite . '.jpg'; }
    public $adminCalls = array();
    public function getAdminLink($controller, $token = true, $sf = array(), $params = array()) { $this->adminCalls[] = array($controller, $sf, $params); return 'index.php?controller=' . $controller . ($params ? '&' . http_build_query($params) : '') . '&token=abc'; }
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
    // Legacy module translations, as PrestaShop reads them (translations/<iso>.php, escaped output).
    public function l($string, $specific = false)
    {
        $iso = $this->context->language->iso_code;
        $file = __DIR__ . '/../weadushoppingfeed/translations/' . $iso . '.php';
        $ret = $string;
        if (file_exists($file)) {
            $_MODULE = array();
            include $file;
            $key = '<{' . $this->name . '}prestashop>' . ($specific ? $specific : $this->name) . '_' . md5(str_replace("'", "\\'", $string));
            if (isset($_MODULE[$key])) {
                $ret = stripslashes($_MODULE[$key]);
            }
        }
        return htmlspecialchars($ret, ENT_COMPAT, 'UTF-8');
    }
}

class Product
{
    public static $prices = array();
    public static $calls = array();
    public static function getPriceStatic($id, $tax = true, $pa = null, $decimals = 6, $divisor = null, $onlyReduc = false, $usereduc = true)
    {
        self::$calls[] = array($id, $tax, $pa, $usereduc);
        $p = isset(self::$prices[$id . '-' . (int) $pa]) ? self::$prices[$id . '-' . (int) $pa] : array(0, 0);
        return $usereduc ? $p[1] : $p[0];
    }
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
check('install stores no UTM setting', Configuration::get('WEADU_SF_SOURCE') === false);
Configuration::$values['WEADU_SF_SOURCE'] = 'old';
check('uninstall succeeds', $module->uninstall() === true);
check('uninstall removes every setting, including 1.0.0 UTM ones', Configuration::$values === array());

// --- settings form ----------------------------------------------------------
Configuration::$values = array('WEADU_SF_LIMIT' => '200');
$module = fresh();
$html = $module->getContent();
check('page shows the settings form', contains($html, 'name="weadu_limit"') && contains($html, 'submitWeaduFeedCheck'));
check('page has no UTM field', !contains($html, 'weadu_source') && !contains($html, 'utm_source'));
check('page warns about UTM in feed links and points to the tracking template', contains($html, 'Google advises against UTM parameters in feed links') && contains($html, 'tracking template'));
check('page says it does not call Google', contains($html, 'It does not call Google.'));
$calls = Context::getContext()->link->adminCalls;
check('form action uses getAdminLink parameters', $calls[0][0] === 'AdminModules' && $calls[0][2]['configure'] === 'weadushoppingfeed' && $calls[0][2]['module_name'] === 'weadushoppingfeed');

$module = fresh();
$_POST = array('submitWeaduFeedSettings' => '1', 'weadu_limit' => '9999');
$html = $module->getContent();
check('valid settings are saved', contains($html, 'Settings saved.'));
check('limit is capped at 500', Configuration::get('WEADU_SF_LIMIT') === '500');
$module = fresh();
$_POST = array('submitWeaduFeedSettings' => '1', 'weadu_limit' => '0');
$module->getContent();
check('limit 0 falls back to 200', Configuration::get('WEADU_SF_LIMIT') === '200');

// --- run the check: a simple product, a product with two combinations, a broken one
Configuration::$values = array('WEADU_SF_LIMIT' => '50');
$module = fresh();
Db::getInstance()->rows = array(
    array('id_product' => 1, 'reference' => 'SHIRT-1', 'ean13' => '4006381333931', 'upc' => '', 'isbn' => '', 'mpn' => '', 'condition' => 'new',
        'name' => 'Acme Cotton Shirt', 'description' => '<p>A cotton shirt for everyday wear.</p>', 'description_short' => '', 'link_rewrite' => 'acme-cotton-shirt', 'brand' => 'Acme'),
    array('id_product' => 2, 'reference' => '', 'ean13' => '4006381333932', 'upc' => '', 'isbn' => '', 'mpn' => '', 'condition' => 'new',
        'name' => '<script>x</script>', 'description' => '', 'description_short' => '', 'link_rewrite' => 'hat', 'brand' => ''),
    array('id_product' => 4, 'reference' => 'TEE', 'ean13' => '', 'upc' => '', 'isbn' => '', 'mpn' => 'T-1', 'condition' => 'new',
        'name' => 'Acme Tee', 'description' => 'A soft cotton tee for every day.', 'description_short' => '', 'link_rewrite' => 'tee', 'brand' => 'Acme'),
);
Db::getInstance()->combos = array(4 => array(
    array('id_product_attribute' => 7, 'reference' => 'TEE-S-BLUE', 'ean13' => '4006381333948', 'upc' => '', 'isbn' => '', 'mpn' => '', 'attributes' => 'S, Blue'),
    array('id_product_attribute' => 8, 'reference' => '', 'ean13' => '', 'upc' => '', 'isbn' => '', 'mpn' => 'T-1-M', 'attributes' => 'M, Blue'),
));
Product::$prices = array('1-0' => array(34.8, 34.8), '2-0' => array(0, 0), '4-7' => array(24, 19.2), '4-8' => array(26.4, 26.4));
StockAvailable::$qty = array(1 => 3, 2 => 0, '4-7' => 2, '4-8' => 0);
Image::$covers = array(1 => 10, 4 => 12);
$_POST = array('submitWeaduFeedCheck' => '1');
$html = $module->getContent();
$queries = Db::getInstance()->queries;
$sql = '';
foreach ($queries as $q) { if (contains($q, 'FROM `ps_product` p')) { $sql = $q; } }
check('query is limited to the saved limit', contains($sql, 'LIMIT 50'));
check('query reads the current shop and language', contains($sql, 'ps.id_shop = 1') && contains($sql, 'pl.id_lang = 1'));
check('query reads active products only', contains($sql, 'ps.active = 1'));
check('combinations are read per product in the current shop', count(Db::getInstance()->comboQueries) === 3 && contains(Db::getInstance()->comboQueries[2], 'pas.id_shop = 1'));
check('summary counts items (one per combination)', contains($html, '4 items,'), strip_tags(substr($html, strpos($html, 'Result'), 120)));
check('clean product shows no finding', contains($html, '<td>SHIRT-1</td><td>Acme Cotton Shirt</td><td>34.80 EUR</td><td>No finding</td>'));
check('id falls back to the product id', contains($html, '<td>2</td>'));
check('product name is escaped', contains($html, '&lt;script&gt;x&lt;/script&gt;') && !contains($html, '<script>x</script>'));
check('findings are listed with severity', contains($html, 'error: price must be greater than zero.') && contains($html, 'error: image_link is empty.') && contains($html, 'error: description is empty.'));
check('missing brand is a warning', contains($html, 'warning: brand is empty.'));
check('combination title carries its attributes', contains($html, '<td>TEE-S-BLUE</td><td>Acme Tee - S, Blue</td>'));
check('combination without reference gets product reference and combination id', contains($html, '<td>TEE-8</td><td>Acme Tee - M, Blue</td>'));
check('specific price shows as sale price', contains($html, '24.00 EUR / sale 19.20 EUR'));
check('prices are asked with tax, with and without specific prices', in_array(array(4, true, 7, false), Product::$calls, true) && in_array(array(4, true, 7, true), Product::$calls, true));
check('each row has an edit link', substr_count($html, '>Edit</a>') === 4 && contains($html, 'controller=AdminProducts&amp;id_product=4&amp;updateproduct=1'));
check('CSV is offered for download', contains($html, 'href="data:text/csv;charset=utf-8,') && contains($html, 'Download CSV'));
check('CSV textarea lists codes', contains($html, 'price_not_positive'));

// mapRow details
$ref = new ReflectionClass('Weadushoppingfeed');
$map = $ref->getMethod('mapRow');
$map->setAccessible(true);
$rows = Db::getInstance()->rows;
$row = $map->invoke($module, $rows[0], null, 'EUR', 1, 1);
check('link has no UTM', $row['link'] === 'https://shop.example/1-acme-cotton-shirt.html', $row['link']);
check('image link is made absolute', $row['image_link'] === 'https://shop.example/10-large/acme-cotton-shirt.jpg', $row['image_link']);
check('price is tax included, with the currency', $row['price'] === '34.80 EUR' && $row['sale_price'] === '');
check('stock above zero is in_stock', $row['availability'] === 'in_stock');
check('EAN13 is used as GTIN', $row['gtin'] === '4006381333931');
$row2 = $map->invoke($module, $rows[1], null, 'EUR', 1, 1);
check('no stock is out_of_stock', $row2['availability'] === 'out_of_stock');
$c7 = $map->invoke($module, $rows[2], Db::getInstance()->combos[4][0], 'EUR', 1, 1);
check('combination link targets the combination', $c7['link'] === 'https://shop.example/4-7-tee.html', $c7['link']);
check('combination GTIN, stock, group id', $c7['gtin'] === '4006381333948' && $c7['availability'] === 'in_stock' && $c7['item_group_id'] === '4');
check('combination sale price', $c7['price'] === '24.00 EUR' && $c7['sale_price'] === '19.20 EUR');
$c8 = $map->invoke($module, $rows[2], Db::getInstance()->combos[4][1], 'EUR', 1, 1);
check('combination MPN wins, product GTIN is the fallback (none here)', $c8['mpn'] === 'T-1-M' && $c8['gtin'] === '' && $c8['availability'] === 'out_of_stock');
$cx = $map->invoke($module, $rows[2], array('id_product_attribute' => 9, 'reference' => 'TEE', 'ean13' => '', 'upc' => '', 'isbn' => '', 'mpn' => '', 'attributes' => 'L'), 'EUR', 1, 1);
check('combination reference equal to the product reference is made unique', $cx['id'] === 'TEE-9', $cx['id']);

// limit counts items, not products
Configuration::$values = array('WEADU_SF_LIMIT' => '2');
$m2 = fresh();
Db::getInstance()->rows = array($rows[2]);
Db::getInstance()->combos = array(4 => array(
    array('id_product_attribute' => 7, 'reference' => 'A', 'ean13' => '', 'upc' => '', 'isbn' => '', 'mpn' => '', 'attributes' => 'S'),
    array('id_product_attribute' => 8, 'reference' => 'B', 'ean13' => '', 'upc' => '', 'isbn' => '', 'mpn' => '', 'attributes' => 'M'),
    array('id_product_attribute' => 9, 'reference' => 'C', 'ean13' => '', 'upc' => '', 'isbn' => '', 'mpn' => '', 'attributes' => 'L'),
));
$_POST = array('submitWeaduFeedCheck' => '1');
check('limit caps the number of items', contains($m2->getContent(), '2 items,'));

// --- older schema without isbn, mpn, condition columns -----------------------
Configuration::$values = array('WEADU_SF_LIMIT' => '50');
$module = fresh();
Db::getInstance()->columns = array();
Db::getInstance()->rows = array();
$_POST = array('submitWeaduFeedCheck' => '1');
$module->getContent();
$sql = Db::getInstance()->queries[count(Db::getInstance()->queries) - 1];
check('missing columns are replaced by empty values', contains($sql, "'' AS isbn") && contains($sql, "'' AS mpn"));
check('fallback condition alias is backticked (MySQL reserved word)', contains($sql, "'new' AS `condition`"));

// --- French back office (native translations/fr.php) -------------------------
$module = fresh();
Context::getContext()->language->iso_code = 'fr';
$module = new Weadushoppingfeed();
Db::getInstance()->rows = array(
    array('id_product' => 3, 'reference' => 'R3', 'ean13' => '', 'upc' => '', 'isbn' => '', 'mpn' => '', 'condition' => 'new',
        'name' => 'Chemise en coton Acme', 'description' => 'Une chemise en coton.', 'description_short' => '', 'link_rewrite' => 'chemise', 'brand' => 'Acme'),
);
Product::$prices = array('3-0' => array(19, 19));
Image::$covers = array(3 => 11);
StockAvailable::$qty = array(3 => 1);
$_POST = array('submitWeaduFeedCheck' => '1');
$html = $module->getContent();
check('French labels come from translations/fr.php', contains($html, 'Lancer le contrôle') && contains($html, 'avertissements') && contains($html, 'Prix TTC'));
check('French apostrophes are not double-escaped', contains($html, 'Il n&#039;appelle pas Google') && !contains($html, '&amp;#039;'));
check('French module name', $module->displayName === 'WeAdU Contrôle de flux Shopping');
check('French finding label', contains($html, 'warning: GTIN et MPN vides.'));

$en = array();
preg_match_all("/\\\$this->(?:t|l)\\('([^']+)'/", file_get_contents(__DIR__ . '/../weadushoppingfeed/weadushoppingfeed.php'), $mm);
$_MODULE = array();
include __DIR__ . '/../weadushoppingfeed/translations/fr.php';
$missing = array();
foreach (array_unique($mm[1]) as $str) {
    if (!isset($_MODULE['<{weadushoppingfeed}prestashop>weadushoppingfeed_' . md5($str)])) { $missing[] = $str; }
}
check('every English string has a French translation', !$missing, implode(' | ', $missing));

finish();
