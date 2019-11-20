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

class Alma_Installments_Helper_Product extends Mage_Core_Helper_Abstract
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
    /** @var string */
    private $plan = '';
    /** @var array */
    private $listing = [
        1 => 'First',
        2 => 'Second',
        3 => 'Third',
        4 => 'Fourth',
    ];

    public function __construct()
    {
        $this->logger = Mage::helper('alma/logger')->getLogger();
        $this->config = Mage::helper('alma/config');
        $this->alma = Mage::helper('alma/AlmaClient')->getDefaultClient();
    }

    /**
     * @return bool
     */
    public function checkEligibility(Mage_Catalog_Model_Product $product) {
        $eligibilityMessage = $this->config->getProductEligibilityMessage();

        if (!$this->alma) {
            $this->eligible = false;
            return false;
        }

        if(empty($product->getId())) {
            $this->eligible = false;
            return false;
        }

        $this->message = $eligibilityMessage;

        try {
            $eligibility = $this->alma->payments->eligibility(Alma_Installments_Model_Data_Product::dataFromProduct($product));
        } catch (RequestError $e) {
            $this->logger->error("Error checking payment eligibility: {$e->getMessage()}");
            $this->eligible = false;
            $this->message = $nonEligibilityMessage;
            return false;
        }
        if (!$eligibility->isEligible) {
            $this->eligible = false;
            $this->message = $nonEligibilityMessage;

            $minAmount = $eligibility->constraints['purchase_amount']['minimum'];
            $maxAmount = $eligibility->constraints['purchase_amount']['maximum'];

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
            $plans = $nplans = [];
            if ($eligibility->paymentPlan) {
                $n = count($eligibility->paymentPlan);
                if (!$this->config->isPnXEnabled($n)) {
                    continue;
                }
                $plans[$n] = '';
                $nplans[] = 'x' . $n;
                $i = 1;
                foreach ($eligibility->paymentPlan as $month) {
                    //foreach ($month as $plan) {
                        $dtm = new DateTime(strtotime($month['due_date']));
                        $plans[$n][] = $this->__($this->listing[$i] . ' payment') . ': ' . Alma_Installments_Helper_Functions::priceFromCents($month['purchase_amount']) . '€';
                    //}
                    $i++;
                }
                $this->plan = implode(', ', $plans[$n]);
                $ns = implode(',', $nplans);
                $this->message = sprintf($this->message, $ns);
            }
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

    public function getPlan()
    {
        return $this->plan;
    }

    private function getFormattedPrice($price)
    {
        return Mage::helper('core')->currency($price, true, false);
    }
}
