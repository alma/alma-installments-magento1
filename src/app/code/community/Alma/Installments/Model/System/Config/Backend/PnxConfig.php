<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_CatalogInventory
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml catalog inventory "Minimum Qty Allowed in Shopping Cart" field
 *
 * @category   Mage
 * @package    Mage_CatalogInventory
 * @author     Magento Core Team <core@magentocommerce.com>
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


    public function _afterLoad()
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
        $configFeePlans = $this->feePlansHelper->getFeePlansFromConfig();
        $mergedConfigAndFormFeePlans = $this->feePlansHelper->mergeConfigAndFormFeePlan($configFeePlans,$formFeePlans);
        $feePlansInCentForSave = $this->feePlansHelper->convertFeePlansPricesForSave($mergedConfigAndFormFeePlans);
        $feePlansInCentForSave = $this->feePlansHelper->validateFeePlanMinAndMaxCustomAmount($feePlansInCentForSave);
        $this->setValue($feePlansInCentForSave);
        parent::_beforeSave();
    }

}
