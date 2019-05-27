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
    /** @var Alma_Installments_Helper_Config */
    private $config;
    /** @var \Alma\API\Client  */
    private $alma;
    /** @var AlmaLogger */
    private $logger;

    /** @var bool */
    private $eligible;
    /** @var string */
    private $message;

    public function __construct()
    {
        $this->logger = Mage::helper('alma/logger')->getLogger();
        $this->config = Mage::helper('alma/config');
        $this->alma = Mage::helper('alma/AlmaClient')->getDefaultClient();
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

        try {
            $eligibility = $this->alma->payments->eligibility(Alma_Installments_Model_Data_Quote::dataFromQuote($quote));
        } catch (RequestError $e) {
            $this->logger->error("Error checking payment eligibility: {$e->getMessage()}");
            $this->eligible = false;
            $this->message = $nonEligibilityMessage;
            return false;
        }

        if (!$eligibility->isEligible) {
            $this->eligible = false;
            $this->message = $nonEligibilityMessage;

            $minAmount = $eligibility->constraints["purchase_amount"]["minimum"];
            $maxAmount = $eligibility->constraints["purchase_amount"]["maximum"];

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

    public function isEligible()
    {
        return $this->eligible;
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
                return false;
            }
        }

        return true;
    }
}
