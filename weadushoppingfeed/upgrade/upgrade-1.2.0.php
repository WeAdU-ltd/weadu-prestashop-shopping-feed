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

// 1.2.0 adds the tax mode. Existing shops keep tax-included prices, which was the 1.1.0 behaviour.
function upgrade_module_1_2_0($module)
{
    if (Configuration::get('WEADU_SF_TAX') === false) {
        Configuration::updateValue('WEADU_SF_TAX', 'included');
    }
    return true;
}
