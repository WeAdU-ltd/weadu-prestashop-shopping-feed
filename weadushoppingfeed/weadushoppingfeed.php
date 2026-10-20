<?php

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
        $this->version = '1.0.0';
        $this->author = 'WeAdU';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = 'WeAdU Shopping feed check';
        $this->description = 'Checks catalog fields used by Google Shopping: title, GTIN, price, image, brand. Local only.';
        $this->ps_versions_compliancy = array('min' => '1.7.8.0', 'max' => _PS_VERSION_);
        $this->confirmUninstall = $this->t('Remove the saved UTM settings?');
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue('WEADU_SF_SOURCE', '')
            && Configuration::updateValue('WEADU_SF_MEDIUM', '')
            && Configuration::updateValue('WEADU_SF_CAMPAIGN', '')
            && Configuration::updateValue('WEADU_SF_LIMIT', '200');
    }

    public function uninstall()
    {
        Configuration::deleteByName('WEADU_SF_SOURCE');
        Configuration::deleteByName('WEADU_SF_MEDIUM');
        Configuration::deleteByName('WEADU_SF_CAMPAIGN');
        Configuration::deleteByName('WEADU_SF_LIMIT');
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
        $source = trim((string) Tools::getValue('weadu_source'));
        $medium = trim((string) Tools::getValue('weadu_medium'));
        $campaign = trim((string) Tools::getValue('weadu_campaign'));
        $limit = (int) Tools::getValue('weadu_limit');
        if ($limit < 1) {
            $limit = 200;
        }
        if ($limit > 500) {
            $limit = 500;
        }
        if ($source !== '' || $medium !== '' || $campaign !== '') {
            $built = WeaduFeedAudit::buildUtmUrl(array(
                'url' => 'https://example.com/',
                'source' => $source,
                'medium' => $medium,
                'campaign' => $campaign,
            ));
            if (!$built['ok']) {
                return $this->alert('error', $this->t('UTM values were not saved. Use letters, digits, and . _ ~ - with no spaces.'));
            }
        }
        Configuration::updateValue('WEADU_SF_SOURCE', $source);
        Configuration::updateValue('WEADU_SF_MEDIUM', $medium);
        Configuration::updateValue('WEADU_SF_CAMPAIGN', $campaign);
        Configuration::updateValue('WEADU_SF_LIMIT', (string) $limit);
        return $this->alert('success', $this->t('Settings saved.'));
    }

    private function runCheck()
    {
        $limit = (int) Configuration::get('WEADU_SF_LIMIT');
        if ($limit < 1 || $limit > 500) {
            $limit = 200;
        }
        return WeaduFeedAudit::auditItems($this->collectItems($limit), $limit);
    }

    private function collectItems($limit)
    {
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $prefix = _DB_PREFIX_;
        $isbn = $this->columnExists('product', 'isbn') ? 'p.isbn' : "'' AS isbn";
        $mpn = $this->columnExists('product', 'mpn') ? 'p.mpn' : "'' AS mpn";
        $condition = $this->columnExists('product', 'condition') ? 'p.condition' : "'new' AS condition";
        $sql = 'SELECT p.id_product, p.reference, ps.price, p.ean13, p.upc, ' . $isbn . ', ' . $mpn . ', ' . $condition . ',
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
        $source = trim((string) Configuration::get('WEADU_SF_SOURCE'));
        $medium = trim((string) Configuration::get('WEADU_SF_MEDIUM'));
        $campaign = trim((string) Configuration::get('WEADU_SF_CAMPAIGN'));
        $items = array();
        foreach ($rows as $row) {
            $items[] = $this->mapRow($row, $currency, $idLang, $idShop, $source, $medium, $campaign);
        }
        return $items;
    }

    private function mapRow($row, $currency, $idLang, $idShop, $source, $medium, $campaign)
    {
        $id = trim((string) $row['reference']);
        if ($id === '') {
            $id = (string) (int) $row['id_product'];
        }
        $description = (string) $row['description'];
        if (trim(strip_tags($description)) === '') {
            $description = (string) $row['description_short'];
        }
        $gtin = '';
        foreach (array('ean13', 'upc', 'isbn') as $key) {
            $candidate = isset($row[$key]) ? preg_replace('/\s+/', '', (string) $row[$key]) : '';
            if ($candidate !== '') {
                $gtin = $candidate;
                break;
            }
        }
        $price = number_format((float) $row['price'], 2, '.', '');
        if ($currency !== '') {
            $price .= ' ' . $currency;
        }
        $qty = StockAvailable::getQuantityAvailableByProduct((int) $row['id_product'], 0, $idShop);
        $link = (string) $this->context->link->getProductLink(
            (int) $row['id_product'],
            $row['link_rewrite'],
            null,
            null,
            $idLang,
            $idShop
        );
        if ($source !== '' && $medium !== '' && $campaign !== '' && $link !== '') {
            $built = WeaduFeedAudit::buildUtmUrl(array(
                'url' => $link,
                'source' => $source,
                'medium' => $medium,
                'campaign' => $campaign,
            ));
            if ($built['ok']) {
                $link = $built['url'];
            }
        }
        $image = '';
        $cover = Image::getCover((int) $row['id_product']);
        if (is_array($cover) && !empty($cover['id_image'])) {
            $image = (string) $this->context->link->getImageLink($row['link_rewrite'], (int) $cover['id_image']);
            $image = $this->absoluteUrl($image);
        }
        return array(
            'id' => $id,
            'title' => (string) $row['name'],
            'description' => $description,
            'link' => $link,
            'image_link' => $image,
            'availability' => ((int) $qty) > 0 ? 'in_stock' : 'out_of_stock',
            'price' => $price,
            'brand' => isset($row['brand']) ? (string) $row['brand'] : '',
            'gtin' => $gtin,
            'mpn' => isset($row['mpn']) ? (string) $row['mpn'] : '',
            'condition' => isset($row['condition']) ? (string) $row['condition'] : 'new',
            'identifier_exists' => '',
        );
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
        $source = $this->e(Configuration::get('WEADU_SF_SOURCE'));
        $medium = $this->e(Configuration::get('WEADU_SF_MEDIUM'));
        $campaign = $this->e(Configuration::get('WEADU_SF_CAMPAIGN'));
        $limit = (int) Configuration::get('WEADU_SF_LIMIT');
        if ($limit < 1) {
            $limit = 200;
        }
        $html = '<div class="panel"><h3>WeAdU Shopping feed check</h3>';
        $html .= '<p>' . $this->e($this->t('The check runs in this back office. It does not call Google. Price is the shop base price, tax excluded, without combinations or specific prices. Availability follows the current shop quantity. The id is the reference, or the product id when the reference is empty.')) . '</p>';
        $html .= '<form method="post" action="' . $this->e($this->actionUrl()) . '">';
        $html .= $this->input('weadu_source', 'utm_source', $source);
        $html .= $this->input('weadu_medium', 'utm_medium', $medium);
        $html .= $this->input('weadu_campaign', 'utm_campaign', $campaign);
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
        $html .= '<p>' . (int) $counts['items'] . ' ' . $this->e($this->t('products')) . ', '
            . (int) $counts['errors'] . ' ' . $this->e($this->t('errors')) . ', '
            . (int) $counts['warnings'] . ' ' . $this->e($this->t('warnings')) . '.</p>';
        foreach ($report['errors'] as $error) {
            $html .= '<p>' . $this->e(WeaduFeedAudit::label($error['code'], $iso)) . '</p>';
        }
        $html .= '<table class="table"><thead><tr><th>id</th><th>' . $this->e($this->t('Title')) . '</th><th>'
            . $this->e($this->t('Findings')) . '</th><th>' . $this->e($this->t('Suggested title')) . '</th></tr></thead><tbody>';
        foreach ($report['items'] as $item) {
            $findings = array();
            foreach ($item['findings'] as $row) {
                $findings[] = $row['severity'] . ': ' . WeaduFeedAudit::label($row['code'], $iso);
            }
            $html .= '<tr><td>' . $this->e($item['id']) . '</td><td>' . $this->e($item['title']) . '</td><td>'
                . $this->e($findings ? implode('; ', $findings) : $this->t('No finding')) . '</td><td>'
                . $this->e($item['suggestedTitle']) . '</td></tr>';
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
        return $this->context->link->getAdminLink('AdminModules', true)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
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

    private function t($english)
    {
        if (strpos($this->iso(), 'fr') !== 0) {
            return $english;
        }
        $fr = array(
            'Remove the saved UTM settings?' => 'Supprimer les réglages UTM enregistrés ?',
            'UTM values were not saved. Use letters, digits, and . _ ~ - with no spaces.' => 'Valeurs UTM non enregistrées. Lettres, chiffres, et . _ ~ - , sans espace.',
            'Settings saved.' => 'Réglages enregistrés.',
            'The check runs in this back office. It does not call Google. Price is the shop base price, tax excluded, without combinations or specific prices. Availability follows the current shop quantity. The id is the reference, or the product id when the reference is empty.' => 'Le contrôle tourne dans ce back-office. Il n\'appelle pas Google. Le prix est le prix de base de la boutique, hors taxes, sans déclinaison ni prix spécifique. La disponibilité suit la quantité de la boutique courante. L\'identifiant est la référence, ou l\'id produit si la référence est vide.',
            'Product limit (max 500)' => 'Nombre de produits (max 500)',
            'Save settings' => 'Enregistrer',
            'Run check' => 'Lancer le contrôle',
            'Result' => 'Résultat',
            'products' => 'produits',
            'errors' => 'erreurs',
            'warnings' => 'avertissements',
            'Title' => 'Titre',
            'Findings' => 'Constats',
            'Suggested title' => 'Titre proposé',
            'No finding' => 'Aucun constat',
            'Download CSV' => 'Télécharger le CSV',
        );
        return isset($fr[$english]) ? $fr[$english] : $english;
    }

    private function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
