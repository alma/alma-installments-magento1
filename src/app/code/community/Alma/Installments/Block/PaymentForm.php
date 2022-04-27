<?php
/**
 * 2018-2019 Alma SAS
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
 * @author    Alma SAS <contact@getalma.eu>
 * @copyright 2018-2019 Alma SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 */


class Alma_Installments_Block_PaymentForm extends Mage_Payment_Block_Form
{
    /**
     * @var Alma_Installments_Helper_Eligibility
     */
    private $eligibilityHelper;
    /**
     * @var Alma_Installments_Helper_Logger
     */
    private $logger;
    /**
     * @var Alma_Installments_Helper_Functions
     */
    private $functionsHelper;

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('alma/payment_form.phtml');
        $this->logger = Mage::helper('alma/logger')->getLogger();
        $this->eligibilityHelper = Mage::helper('alma/eligibility');
        $this->functionsHelper = Mage::helper('alma/Functions');
        $this->quote = Mage::helper('checkout/cart')->getQuote();
    }

    /**
     * @return array
     */
    public function getEligibleFeePlans(){
        try {
            $eligibleFeePlans = $this->eligibilityHelper->getEligibleFeePlans();
        } catch (Exception $e) {
            $this->logger->error('Get Alma fee plans eligibility exception : ',[$e->getMessage()]);
        }
        $this->logger->info('$eligibleFeePlans',[$eligibleFeePlans]);
        return $eligibleFeePlans;
    }

    /**
     * @param $ts
     * @return string
     */
    public function tsToLocaleDate($ts)
    {
        return Mage::app()->getLocale()->date($ts)->toString(Zend_Date::DATE_MEDIUM);
    }

    /**
     * @param $cents
     * @return mixed
     */
    public function convertCentToPrice($cents)
    {
        return Mage::helper('core')->currency($this->functionsHelper->priceFromCents($cents), true, false);
    }

    /**
     * @param $fee
     * @return string
     */
    public function getFeeLabel($fee)
    {
        return sprintf(__('Including fees: %s'),Mage::helper('core')->currency($this->functionsHelper->priceFromCents($fee)));
    }

    /**
     * @return string
     */
    public function getCartTotal()
    {
        return Mage::helper('core')->currency($this->quote->getGrandTotal());
    }

    /**
     * @param $annualInterestRate
     * @return string
     */
    public function getAnnualInterestRate($annualInterestRate)
    {
        return $annualInterestRate/100 .'%' ;
    }

    /**
     * @param $creditCost
     * @return string
     */
    public function getCreditCost($creditCost)
    {
        return Mage::helper('core')->currency($this->functionsHelper->priceFromCents($creditCost));
    }

    /**
     * @param $creditCost
     * @return string
     */
    public function getTotalPaid($creditCost)
    {
        return Mage::helper('core')->currency($this->functionsHelper->priceFromCents($creditCost)+$this->quote->getGrandTotal());
    }

}
