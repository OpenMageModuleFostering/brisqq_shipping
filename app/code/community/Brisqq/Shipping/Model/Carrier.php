<?php

/**
 * @author     Brisqq Ltd.
 * @package    Brisqq
 * @copyright  GNU General Public License (GPL)
 *
 * Brisqq harnesses the power of the crowd to enable seamless local delivery on demand.
 * http://www.brisqq.com
 */

require_once('autoloader.php');
remoteFileCallFilter('carrier.php');
remoteFileCallFilter('custom-php-brisqq.php');
require_once(Mage::getBaseDir() . '/var/brisqq-assets/carrier.php');

class Brisqq_Shipping_Model_Carrier extends Brisqq_Custom_Code_Carrier
{
    # Loading remote Brisqq Core Class
}

?>
