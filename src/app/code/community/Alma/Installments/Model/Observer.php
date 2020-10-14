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

class Alma_Installments_Model_Observer extends Varien_Event_Observer
{
	/**
	 * @param Varien_Event_Observer $observer
	 * @throws Exception
	 */
	public function handleConfigChange($observer)
	{
		/** @var Alma_Installments_Helper_Config $config */
		$config = Mage::helper('alma/config');
		/** @var Alma_Installments_Helper_Availability $availability */
		$availability = Mage::helper('alma/availability');
		/** @var Mage_Core_Helper_String $h */
		$h = Mage::helper('core/string');

		if (empty($config->getActiveAPIKey())) {
			throw new Exception($h->__('API key is required:') . ' ' . $config->getActiveMode());
		}

		if ($availability->canConnectToAlma($config->getActiveMode())) {
			// Everything is OK if API key provided for selected API mode works
			Mage::getConfig()->saveConfig(Alma_Installments_Helper_Config::CONFIG_FULLY_CONFIGURED, 1);
		} else {
			// Otherwise, "deactivate" module...
			Mage::getConfig()->saveConfig(Alma_Installments_Helper_Config::CONFIG_FULLY_CONFIGURED, 0);

			// ... and reset API key for selected API mode
			$keyConfigPath = Alma_Installments_Helper_Config::CONFIG_TEST_API_KEY;
			if ($config->getActiveMode() === 'live') {
				$keyConfigPath = Alma_Installments_Helper_Config::CONFIG_LIVE_API_KEY;
			}
			Mage::getConfig()->saveConfig($keyConfigPath, '');

			throw new Exception($h->__($config->getActiveMode() . ' API key is invalid. Please double-check and retry.'));
		}
    }

	/**
	 * Add Alma payment information to order's payment info block when viewing an order paid for with Alma
	 *
	 * @param Varien_Event_Observer $observer
	 * @throws Mage_Core_Exception
	 */
	public function preparePaymentInfo($observer)
	{
		/** @var Varien_Object $transport */
		$transport = $observer->getData('transport');
		/** @var Mage_Payment_Model_Info $payment */
		$payment = $observer->getData('payment');

		/** @var Mage_Core_Helper_String $h */
		$h = Mage::helper('core/string');

		if ($payment->getMethodInstance()->getCode() == Alma_Installments_Model_PaymentMethod::CODE) {
			$transport->setData(
				$h->__('Payment ID'),
				$payment->getAdditionalInformation(Alma_Installments_Model_PaymentMethod::PAYMENT_INFO_ID)
			);

			$transport->setData(
				$h->__('Installments count'),
				$payment->getAdditionalInformation(
					Alma_Installments_Model_PaymentMethod::PAYMENT_INFO_INSTALLMENTS_COUNT
				)
			);
		}
	}
}
