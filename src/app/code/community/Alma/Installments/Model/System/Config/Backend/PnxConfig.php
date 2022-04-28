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

class Alma_Installments_Model_System_Config_Backend_PnxConfig extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array
{
    /**
     * @var Mage_Core_Helper_Abstract|null
     */
    private $feePlansHelper;
    public function __construct()
    {
        $this->feePlansHelper = Mage::helper('alma/FeePlansHelper');
        parent::__construct();
    }

    /**
     * @description Set mixed value from bd and feePlans API on Backend Serialized Array
     * @override
     * @return void
     */
    protected function _afterLoad()
    {
        if (!is_array($this->getValue())) {
            $serializedFeePlans = $this->getValue();
            $UnserializedFormFeePlans = false;
            if (!empty($serializedFeePlans)) {
                try {
                    $UnserializedFormFeePlans = Mage::helper('core/unserializeArray')->unserialize($serializedFeePlans);
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

            $almaApiFeePlans = $this->feePlansHelper->getFormattedFeePlansFromAlmaApi();
            $mergedConfigAndFormFeePlans = $this->feePlansHelper->mergeConfigAndFormFeePlan($almaApiFeePlans,$UnserializedFormFeePlans);
            $feePlansWithPriceForDisplay = $this->feePlansHelper->convertFeePlansPricesForDisplay($mergedConfigAndFormFeePlans);

            $this->setValue($feePlansWithPriceForDisplay);
        }
    }
    /**
     * Check object existence in incoming data and unset array element with '__empty' key
     *
     * @override
     * @throws Mage_Core_Exception
     * @return void
     */
    protected function _beforeSave()
    {
        try {
            Mage::helper('core/unserializeArray')->unserialize(serialize($this->getValue()));
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('adminhtml')->__('Serialized data is incorrect'));
        }
        $formFeePlans = $this->getValue();
        $configFeePlans = $this->feePlansHelper->getBaseFeePlansFromConfig();
        $mergedConfigAndFormFeePlans = $this->feePlansHelper->mergeConfigAndFormFeePlan($configFeePlans,$formFeePlans);
        $feePlansInCentForSave = $this->feePlansHelper->convertFeePlansPricesForSave($mergedConfigAndFormFeePlans);
        $feePlansInCentForSave = $this->feePlansHelper->validateFeePlanMinAndMaxCustomAmount($feePlansInCentForSave);
        $this->setValue($feePlansInCentForSave);
        parent::_beforeSave();
    }

}
