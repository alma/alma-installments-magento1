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

class Alma_Installments_Block_CartWidget extends Mage_Core_Block_Template
{

    /** @var Alma_Installments_Helper_Config */
    private $config;
    /** @var Mage_Checkout_Helper_Cart */
    private  $quoteHelper;
    /** @var Alma_Installments_Helper_FeePlansHelper */
    private $feePlansHelper;
    /** @var Mage_Sales_Model_Quote */
    private $quote;


    public function __construct(array $args = array())
    {
        parent::__construct($args);

        $this->feePlansHelper = Mage::helper('alma/feePlansHelper');
        $this->quoteHelper = Mage::helper('checkout/cart');
        $this->quote = $this->quoteHelper->getQuote();
        $this->config = Mage::helper('alma/config');
    }

    protected function _toHtml()
    {
        // We know that we're rendering as a widget when no template file is set yet
        if (empty($this->_template)) {
            $this->_template = "alma/cart/eligibility.phtml";
        }

        return parent::_toHtml();
    }

    /**
     * @return bool
     */
    public function widgetIsEnable()
    {
        return $this->config->showEligibilityMessage();
    }

    /**
     * @return float
     */
    public function getFinalPrice(){
        return Alma_Installments_Helper_Functions::priceToCents($this->quote->getGrandTotal());
    }

    /**
     * @return string
     */
    public function getActiveMode()
    {
        return $this->config->getActiveMode();
    }

    /**
     * @return array
     */
    public function getEnableFeePlansForBadge()
    {
        return $this->feePlansHelper->getEnableFeePlansForBadge();
    }
    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->config->getMerchantId();
    }
    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->config->getLocale();
    }

}
