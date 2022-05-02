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

class Alma_Installments_Block_Badge extends Mage_Core_Block_Template
{
    /** @var Alma_Installments_Helper_Config */
    private $config;
    /**@var Alma_Installments_Helper_FeePlansHelper */
    private $feePlansHelper;

    private $isWidget = false;

    /**
     * @param array $args
     */
    public function __construct(array $args = array())
    {
        parent::__construct($args);
        $this->logger = Mage::helper('alma/logger')->getLogger();
        $this->feePlansHelper = Mage::helper('alma/feePlansHelper');
        $this->config = Mage::helper('alma/config');
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        // We know that we're rendering as a widget when no template file is set yet
        if (empty($this->_template)) {
            $this->_template = "alma/product/badge.phtml";
            $this->isWidget = true;
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
    function getProduct()
    {
        return Mage::registry('product');
    }

    /**
     * @return string
     */
    public function getEnableFeePlansForBadge()
    {
        $plansForBadge = $this->feePlansHelper->getEnabledFeePlansConfigFromBackOffice();
        $this->logger->info('$plansForBadge',[$plansForBadge]);
        foreach ($plansForBadge as $panKey => $plan) {
            $plansForBadge[$panKey] = $this->formatPlanForBadge($plan);
        }
        return json_encode(array_values($plansForBadge));

    }

    /**
     * @param $plan
     * @return array
     */
    public function formatPlanForBadge($plan)
    {
       return [
           'installmentsCount'=> $plan['installments_count'],
          'deferredDays'=> $plan['deferred_days'],
          'deferredMonths'=> $plan['deferred_months'],
          'minAmount'=> $plan['custom_min_purchase_amount'],
          'maxAmount'=> $plan['custom_max_purchase_amount'],
       ];
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        $locale ='en';
        $localeStoreCode = Mage::app()->getLocale()->getLocaleCode();

        if (preg_match('/^([a-z]{2})_([A-Z]{2})$/',$localeStoreCode,$matches)){
            $locale = $matches[1];
        }
        return $locale;
    }

    /**
     * @return bool
     */
    public function widgetIsEnableOnProductPage()
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
