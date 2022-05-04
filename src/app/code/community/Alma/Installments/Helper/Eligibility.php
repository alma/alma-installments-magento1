<?php
/**
 * 2018 Alma / Nabla SAS
 *
 * THE MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @author    Alma / Nabla SAS <contact@getalma.eu>
 * @copyright 2018 Alma / Nabla SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 *
 */

use Alma\API\RequestError;

class Alma_Installments_Helper_Eligibility extends Mage_Core_Helper_Abstract
{
    const MAGE_CART_KEY = 'checkout/cart';

    /**
     * @var Mage_Core_Helper_Abstract|null
     */
    private $config;
    /**
     * @var \Alma\API\Client
     */
    private $alma;
    /**
     * @var AlmaLogger
     */
    private $logger;
    /**
     * @var Alma_Installments_Helper_FeePlansHelper
     */
    private $feePlansHelper;
    /**
     * @var array
     */
    private $currentEligibleFeePlans;
    /**
     * @var bool
     */
    private $eligibleFeePlansAreLoaded;

    public function __construct()
    {
        $this->logger = Mage::helper('alma/logger')->getLogger();
        $this->alma = Mage::helper('alma/AlmaClient')->getDefaultClient();
        $this->config = Mage::helper('alma/config');
        $this->feePlansHelper = Mage::helper('alma/FeePlansHelper');
        $this->currentEligibleFeePlans = array();
        $this->eligibleFeePlansAreLoaded = false;
    }

    /**
     * @return Alma\API\Endpoints\Results\Eligibility[]
     * @throws Mage_Core_Exception
     */
    public function getEligibleFeePlans()
    {
       if($this->eligibleFeePlansAreLoaded){
           return $this->currentEligibleFeePlans;
       }
        $feePlansEligibilities = $this->loadEligibleFeePlansFromApi();
        $eligibleFeePlans = $this->filterEligibleFeePlans($feePlansEligibilities);
        $this->eligibleFeePlansAreLoaded = true;
        $this->currentEligibleFeePlans = $eligibleFeePlans;
        return $eligibleFeePlans;
    }

    /**
     * @return Alma\API\Endpoints\Results\Eligibility[]
     * @throws Mage_Core_Exception|Exception
     */
    private function loadEligibleFeePlansFromApi()
    {
        if(!$this->checkEligibilityPrerequisite()){
            return [];
        }
        $quote = Mage::helper(self::MAGE_CART_KEY)->getQuote();
        $cartTotal = Alma_Installments_Helper_Functions::priceToCents((float)$quote->getGrandTotal());
        $enabledFeePlansInConfig = $this->feePlansHelper->getEnabledFeePlansConfigFromBackOffice();

        $feePlansEligibilities = [];
        $installmentsQuery = [];
        foreach ($enabledFeePlansInConfig as $configFeePlan) {
            if (
                $cartTotal >= $configFeePlan[Alma_Installments_Helper_FeePlansHelper::MIN_DISPLAY_KEY] &&
                $cartTotal <= $configFeePlan[Alma_Installments_Helper_FeePlansHelper::MAX_DISPLAY_KEY]
            ){
                $installmentsQuery[] = [
                    'purchase_amount' => $cartTotal,
                    'installments_count' => $configFeePlan['installments_count'],
                    'deferred_days' => $configFeePlan['deferred_days'],
                    'deferred_month' => $configFeePlan['deferred_months'],
                    'cart_total' => $cartTotal,
                ];
            }
        }
        if (!empty($installmentsQuery)) {
            try {
                $feePlansEligibilities = $this->alma->payments->eligibility(
                    $this->formatEligibilityPayload($quote, $installmentsQuery),
                    true
                );
            } catch (RequestError $e) {
                $this->logger->error('$e',[$e->getMessage()]);
            }
        }
        return $feePlansEligibilities;
    }

    /**
     * @param Alma\API\Endpoints\Results\Eligibility[] $feePlansEligibilities
     * @return Alma\API\Endpoints\Results\Eligibility[]
     */
    private function filterEligibleFeePlans($feePlansEligibilities){
        $eligibleFeePlans = [];
        foreach ($feePlansEligibilities as $planKey => $feePlansEligibility) {
            if ($feePlansEligibility->isEligible()){
                $eligibleFeePlans[$planKey]=$feePlansEligibility;
            }
        }
        return $eligibleFeePlans;
    }

    /**
     * @return bool
     */
    public function checkEligibility() {
        $isEligible = false;
        $eligibleFeePlans = $this->getEligibleFeePlans();
        if(count($eligibleFeePlans)){
            $isEligible = true;
        }
        return $isEligible;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->config->getEligibilityMessage();
    }

    /**
     * @return bool
     */
    private function checkItemsTypes()
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::helper(self::MAGE_CART_KEY)->getQuote();
        $excludedProductTypes = $this->config->getExcludedProductTypes();

        /** @var Mage_Sales_Model_Quote_Item $item */
        foreach ($quote->getAllItems() as $item) {
            if (in_array($item->getRealProductType(), $excludedProductTypes)) {
                $this->logger->error('A product is in excluded product type list',[]);
                return false;
            }
        }
        return true;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @param $installmentsQuery
     * @return array
     */
    private function formatEligibilityPayload($quote,$installmentsQuery)
    {
        $BillingAddressCountryId = $quote->getBillingAddress()->getCountryId();
        $shippingAddressCountryId = $quote->getShippingAddress()->getCountryId();
        $payload = [
        'online'          => 'online',
        'purchase_amount' => Alma_Installments_Helper_Functions::priceToCents((float)$quote->getGrandTotal()),
        'locale'          => Mage::app()->getLocale()->getLocaleCode(),
        'queries'         => $installmentsQuery,
         ];
        if(!empty($BillingAddressCountryId)){
            $payload['billing_address'] = ['country' => $BillingAddressCountryId];
        }
        if(!empty($shippingAddressCountryId)){
            $payload['shipping_address'] = ['country' => $shippingAddressCountryId];
        }
        return $payload;
    }

    /**
     * @return bool
     */
    private function checkEligibilityPrerequisite()
    {
        if (!$this->alma) {
            $this->logger->error('Alma client is not define',[]);
            return false;
        }
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::helper(self::MAGE_CART_KEY)->getQuote();
        if(!$quote) {
            $this->logger->error('Quote is not define ',[]);
            return false;
        }

        if(!$this->checkItemsTypes()){
            return false ;
        }
        return true;
    }
}
