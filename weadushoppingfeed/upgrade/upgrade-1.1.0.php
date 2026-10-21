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

// 1.1.0 removed the UTM option: delete the settings stored by 1.0.0.
function upgrade_module_1_1_0($module)
{
    foreach (array('WEADU_SF_SOURCE', 'WEADU_SF_MEDIUM', 'WEADU_SF_CAMPAIGN') as $key) {
        Configuration::deleteByName($key);
    }
    return true;
}
