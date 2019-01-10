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
    const CONFIG_SHOW_ELIGIBILITY_MESSAGE = 'payment/alma_installments/show_eligibility_message';
    const CONFIG_ELIGIBILITY_MESSAGE = 'payment/alma_installments/eligibility_message';
    const CONFIG_NON_ELIGIBILITY_MESSAGE = 'payment/alma_installments/non_eligibility_message';
    const CONFIG_TITLE = 'payment/alma_installments/title';
    const CONFIG_DESCRIPTION = 'payment/alma_installments/description';
    const CONFIG_EXCLUDED_PRODUCT_TYPES = 'payment/alma_installments/excluded_product_types';

    const CONFIG_FULLY_CONFIGURED = 'payment/alma_installments/fully_configured';

    public function get($field, $default = null, $storeId = null)
    {
        $value = Mage::getStoreConfig($field, $storeId);

        if ($value === null) {
            $value = $default;
        }

        return $value;
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
        return $this->get(self::CONFIG_ELIGIBILITY_MESSAGE);
    }

    public function getNonEligibilityMessage()
    {
        return $this->get(self::CONFIG_NON_ELIGIBILITY_MESSAGE);
    }

    public function showEligibilityMessage()
    {
        return (bool)(int)$this->get(self::CONFIG_SHOW_ELIGIBILITY_MESSAGE);
    }

    public function getPaymentButtonTitle()
    {
        return $this->get(self::CONFIG_TITLE);
    }

    public function getPaymentButtonDescription()
    {
        return $this->get(self::CONFIG_DESCRIPTION);
    }

    public function getExcludedProductTypes()
    {
        return $this->get(self::CONFIG_EXCLUDED_PRODUCT_TYPES);
    }

    public function isFullyConfigured()
    {
        return !$this->needsAPIKeys() && (bool)(int)$this->get(self::CONFIG_FULLY_CONFIGURED, false);
    }
}
