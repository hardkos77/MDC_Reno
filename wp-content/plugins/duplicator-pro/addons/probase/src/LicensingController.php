<?php

/**
 * Version Pro Base functionalities
 *
 * Name: Duplicator PRO base
 * Version: 1
 * Author: Snap Creek
 * Author URI: http://snapcreek.com
 *
 * @package Duplicator
 * @copyright (c) 2021, Snapcreek LLC
 *
 */

namespace Duplicator\Addons\ProBase;

use DUP_PRO_U;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Controllers\SettingsPageController;

class LicensingController
{
    const L2_SLUG_LICENSING = 'licensing';

    /**
     * License controller init
     *
     * @return void
     */
    public static function init()
    {
        add_filter('duplicator_sub_menu_items_' . ControllersManager::SETTINGS_SUBMENU_SLUG, array(__CLASS__, 'licenseSubMenu'));
        add_filter('duplicator_render_page_content_' . ControllersManager::SETTINGS_SUBMENU_SLUG, array(__CLASS__, 'renderLicenseContent'));
    }

    /**
     * Add license sub menu page
     *
     * @param array $subMenus sub menus
     *
     * @return array
     */
    public static function licenseSubMenu($subMenus)
    {
        $subMenus[] = SettingsPageController::generateSubMenuItem(self::L2_SLUG_LICENSING, __('Licensing', 'duplicator-pro'), '', true, 100);
        return $subMenus;
    }

    /**
     * Render license page
     *
     * @param string[] $currentLevelSlugs current page/tables slugs
     *
     * @return void
     */
    public static function renderLicenseContent($currentLevelSlugs)
    {
        switch ($currentLevelSlugs[1]) {
            case self::L2_SLUG_LICENSING:
                require ProBase::getAddonPath() . '/template/licensing.php';
                break;
        }
    }

    /**
     * License type viewer
     *
     * @param array $opts options
     *
     * @return void
     */
    public static function typeViewer($opts)
    {
        $opts['mu1'] = '<i class="far fa-check-square"></i>  ';
        $opts['mu2'] = $opts['mu2'] == 1 ? '<i class="far fa-check-square"></i>  ' : '<i class="far fa-square"></i>  ';

        $txt_lic_hdr = DUP_PRO_U::__('Site Licenses');
        $txt_lic_msg = DUP_PRO_U::__(
            'Number of site licenses indicates the number of sites the plugin can be active on at any one time. ' .
            'At any point you may deactivate/uninstall the plugin to free up the license and use the plugin elsewhere if needed.'
        );
        $txt_mu1_hdr = DUP_PRO_U::__('Multisite Basic');
        $txt_mu1_msg = DUP_PRO_U::__('Can backup & migrate standalone sites and full multisite networks.');
        $txt_mu2_hdr = DUP_PRO_U::__('Multisite Plus+');
        $txt_mu2_msg = DUP_PRO_U::__(
            'Ability to install a subsite as a standalone site. ' .
            'Additional subsite features are planned for Multisite Plus+ in the future. ' .
            'This option is only available in Business and Gold.'
        );

        $lic_limit = is_numeric($opts['lic']) ? $opts['lic'] : "<a href='#' id='unlmtd-lic' onclick='DupPro.Licensing.ToggleUnlimited()'>{$opts['lic']}</a>";

        //ARRAY:
        echo '<div>';
        echo "<i class='far fa-check-square'></i>  {$txt_lic_hdr} ({$lic_limit}) " .
            "<i class='fa fa-question-circle' data-tooltip-title='{$txt_lic_hdr}' data-tooltip='{$txt_lic_msg}'></i><br/>";
        echo $opts['mu1'] . "{$txt_mu1_hdr} <i class='fa fa-question-circle' data-tooltip-title='{$txt_mu1_hdr}' data-tooltip='{$txt_mu1_msg}'></i><br/>";
        echo $opts['mu2'] . "{$txt_mu2_hdr} <i class='fa fa-question-circle' data-tooltip-title='{$txt_mu2_hdr}' data-tooltip='{$txt_mu2_msg}'></i><br/>";
        echo '</div>';

        echo '<div id="unlmtd-lic-text" class="unlmtd-lic-text">';
        printf(
            __(
                '<b>*License Allocations</b>: Business/Gold site licensess are granted in batches of 500.'
                . ' If you hit the 500 please %1s submit a help ticket%2s request and we will grant you another batch of 500.'
                . ' This process helps us to make sure that licenses are not stolen or abused for our users.',
                'duplicator-pro'
            ),
            '<a href="https://snapcreek.com/ticket/index.php?a=add&category=1" target="_blank">',
            '</a>'
        );
        echo '</div>';
    }
}
