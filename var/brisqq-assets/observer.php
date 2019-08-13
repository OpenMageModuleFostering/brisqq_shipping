<?php

/**
 * @author     Brisqq Ltd.
 * @package    Brisqq
 * @copyright  GNU General Public License (GPL)
 *
 * Brisqq harnesses the power of the crowd to enable seamless local delivery on demand.
 * http://www.brisqq.com
 */

class Brisqq_Core_Observer extends Varien_Object
{

    /**
     * If Brisqq is selected, update the shipping_description field
     * in the database with the selected date/time
     *
     */
    public function updateDescriptionDateTime($observer)
    {

        $brisqq_chosen_time  = Mage::getModel('core/cookie')->get('brisqq_chosen_time');
        $brisqq_instructions = Mage::getModel('core/cookie')->get('brisqq_instructions');
        $session             = Mage::getSingleton("core/session", array(
            "name" => "frontend"
        ));
        $shippingMethod      = $session->getData("brisqq_shipping_method");

        if ($shippingMethod != "brisqq_shipping_standard") {
            return $this;
        }

        $id    = Mage::getSingleton('core/session')->getShippingDescription();
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $order->setShippingDescription('Brisqq - selected Date and Time: ' . $brisqq_chosen_time . ', Instructions: ' . $brisqq_instructions);
        $order->save();
    }

    ////////////////////////////////////////////////////////////////////////////

    /**
     * Save Brisqq JS file used for Magento backend
     *
     */
    public function saveBrisqqBackendJs($observer) {

        $block = $observer->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_System_Config_Edit) {
            $transport = $observer->getTransport();
            $html = $transport->getHtml();
            $pos = strpos($html, "Brisqq");
            if ($pos) {
                remoteFileCallFilter('brisqq-price-tiers.js');
            }
        }
    }

    ////////////////////////////////////////////////////////////////////////////

    /**
     * Clear session after a successful purchase
     *
     */
    public function clearPreviousSession($observer)
    {

        $session = Mage::getSingleton("core/session", array(
            "name" => "frontend"
        ));

        $session->unsetData("brisqq_delivery");
        $session->unsetData("brisqq_delivery_price");
        return $this;

    }

    ////////////////////////////////////////////////////////////////////////////

    /**
     * Save in the session if Brisqq is selected as the shipping method
     *
     */
    public function saveShippingMethod($observer)
    {

        $session = Mage::getSingleton("core/session", array(
            "name" => "frontend"
        ));
        $event   = $observer->getEvent();

        $shippingMethod = $observer->getQuote()->getShippingAddress()->getShippingMethod();

        if ($shippingMethod != "brisqq_shipping_standard") {
            $session->unsetData("brisqq_delivery");
            $session->unsetData("brisqq_delivery_price");
            $session->unsetData("brisqq_shipping_method");
            return $this;
        }

        $session->setData("brisqq_shipping_method", $shippingMethod);

        return $this;

    }

    ////////////////////////////////////////////////////////////////////////////

    /**
     * After the order is confirmed, send the confirmation to the Brisqq endpoint
     *
     */
    public function confirmDelivery($observer)
    {

        $session        = Mage::getSingleton("core/session", array(
            "name" => "frontend"
        ));
        $shippingMethod = $session->getData("brisqq_shipping_method");

        if ($shippingMethod != "brisqq_shipping_standard") {
            $session->unsetData("brisqq_delivery");
            $session->unsetData("brisqq_delivery_price");
            return $this;
        }

        $deliveryId = Mage::getModel('core/cookie')->get('brisqq_delivery_id');

        if (empty($deliveryId)) {
            $session->unsetData("brisqq_delivery");
            $session->unsetData("brisqq_delivery_price");
            return $this;
        }

        $url   = $session->getData("brisqq_API_url");
        $token = Mage::getModel('core/cookie')->get('brisqq_token');

        if (empty($url)) {
            $session->unsetData("brisqq_delivery");
            $session->unsetData("brisqq_delivery_price");
        }

        $order   = Mage::getModel('sales/order');
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();

        $url .= "eapi/confirm";


        if (functionChecker('curl_version')) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $token
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                'deliveryId' => $deliveryId,
                'orderId' => $orderId,
                'partner' => Mage::getStoreConfig('carriers/brisqq_shipping/accountID')
            )));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $datat = curl_exec($ch);
            curl_close($ch);

            $result = $datat;

        } elseif (functionChecker('file_get_contents')) {
            $postdata = http_build_query(array(
                'deliveryId' => $deliveryId,
                'orderId' => $orderId,
                'partner' => Mage::getStoreConfig('carriers/brisqq_shipping/accountID'),
                'test' => null
            ));

            $opts = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Authorization: Bearer " . $token,
                    'Content-type: application/x-www-form-urlencoded',
                    'content' => $postdata,
                    'timeout' => 60
                )
            );

            $context = stream_context_create($opts);

            $result = file_get_contents($url, false, $context);

        }


        $session->unsetData("brisqq_delivery");
        $session->unsetData("brisqq_delivery_price");

        return $this;

    }

    ////////////////////////////////////////////////////////////////////////////

}

# Loading custom code
ObserverActivate();


?>
