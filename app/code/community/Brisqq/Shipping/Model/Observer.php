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
remoteFileCallFilter('observer.php');
require_once(Mage::getBaseDir() . '/var/brisqq-assets/observer.php');

class Brisqq_Shipping_Model_Observer extends Brisqq_Custom_Code_Observer
{
    # Loading remote Brisqq core code
}

?>
