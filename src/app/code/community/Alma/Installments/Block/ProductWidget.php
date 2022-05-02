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

class Alma_Installments_Block_ProductWidget extends Mage_Core_Block_Template
{
    /** @var Alma_Installments_Helper_Config */
    private $config;
    /**@var Alma_Installments_Helper_FeePlansHelper */
    private $feePlansHelper;

    /**
     * @param array $args
     */
    public function __construct(array $args = array())
    {
        parent::__construct($args);
        $this->feePlansHelper = Mage::helper('alma/feePlansHelper');
        $this->config = Mage::helper('alma/config');
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if (empty($this->_template)) {
            $this->_template = "alma/product/badge.phtml";
        }
        return parent::_toHtml();
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
    public function getActiveMode()
    {
        return $this->config->getActiveMode();
    }

    /**
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('product');
    }

    /**
     * @return float
     */
    public function getFinalPrice(){
        return Alma_Installments_Helper_Functions::priceToCents($this->getProduct()->getFinalPrice());
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
    public function getLocale()
    {
        return $this->config->getLocale();
    }

    /**
     * @return bool
     */
    public function widgetIsEnable()
    {
        return $this->config->widgetIsEnableInProductPage();
    }

    /**
     * @return string
     */
    public function getWidgetCustomPosition()
    {
        return $this->config->getWidgetCustomPosition();
    }
}
