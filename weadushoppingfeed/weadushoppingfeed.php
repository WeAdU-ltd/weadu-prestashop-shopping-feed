<?php
/**
 * WeAdU Shopping feed check
 *
 * @author    WeAdU <https://weadu.com>
 * @copyright 2026 WeAdU
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/FeedAudit.php';

class Weadushoppingfeed extends Module
{
    public function __construct()
    {
        $this->name = 'weadushoppingfeed';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'WeAdU';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('WeAdU Shopping feed check');
        $this->description = $this->l('Checks catalog fields used by Google Shopping: title, GTIN, price, image, brand. Local only.');
        $this->ps_versions_compliancy = array('min' => '1.7.8.0', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue('WEADU_SF_LIMIT', '200');
    }

    public function uninstall()
    {
        Configuration::deleteByName('WEADU_SF_LIMIT');
        // Settings from 1.0.0, removed with the UTM option.
        Configuration::deleteByName('WEADU_SF_SOURCE');
        Configuration::deleteByName('WEADU_SF_MEDIUM');
        Configuration::deleteByName('WEADU_SF_CAMPAIGN');
        return parent::uninstall();
    }

    public function getContent()
    {
        $html = '';
        if (Tools::isSubmit('submitWeaduFeedSettings')) {
            $html .= $this->saveSettings();
        }
        $report = null;
        if (Tools::isSubmit('submitWeaduFeedCheck')) {
            $report = $this->runCheck();
        }
        return $html . $this->renderPage($report);
    }

    private function saveSettings()
    {
        $limit = (int) Tools::getValue('weadu_limit');
        if ($limit < 1) {
            $limit = 200;
        }
        if ($limit > 500) {
            $limit = 500;
        }
        Configuration::updateValue('WEADU_SF_LIMIT', (string) $limit);
        return $this->alert('success', $this->t('Settings saved.'));
    }

    private function runCheck()
    {
        $limit = (int) Configuration::get('WEADU_SF_LIMIT');
        if ($limit < 1 || $limit > 500) {
            $limit = 200;
        }
        $items = $this->collectItems($limit);
        $report = WeaduFeedAudit::auditItems($items, $limit);
        $report['extra'] = array();
        foreach ($items as $i => $item) {
            $report['extra'][$i] = array(
                'price' => $item['price'],
                'sale_price' => $item['sale_price'],
                'edit_link' => $item['edit_link'],
            );
        }
        return $report;
    }

    private function collectItems($limit)
    {
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $prefix = _DB_PREFIX_;
        $isbn = $this->columnExists('product', 'isbn') ? 'p.isbn' : "'' AS isbn";
        $mpn = $this->columnExists('product', 'mpn') ? 'p.mpn' : "'' AS mpn";
        $condition = $this->columnExists('product', 'condition') ? 'p.`condition`' : "'new' AS `condition`";
        $sql = 'SELECT p.id_product, p.reference, p.ean13, p.upc, ' . $isbn . ', ' . $mpn . ', ' . $condition . ',
                pl.name, pl.description, pl.description_short, pl.link_rewrite, m.name AS brand
            FROM `' . $prefix . 'product` p
            INNER JOIN `' . $prefix . 'product_shop` ps
                ON ps.id_product = p.id_product AND ps.id_shop = ' . $idShop . '
            INNER JOIN `' . $prefix . 'product_lang` pl
                ON pl.id_product = p.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop . '
            LEFT JOIN `' . $prefix . 'manufacturer` m
                ON m.id_manufacturer = p.id_manufacturer
            WHERE ps.active = 1
            ORDER BY p.id_product ASC
            LIMIT ' . (int) $limit;
        $rows = Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            return array();
        }
        $currency = '';
        if (isset($this->context->currency->iso_code)) {
            $currency = (string) $this->context->currency->iso_code;
        }
        $items = array();
        foreach ($rows as $row) {
            $combinations = $this->combinations((int) $row['id_product'], $idLang, $idShop);
            if (!$combinations) {
                $combinations = array(null);
            }
            foreach ($combinations as $combination) {
                if (count($items) >= $limit) {
                    return $items;
                }
                $items[] = $this->mapRow($row, $combination, $currency, $idLang, $idShop);
            }
        }
        return $items;
    }

    // One row per combination of the product in this shop, with its attribute names.
    private function combinations($idProduct, $idLang, $idShop)
    {
        $prefix = _DB_PREFIX_;
        $isbn = $this->columnExists('product_attribute', 'isbn') ? 'pa.isbn' : "'' AS isbn";
        $mpn = $this->columnExists('product_attribute', 'mpn') ? 'pa.mpn' : "'' AS mpn";
        $sql = 'SELECT pa.id_product_attribute, pa.reference, pa.ean13, pa.upc, ' . $isbn . ', ' . $mpn . ',
                GROUP_CONCAT(al.name ORDER BY ag.position, a.position SEPARATOR \', \') AS attributes
            FROM `' . $prefix . 'product_attribute` pa
            INNER JOIN `' . $prefix . 'product_attribute_shop` pas
                ON pas.id_product_attribute = pa.id_product_attribute AND pas.id_shop = ' . (int) $idShop . '
            LEFT JOIN `' . $prefix . 'product_attribute_combination` pac
                ON pac.id_product_attribute = pa.id_product_attribute
            LEFT JOIN `' . $prefix . 'attribute` a
                ON a.id_attribute = pac.id_attribute
            LEFT JOIN `' . $prefix . 'attribute_group` ag
                ON ag.id_attribute_group = a.id_attribute_group
            LEFT JOIN `' . $prefix . 'attribute_lang` al
                ON al.id_attribute = pac.id_attribute AND al.id_lang = ' . (int) $idLang . '
            WHERE pa.id_product = ' . (int) $idProduct . '
            GROUP BY pa.id_product_attribute
            ORDER BY pa.id_product_attribute ASC';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    private function mapRow($row, $combination, $currency, $idLang, $idShop)
    {
        $idProduct = (int) $row['id_product'];
        $idPa = $combination ? (int) $combination['id_product_attribute'] : 0;
        $productRef = trim((string) $row['reference']);
        $id = $combination ? trim((string) $combination['reference']) : '';
        // A combination often copies the product reference: keep ids unique per combination.
        if ($id === '' || ($combination && $id === $productRef)) {
            $id = $productRef;
            if ($id !== '' && $combination) {
                $id .= '-' . $idPa;
            }
        }
        if ($id === '') {
            $id = $combination ? $idProduct . '-' . $idPa : (string) $idProduct;
        }
        $title = (string) $row['name'];
        if ($combination && trim((string) $combination['attributes']) !== '') {
            $title .= ' - ' . $combination['attributes'];
        }
        $description = (string) $row['description'];
        if (trim(strip_tags($description)) === '') {
            $description = (string) $row['description_short'];
        }
        $gtin = '';
        $sources = $combination ? array($combination, $row) : array($row);
        foreach ($sources as $source) {
            foreach (array('ean13', 'upc', 'isbn') as $key) {
                $candidate = isset($source[$key]) ? preg_replace('/\s+/', '', (string) $source[$key]) : '';
                if ($candidate !== '') {
                    $gtin = $candidate;
                    break 2;
                }
            }
        }
        $mpn = $combination && trim((string) $combination['mpn']) !== '' ? (string) $combination['mpn'] : (isset($row['mpn']) ? (string) $row['mpn'] : '');
        // Final price with tax, with the combination impact; the regular price ignores specific prices.
        $regular = (float) Product::getPriceStatic($idProduct, true, $idPa ? $idPa : null, 2, null, false, false);
        $final = (float) Product::getPriceStatic($idProduct, true, $idPa ? $idPa : null, 2, null, false, true);
        $suffix = $currency !== '' ? ' ' . $currency : '';
        $price = number_format($regular, 2, '.', '') . $suffix;
        $salePrice = ($final < $regular) ? number_format($final, 2, '.', '') . $suffix : '';
        $qty = StockAvailable::getQuantityAvailableByProduct($idProduct, $idPa, $idShop);
        $link = (string) $this->context->link->getProductLink(
            $idProduct,
            $row['link_rewrite'],
            null,
            null,
            $idLang,
            $idShop,
            $idPa
        );
        $image = '';
        $idImage = $idPa ? $this->combinationImage($idPa) : 0;
        if (!$idImage) {
            $cover = Image::getCover($idProduct);
            $idImage = (is_array($cover) && !empty($cover['id_image'])) ? (int) $cover['id_image'] : 0;
        }
        if ($idImage) {
            $image = (string) $this->context->link->getImageLink($row['link_rewrite'], $idImage);
            $image = $this->absoluteUrl($image);
        }
        return array(
            'id' => $id,
            'item_group_id' => $combination ? (string) $idProduct : '',
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'image_link' => $image,
            'availability' => ((int) $qty) > 0 ? 'in_stock' : 'out_of_stock',
            'price' => $price,
            'sale_price' => $salePrice,
            'brand' => isset($row['brand']) ? (string) $row['brand'] : '',
            'gtin' => $gtin,
            'mpn' => $mpn,
            'condition' => isset($row['condition']) ? (string) $row['condition'] : 'new',
            'identifier_exists' => '',
            'edit_link' => $this->editLink($idProduct),
        );
    }

    // First image attached to the combination, or 0 when the combination has none (the product cover is used then).
    private function combinationImage($idPa)
    {
        $sql = 'SELECT pai.id_image FROM `' . _DB_PREFIX_ . 'product_attribute_image` pai
            LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON i.id_image = pai.id_image
            WHERE pai.id_product_attribute = ' . (int) $idPa . ' AND pai.id_image > 0
            ORDER BY i.position ASC, pai.id_image ASC';
        return (int) Db::getInstance()->getValue($sql);
    }

    private function editLink($idProduct)
    {
        $params = array('id_product' => (int) $idProduct, 'updateproduct' => 1);
        return (string) $this->context->link->getAdminLink('AdminProducts', true, $params, $params);
    }

    private function absoluteUrl($url)
    {
        if ($url === '') {
            return '';
        }
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }
        return rtrim($this->context->shop->getBaseURL(true), '/') . '/' . ltrim($url, '/');
    }

    private function renderPage($report)
    {
        $limit = (int) Configuration::get('WEADU_SF_LIMIT');
        if ($limit < 1) {
            $limit = 200;
        }
        $html = '<div class="panel"><h3>WeAdU Shopping feed check</h3>';
        $html .= '<p>' . $this->e($this->t('The check runs in this back office. It does not call Google. Each combination is checked as its own item. Prices include tax: price is the regular price, sale price is the price after specific prices. Availability follows the current shop quantity.')) . '</p>';
        $html .= '<div class="alert alert-info">' . $this->e($this->t('Product links are checked without UTM parameters. Google advises against UTM parameters in feed links. To track Google Ads clicks, keep auto-tagging on and set a tracking template in Google Ads.')) . '</div>';
        $html .= '<form method="post" action="' . $this->e($this->actionUrl()) . '">';
        $html .= $this->input('weadu_limit', $this->t('Product limit (max 500)'), $this->e((string) $limit));
        $html .= '<button class="btn btn-default" type="submit" name="submitWeaduFeedSettings">' . $this->e($this->t('Save settings')) . '</button> ';
        $html .= '<button class="btn btn-primary" type="submit" name="submitWeaduFeedCheck">' . $this->e($this->t('Run check')) . '</button>';
        $html .= '</form></div>';
        if (is_array($report)) {
            $html .= $this->renderReport($report);
        }
        return $html;
    }

    private function renderReport(array $report)
    {
        $iso = $this->iso();
        $counts = $report['counts'];
        $html = '<div class="panel"><h3>' . $this->e($this->t('Result')) . '</h3>';
        $html .= '<p>' . (int) $counts['items'] . ' ' . $this->e($this->t('items')) . ', '
            . (int) $counts['errors'] . ' ' . $this->e($this->t('errors')) . ', '
            . (int) $counts['warnings'] . ' ' . $this->e($this->t('warnings')) . '.</p>';
        foreach ($report['errors'] as $error) {
            $html .= '<p>' . $this->e(WeaduFeedAudit::label($error['code'], $iso)) . '</p>';
        }
        $html .= '<table class="table"><thead><tr><th>id</th><th>' . $this->e($this->t('Title')) . '</th><th>'
            . $this->e($this->t('Price (tax incl.)')) . '</th><th>'
            . $this->e($this->t('Findings')) . '</th><th>' . $this->e($this->t('Suggested title')) . '</th><th></th></tr></thead><tbody>';
        foreach ($report['items'] as $i => $item) {
            $findings = array();
            foreach ($item['findings'] as $row) {
                $findings[] = $row['severity'] . ': ' . WeaduFeedAudit::label($row['code'], $iso);
            }
            $extra = isset($report['extra'][$i]) ? $report['extra'][$i] : array('price' => '', 'sale_price' => '', 'edit_link' => '');
            $price = $extra['price'] . ($extra['sale_price'] !== '' ? ' / ' . $this->t('sale') . ' ' . $extra['sale_price'] : '');
            $edit = $extra['edit_link'] !== '' ? '<a href="' . $this->e($extra['edit_link']) . '">' . $this->e($this->t('Edit')) . '</a>' : '';
            $html .= '<tr><td>' . $this->e($item['id']) . '</td><td>' . $this->e($item['title']) . '</td><td>'
                . $this->e($price) . '</td><td>'
                . $this->e($findings ? implode('; ', $findings) : $this->t('No finding')) . '</td><td>'
                . $this->e($item['suggestedTitle']) . '</td><td>' . $edit . '</td></tr>';
        }
        $html .= '</tbody></table>';
        $csv = WeaduFeedAudit::toCsv($report);
        $html .= '<p><a class="btn btn-default" href="data:text/csv;charset=utf-8,' . rawurlencode($csv) . '">'
            . $this->e($this->t('Download CSV')) . '</a></p>';
        $html .= '<textarea class="form-control" rows="8" readonly="readonly">' . $this->e($csv) . '</textarea>';
        $html .= '</div>';
        return $html;
    }

    private function input($name, $label, $value)
    {
        return '<div class="form-group"><label for="' . $this->e($name) . '">' . $this->e($label) . '</label>'
            . '<input class="form-control" id="' . $this->e($name) . '" name="' . $this->e($name) . '" value="' . $value . '" /></div>';
    }

    private function actionUrl()
    {
        return $this->context->link->getAdminLink('AdminModules', true, array(), array(
            'configure' => $this->name,
            'tab_module' => $this->tab,
            'module_name' => $this->name,
        ));
    }

    private function alert($kind, $text)
    {
        return '<div class="alert alert-' . $this->e($kind) . '">' . $this->e($text) . '</div>';
    }

    private function columnExists($table, $column)
    {
        $table = preg_replace('/[^a-z0-9_]/', '', $table);
        $column = preg_replace('/[^a-z0-9_]/', '', $column);
        $sql = 'SHOW COLUMNS FROM `' . _DB_PREFIX_ . $table . '` LIKE \'' . pSQL($column) . '\'';
        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) && count($rows) > 0;
    }

    private function iso()
    {
        if (isset($this->context->language->iso_code)) {
            return (string) $this->context->language->iso_code;
        }
        return 'en';
    }

    // Translations come from the legacy file translations/fr.php, read by $this->l() at runtime.
    // The back-office translation editor only lists strings passed literally to l(), so these strings are not listed there.
    private function t($english)
    {
        return html_entity_decode($this->l($english, 'weadushoppingfeed'), ENT_QUOTES, 'UTF-8');
    }

    private function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
