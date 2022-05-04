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

class Alma_Installments_Helper_Config extends Mage_Core_Helper_Abstract
{
    const CONFIG_ACTIVE = 'payment/alma_installments/active';
    const CONFIG_SORT_ORDER = 'payment/alma_installments/sort_order';
    const CONFIG_LOGGING = 'payment/alma_installments/logging';
    const CONFIG_LIVE_API_KEY = 'payment/alma_installments/live_api_key';
    const CONFIG_TEST_API_KEY = 'payment/alma_installments/test_api_key';
    const CONFIG_API_MODE = 'payment/alma_installments/api_mode';
    const WIDGET_ENABLE_CART_PAGE = 'payment/alma_installments/enable_widget_cart';
    const CONFIG_ELIGIBILITY_MESSAGE = 'payment/alma_installments/eligibility_message';
    const CONFIG_NON_ELIGIBILITY_MESSAGE = 'payment/alma_installments/non_eligibility_message';
    const CONFIG_TITLE = 'payment/alma_installments/title';
    const CONFIG_DESCRIPTION = 'payment/alma_installments/description';
    const CONFIG_EXCLUDED_PRODUCT_TYPES = 'payment/alma_installments/excluded_product_types';
    const CONFIG_EXCLUDED_PRODUCTS_MESSAGE = 'payment/alma_installments/excluded_products_message';

    const CONFIG_PNX_ENABLED = 'payment/alma_installments/p%dx_enabled';
    const CONFIG_PNX_MIN_AMOUNT = 'payment/alma_installments/p%dx_min_amount';
    const CONFIG_PNX_MAX_AMOUNT = 'payment/alma_installments/p%dx_max_amount';

    const CONFIG_FULLY_CONFIGURED = 'payment/alma_installments/fully_configured';
    const CONFIG_MERCHANT_ID = 'payment/alma_installments/merchant_id';

    const WIDGET_ENABLE_PRODUCT_PAGE =  'payment/alma_installments/enable_widget_product';
    const WIDGET_CUSTOM_POSITION =  'payment/alma_installments/custom_widget_position';

    public function get($field, $default = null, $storeId = null)
    {
        $value = Mage::getStoreConfig($field, $storeId);

        if ($value === null) {
            $value = $default;
        }

        return $value;
    }

    public function isActive()
    {
        return (bool)(int)$this->get(self::CONFIG_ACTIVE);
    }

    public function canLog()
    {
        return (bool)(int)$this->get(self::CONFIG_LOGGING, false);
    }

    public function getActiveMode()
    {
        return  $this->get(self::CONFIG_API_MODE, 'test');
    }

    public function getActiveAPIKey() {
        $mode = $this->getActiveMode();

        switch ($mode) {
            case 'live':
                return $this->getLiveKey();
            default:
                return $this->getTestKey();
        }
    }

    public function getLiveKey()
    {
        return $this->get(self::CONFIG_LIVE_API_KEY, '');
    }

    public function getTestKey()
    {
        return $this->get(self::CONFIG_TEST_API_KEY, '');
    }

    public function needsAPIKeys()
    {
        return empty(trim($this->getLiveKey())) || empty(trim($this->getTestKey()));
    }

    public function getEligibilityMessage()
    {
        return $this->__(trim($this->get(self::CONFIG_ELIGIBILITY_MESSAGE)));
    }

    public function getNonEligibilityMessage()
    {
        return  $this->__(trim($this->get(self::CONFIG_NON_ELIGIBILITY_MESSAGE)));
    }

    public function widgetIsEnableInCartPage()
    {
        return (bool)(int)$this->get(self::WIDGET_ENABLE_CART_PAGE);
    }
    public function getExcludedProductTypes()
    {
        return explode(',', $this->get(self::CONFIG_EXCLUDED_PRODUCT_TYPES));
    }

    public function getExcludedProductsMessage()
    {
        return $this->__(trim($this->get(self::CONFIG_EXCLUDED_PRODUCTS_MESSAGE)));
    }

    public function isFullyConfigured()
    {
        return !$this->needsAPIKeys() && (bool)(int)$this->get(self::CONFIG_FULLY_CONFIGURED, false);
    }
    public function getMerchantId()
    {
        return $this->get(self::CONFIG_MERCHANT_ID, '');
    }
    public function widgetIsEnableInProductPage()
    {
        return $this->get(self::WIDGET_ENABLE_PRODUCT_PAGE, false);
    }
    public function getWidgetCustomPosition()
    {
        return $this->get(self::WIDGET_CUSTOM_POSITION, '');
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
    public function showProductPageWidget()
    {
        return ($this->widgetIsEnableInProductPage() && $this->isActive());
    }
    /**
     * @return bool
     */
    public function showCartWidget()
    {
        return ($this->widgetIsEnableInCartPage() && $this->isActive());
    }

}
