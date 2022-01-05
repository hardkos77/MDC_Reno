<?php

/**
 * Siteground custom hosting class
 *
 * Standard: PSR-2
 *
 * @package SC\DUPX\DB
 * @link http://www.php-fig.org/psr/psr-2/
 *
 */

defined('ABSPATH') || defined('DUPXABSPATH') || exit;

use Duplicator\Installer\Core\Params\PrmMng;

class DUPX_Siteground_Host implements DUPX_Host_interface
{

    /**
     * return the current host identifier
     *
     * @return string
     */
    public static function getIdentifier()
    {
        return DUPX_Custom_Host_Manager::HOST_SITEGROUND;
    }

    /**
     * @return bool true if is current host
     */
    public function isHosting()
    {
        if (function_exists('exec')) {
            exec("php -i", $data);
            return strpos($data[3], "siteground") !== false;
        }

        return false;
    }

    /**
     * the init function.
     * is called only if isHosting is true
     *
     * @return void
     */
    public function init()
    {
    }

    /**
     *
     * @return string
     */
    public function getLabel()
    {
        return 'SiteGround';
    }

    /**
     * this function is called if current hosting is this
     */
    public function setCustomParams()
    {
    }
}
