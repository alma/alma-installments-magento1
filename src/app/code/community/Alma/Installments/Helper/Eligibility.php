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
     * @var bool
     */
    private $eligible;
    /**
     * @var array
     */
	private $eligibilities;
    /**
     * @var string
     */
    private $message;
    /**
     * @var Alma_Installments_Helper_FeePlansHelper
     */
    private $feePlansHelper;
    /**
     * @var array
     */
    private $eligibleFeePlans;
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
        $this->eligibilities = array();
        $this->eligibleFeePlans = array();
        $this->eligibleFeePlansAreLoaded = false;
    }

    public function getEligibleFeePlans()
    {
       if($this->eligibleFeePlansAreLoaded){
           return $this->eligibleFeePlans;
       }
        return $this->getAlmaFeePlansEligibility();
    }

    /**
     * @return array|void
     * @throws Mage_Core_Exception
     */
    private function getAlmaFeePlansEligibility()
    {
        $this->eligibleFeePlansAreLoaded = true;
        if(!$this->checkEligibilityPrerequisite()){
            return [];
        }
        $quote = Mage::helper('checkout/cart')->getQuote();
        $cartTotal = Alma_Installments_Helper_Functions::priceToCents((float)$quote->getGrandTotal());
        $enabledFeePlansInConfig = $this->feePlansHelper->getEnabledFeePlansConfigFromBackOffice();

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
        if (empty($installmentsQuery)) {
            return [];
        }

        try {
            $feePlansEligibilities = $this->alma->payments->eligibility(
                $this->formatEligibilityPayload($quote, $installmentsQuery),
                true
            );
        } catch (RequestError $e) {
            $this->logger->info('$e',[$e->getMessage()]);
            return [];
        }

        $eligibleFeePlans = $this->selectEligibleFeePlans($feePlansEligibilities);
        $this->saveEligibleFeePlans($eligibleFeePlans);
        return $eligibleFeePlans;
    }

    /**
     * @param $eligibleFeePlans
     * @return void
     */
    private function saveEligibleFeePlans($eligibleFeePlans)
    {
        $this->eligibleFeePlans = $eligibleFeePlans;
    }

    /**
     * @param $feePlansEligibilities
     * @return array
     */
    private function selectEligibleFeePlans($feePlansEligibilities){
        $eligibleFeePlans = [];
        foreach ($feePlansEligibilities as $planKey => $feePlansEligibility) {
            /** @var Alma\API\Endpoints\Results\Eligibility $feePlansEligibility */
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
        $eligibilityMessage = $this->config->getEligibilityMessage();
        $nonEligibilityMessage = $this->config->getNonEligibilityMessage();
        $excludedProductsMessage = $this->config->getExcludedProductsMessage();

        if (!$this->alma) {
            $this->eligible = false;
            return false;
        }

        if (!$this->checkItemsTypes()) {
            $this->eligible = false;
            $this->message = $nonEligibilityMessage . '<br>' . $excludedProductsMessage;
            return false;
        }

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::helper('checkout/cart')->getQuote();
        if(!$quote) {
            $this->eligible = false;
            return false;
        }

        $this->message = $eligibilityMessage;
        $cartTotal = Alma_Installments_Helper_Functions::priceToCents((float)$quote->getGrandTotal());

        // Check that the amount is within any merchant-activated fee plan bounds
		$installmentsCounts = array();
		$enabledInstallmentsCounts = $this->config->enabledInstallmentsCounts();

		foreach ($enabledInstallmentsCounts as $n) {
			$min = $this->config->pnxMinAmount($n);
			$max = $this->config->pnxMaxAmount($n);

			if ($cartTotal >= $min && $cartTotal <= $max) {
				$installmentsCounts[] = $n;
			}
		}

		// Check that the in-bound amount is also deemed eligible by our API
		if (!empty($installmentsCounts)) {
			$requestData = Alma_Installments_Model_Data_Quote::dataFromQuote($quote, $installmentsCounts);

			try {
				$this->eligibilities = $this->alma->payments->eligibility($requestData);
			} catch (RequestError $e) {
				$this->logger->error("Error checking payment eligibility: {$e->getMessage()}");
				$this->eligible = false;
				$this->message = $nonEligibilityMessage;
				return false;
			}
		}



        if (empty($installmentsCounts) || (isset($eligibilities) && !$this->hasAnyEligible($eligibilities))) {
            $this->eligible = false;
            $this->message = $nonEligibilityMessage;

            $minAmount = min(array_map(array($this->config, 'pnxMinAmount'), $enabledInstallmentsCounts));
            $maxAmount = max(array_map(array($this->config, 'pnxMaxAmount'), $enabledInstallmentsCounts));

            if ($cartTotal < $minAmount || $cartTotal > $maxAmount) {
                if ($cartTotal > $maxAmount) {
                    $price = $this->getFormattedPrice(Alma_Installments_Helper_Functions::priceFromCents($maxAmount));
                    $this->message .= ' ' . sprintf($this->__('(Maximum amount: %s)'), $price);
                } else {
                    $price = $this->getFormattedPrice(Alma_Installments_Helper_Functions::priceFromCents($minAmount));
                    $this->message .= ' ' . sprintf($this->__('(Minimum amount: %s)'), $price);
                }
            }
        } else {
            $this->eligible = true;
        }

        return $this->eligible;
    }

    private function hasAnyEligible($eligibilities) {
    	foreach ($eligibilities as $eligibility) {
    		if ($eligibility->isEligible) {
    			return true;
			}
		}

    	return false;
	}

    public function isEligible($n = null)
    {
    	if ($n === null) {
			return $this->eligible;
		} else {
			foreach ($this->eligibilities as $eligibility) {
				if ($eligibility->installmentsCount === $n && $eligibility->isEligible) {
					return true;
				}
			}

			return false;
		}
	}

    public function getMessage()
    {
        return $this->message;
    }

    private function getFormattedPrice($price)
    {
        return Mage::helper('core')->currency($price, true, false);
    }

    /**
     * @return bool
     */
    private function checkItemsTypes()
    {
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::helper('checkout/cart')->getQuote();
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

    private function formatEligibilityPayload($quote,$installmentsQuery)
    {
        return [
        'online'          => 'online',
        'purchase_amount' => Alma_Installments_Helper_Functions::priceToCents((float)$quote->getGrandTotal()),
        'locale'          => Mage::app()->getLocale()->getLocaleCode(),
        'queries'         => $installmentsQuery,
         ];
    }

    private function checkEligibilityPrerequisite()
    {
        $isOk = true;
        if (!$this->alma) {
            $this->logger->error('Alma client is not define',[]);
            $isOk = false;
        }
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::helper('checkout/cart')->getQuote();
        if(!$quote) {
            $this->logger->error('Quote is not define ',[]);
            $isOk = false;
        }

        if(!$this->checkItemsTypes()){
            $isOk = false ;
        }
        return $isOk;
    }
}
