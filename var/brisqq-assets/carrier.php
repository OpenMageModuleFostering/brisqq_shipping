<?php

/**
 * @author     Brisqq Ltd.
 * @package    Brisqq
 * @copyright  GNU General Public License (GPL)
 *
 * Brisqq harnesses the power of the crowd to enable seamless local delivery on demand.
 * http://www.brisqq.com
 */
class Brisqq_Core_Carrier extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'brisqq_shipping';

    protected $_brisqqAPIEndpoint = 'https://core-staging.brisqq.com/';

    protected $_getInitURL = 'eapi/checkCoverage';

    public $_APIparams = null;

    ///////////////////////////////////////////////////////////////////////

    /**
     * Custom method for selecting the Brisqq production/staging endpoint
     *
     * @param void
     */
    public function _isProduction()
    {

        if (production()) {
            $this->_brisqqAPIEndpoint = 'https://core.brisqq.com/';
            if (chromePhpLogsCarrier()) {
                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Brisqq plugin is in the production mode.');
                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . $this->$_brisqqAPIEndpoint);
            }
        }
    }


    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {

        $result = Mage::getModel('shipping/rate_result');

        if (!isset($_COOKIE['brisqq_compatible']) || $_COOKIE['brisqq_compatible'] == "false") {
            return $result;
        }

        $countrycode = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getData('country_id');

        if ($countrycode !== 'GB') {
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Selected country is not GB: ' . $countrycode);
            return $result;
        }

        $this->_isProduction();


        $session = Mage::getSingleton("core/session", array(
            "name" => "frontend"
        ));

        $session->unsetData("brisqq_delivery_price");
        $session->unsetData("brisqq_delivery");
        $session->unsetData("brisqq_API_url");
        $session->unsetData("brisqq_API_token");
        unset($_COOKIE['brisqq_account_id']);
        unset($_COOKIE['brisqq_delivery']);
        setrawcookie('brisqq_account_id', null);
        setrawcookie('brisqq_delivery', null);
        // skip if not enabled
        if (!Mage::getStoreConfig('carriers/' . $this->_code . '/active')) {
            if (chromePhpLogsCarrier()) {
                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Brisqq plugin is not enabled.');
            }
            return $result;
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $shipping = $quote->getShippingAddress();

        $this->_APIparams = json_decode($this->_getAPIInit(), true);

        if (empty($this->_APIparams) || !$this->_APIparams['covered']) {
            if (chromePhpLogsCarrier()) {
                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- API params is empty or not covered.');
                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Check if API params is empty: ' . empty($this->_APIparams) . ' (should be false)');
                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Check if API params is covered: ' . $this->_APIparams['covered'] . ' (should be true)');
            }
            return $result;
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $cartItems = $quote->getAllVisibleItems(); // get all items in the cart
        $volWeightSum = 0; // define volume weight sum variable
        $deliverySize = 'L'; // default delivery size variable
        $packageCount = 0; // gross products quantity
        $countVolumetricWeight = false; // tells should volumetric weight be counted

        // iterate through each product in the cart
        foreach ($cartItems as $item) {
            $currentProductQty = $item->getQty(); // get product quantity
            $packageCount += $currentProductQty; // add each product quantity to the gross quantity


            $product_id = $item->getProductId(); //product id string

            $productObject = Mage::getModel('catalog/product')->load($product_id); // get product object


            if ($countVolumetricWeight) {
                // get product width, height and depth

                $productWidth = (int)$productObject->getData('width');
                $productHeight = (int)$productObject->getData('length');
                $productDepth = (int)$productObject->getData('depth');

                if (chromePhpLogsCarrier()) {
                    ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Products volumetric weight details:');
                    ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Width: ' . $productWidth);
                    ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Height: ' . $productHeight);
                    ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Depth: ' . $productDepth);
                }

                $volumetric_weight = $productWidth * $productHeight * $productDepth / 5000;

                if ($volumetric_weight == 0) {
                    $missing_vol_weight_params++;
                }

                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- missing_vol_weight_params: ' . $missing_vol_weight_params);
                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- volumetric_weight: ' . $volumetric_weight);
                $volWeightSum = $volWeightSum + $volumetric_weight * $currentProductQty;
                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- volWeightSum: ' . $volWeightSum);

                if ($missing_vol_weight_params == 0) {
                    ChromePhp::log($missing_vol_weight_params);
                    if ($volWeightSum <= 10) {
                        $deliverySize = 'S';
                    } elseif ($volWeightSum <= 15 && $volWeightSum > 10) {
                        $deliverySize = 'M';
                    } elseif ($volWeightSum <= 30 && $volWeightSum > 15) {
                        $deliverySize = 'L';
                    } elseif ($volWeightSum <= 100 && $volWeightSum > 30) {
                        $deliverySize = 'XL';
                    }
                } else {
                    $deliverySize = 'L';
                }
            };
        };

        if (chromePhpLogsCarrier()) {
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- delivery size next line');
            ChromePhp::log($volWeightSum);
            ChromePhp::log($deliverySize);

        }

        // Get customer info from the checkout page session
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $shipping = $quote->getShippingAddress();

        $billingInfo = $quote->getBillingAddress();

        $flname = $shipping->getFirstname() . " " . $shipping->getLastname();
        $phone = $shipping->getTelephone();
        $email = $shipping->getEmail();
        if (!$email) {
            $email = $billingInfo->getEmail();
        }
        $adress = $shipping->getStreetFull();
        $postc = $shipping->getPostcode();


        if (chromePhpLogsCarrier()) {
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Customer info from the current session:');
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Name: ' . $flname);
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Phone: ' . $phone);
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Email: ' . $email);
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Address: ' . $adress);
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Post Code: ' . $postc);
        }

        // if there is no customer details in the checkout page session, take info from default shipping account details
        $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping();
        if ($customerAddressId) {
            $address = Mage::getModel('customer/address')->load($customerAddressId);
            $test = $address->getData();
            if (!$flname || $flname == ' ') {
                $flname = $test['firstname'] . " " . $test['lastname'];
            }
            if (!$phone) {
                $phone = $test['telephone'];
            }
            if (!$adress) {
                $adress = $test['street'];
            }
            if (!$email) {
                $email = $quote->getCustomerEmail();
            }
            if (!$postc) {
                $postc = $test['postcode'];
            }
        }


        $cartGrossTotal = 0;

        foreach ($quote->getAllItems() as $item) {
            $cartGrossTotal += $item->getPriceInclTax() * $item->getQty();
        }

        $delivery = array(
            "price" => $this->_APIparams['price'],
            "additionalPackagePrice" => $this->_APIparams['additionalPackagePrice'],
            "distance" => $this->_APIparams['distance'],
            "matchedDistance" => $this->_APIparams['matchedDistance'],
            "dropoff" => array(
                "contactName" => $flname,
                "contactPhone" => $phone,
                "contactEmail" => $email,
                "address" => $adress,
                "postCode" => $postc,
                "coordinates" => $this->_APIparams['dropoff']['coordinates']
            ),
            "packageCount" => $packageCount,
            "size" => $deliverySize,
            "orderValue" => $cartGrossTotal
        );

        $session->setData("brisqq_API_url", $this->_brisqqAPIEndpoint);
        $session->setData("brisqq_API_token", $this->_APIparams['token']);

        $this->_APIparams['url'] = $this->_brisqqAPIEndpoint;

        $cookieTest1 = setrawcookie('brisqq_delivery', rawurlencode(json_encode($delivery, JSON_FORCE_OBJECT)), time() + (86400 * 30), "/");
        $cookieTest2 = setrawcookie('brisqq_account_id', $this->getConfigData('accountID'), time() + (86400 * 30), "/");

        if (chromePhpLogsCarrier()) {
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Current api endpoint: ' . $this->_brisqqAPIEndpoint);
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Trying to set delivery cookie, results: ' . $cookieTest1);
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Trying to set API settings cookie, results: ' . $cookieTest2);
        }

        $result->append($this->_getTimeslotShippingRate());

        return $result;
    }

    ///////////////////////////////////////////////////////////////////////

    public function getAllowedMethods()
    {

        return array(
            'timeslot' => $this->getConfigData('timeslotDescription')
        );

    }

    ///////////////////////////////////////////////////////////////////////
    /**
     * Brisqq rest API communication
     *
     * @param string $url
     *
     * @return string
     */
    public function file_get_contents_curl($url)
    {

        $accountId = $this->getConfigData('accountID');
        $dropoffPostCode = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getPostcode();

        if (chromePhpLogsCarrier()) {
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Requesting API settings from Brisqq server... ');
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- URL: ' . $url);
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Account ID: ' . $accountId);
            ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Dropoff post code: ' . $dropoffPostCode);
        }

        if (functionChecker('curl_version')) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);

            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                'accountId' => $accountId,
                'dropoffPostCode' => $dropoffPostCode
            )));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $data = curl_exec($ch);

            if (chromePhpLogsCarrier()) {
                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Sending http request via CURL');
                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' Server response: ' . isset($data));
            }

            curl_close($ch);
        } elseif (functionChecker('file_get_contents')) {
            $postdata = http_build_query(array(
                'accountId' => $accountId,
                'dropoffPostCode' => $dropoffPostCode
            ));

            $opts = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $postdata
                )
            );

            $context = stream_context_create($opts);

            $data = file_get_contents($url, false, $context);

            if (chromePhpLogsCarrier()) {
                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . '- Sending http request via FILE GET CONTENTS');
                ChromePhp::log(fileName(__FILE__) . '/' . __LINE__ . ' Server response: ' . isset($data));
            }

        }

        return $data;
    }

    protected function _getAPIInit()
    {
        $url = $this->_brisqqAPIEndpoint . $this->_getInitURL;
        $result = $this->file_get_contents_curl($url);
        return $result;
    }

    ///////////////////////////////////////////////////////////////////////

    protected function _getTimeslotShippingRate()
    {

        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);

        $rate->setCarrierTitle($this->getConfigData('title'));

        $rate->setMethod('standard');
        $rate->setMethodTitle($this->getConfigData('timeslotDescription'));

        $price = $this->_APIparams['price'];
        $matchedDistance = $this->_APIparams['matchedDistance'];

        $partnerPrices = $this->getConfigData('partnerPriceTiers');


        if (isset($partnerPrices)) {
            $partnerpriceserialized = explode('&', $partnerPrices);
        }

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $cartGrossTotal = 0;

        foreach ($quote->getAllItems() as $item) {
            $cartGrossTotal += $item->getPriceInclTax() * $item->getQty();
        }

        $partnerPricesRules = $this->getConfigData('priceRulesSaved');

        if (isset($partnerPricesRules)) {
            $pricesRulesSerialized = explode('&', $partnerPricesRules);
        }

        $moreLessRule = array();
        if (isset($partnerpriceserialized)) {
            foreach ($partnerpriceserialized as $value) {
                $customerpricetiers = explode('=', $value);
                if (isset($customerpricetiers[1]) && $customerpricetiers[1] !== "" && $customerpricetiers[1] !== " " && $customerpricetiers[0] == $matchedDistance) {
                    $GLOBALS['brisqqShowTierPrice'] = $customerpricetiers[1];
                }
            }
        }


        if (isset($GLOBALS['brisqqShowTierPrice'])) {
            $showprice = $GLOBALS['brisqqShowTierPrice'];
        } else {
            $showprice = $price;
        }

        if (!empty($showprice)) {
            //
            if (isset($pricesRulesSerialized) && $pricesRulesSerialized[0] != "") {
                foreach ($pricesRulesSerialized as $key => $eachRule) {
                    if (strpos($eachRule, '=') !== false) {
                        $equalRule = explode("=", $eachRule);
                        if ($equalRule[0] == $cartGrossTotal) {
                            if (strpos($equalRule[1], '%') !== false) {
                                $priceOnly = explode('%', $equalRule[1]);
                                $newprice = round($showprice * ((100 - $priceOnly[0]) / 100), 2);
                                $showprice = $newprice;
                                if (!empty($showprice)) {
                                    $rate->setPrice($showprice);
                                }
                                $rate->setCost(0);
                                return $rate;
                            } else {
                                $showprice = $equalRule[1];
                                if (!empty($showprice)) {
                                    $rate->setPrice($showprice);
                                }
                                $rate->setCost(0);
                                return $rate;
                            }
                        }
                    }

                    if (strpos($eachRule, '>') !== false) {
                        $moreRule = explode(">", $eachRule);
                        if ($moreRule[0] <= $cartGrossTotal) {
                            $moreLessRule[] = $moreRule;
                        }
                    }

                    if (strpos($eachRule, '<') !== false) {
                        $lessRule = explode("<", $eachRule);
                        if ($lessRule[0] >= $cartGrossTotal) {
                            $moreLessRule[] = $lessRule;
                        }
                    }

                }

                if (isset($moreLessRule) && !empty($moreLessRule)) {

                    $finalRule = $moreLessRule[0];
                    if (strpos($finalRule[1], '%') !== false) {
                        $priceOnly = explode('%', $finalRule[1]);
                        $newprice = round($showprice * ((100 - $priceOnly[0]) / 100), 2);
                        $showprice = $newprice;
                        $GLOBALS['saveRulePrice'] = $newprice;
                    } else {
                        $showprice = $finalRule[1];
                        $GLOBALS['saveRulePrice'] = $finalRule[1];
                    }
                }
            }
            $rate->setPrice($showprice);
        }

        if (!empty($showprice)) {
            $rate->setPrice($showprice);
        }

        $rate->setCost(0);

        return $rate;

    }

    ///////////////////////////////////////////////////////////////////////

    //override default abstract class method
    public function isTrackingAvailable()
    {
        return true;
    }

    ///////////////////////////////////////////////////////////////////////
}

# Loading custom code
CarrierActivate();

?>
