<?php
/**
 * 2024 Alma / Nabla SAS
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
 * @copyright 2024 Alma / Nabla SAS
 * @license   https://opensource.org/licenses/MIT The MIT License
 *
 */

class Alma_Installments_Helper_Payment extends Mage_Core_Helper_Abstract
{
    const HEADER_SIGNATURE_KEY = 'X-Alma-Signature';

    /**
     * @var AlmaLogger
     */
    private $logger;

    public function __construct()
    {
        $this->logger = Mage::helper('alma/Logger')->getLogger();
    }

    /**
     * @return string
     * @throws Alma_installments_Model_Exceptions_PaymentException
     */
    private function getHeaderSignature()
    {
        $signature = null;

        try {
            $signature = $this->_getRequest()->getHeader(self::HEADER_SIGNATURE_KEY);
        } catch (Zend_Controller_Request_Exception $e) {
            $this->logger->error('Error retrieving header signature: ', [$e->getMessage()]);
        } finally {
            if (!$signature) {
                throw new Alma_installments_Model_Exceptions_PaymentException('Header signature not found');
            };
            return $signature;
        }
    }

    /**
     * @return void
     * @throws Alma_installments_Model_Exceptions_PaymentException
     */
    public function checkSignature($almaPaymentId)
    {
        if (!$this->isHmacValidated($almaPaymentId, $this->getApiKey(), $this->getHeaderSignature())) {
            throw new Alma_installments_Model_Exceptions_PaymentException('Invalid signature');
        }
    }

    private function isHmacValidated($almaPaymentId, $apiKey, $signature)
    {
        return is_string($almaPaymentId) &&
            is_string($apiKey) &&
            hash_hmac('sha256', $almaPaymentId, $apiKey) === $signature;
    }

    /**
     * @return string
     * @throws Alma_installments_Model_Exceptions_PaymentException
     */
    private function getApiKey()
    {
        /** @var Alma_Installments_Helper_Config $configHelper */
        $configHelper = Mage::helper('alma/Config');
        $apiKey = $configHelper->getActiveAPIKey();
        if (!$apiKey) {
            throw new Alma_installments_Model_Exceptions_PaymentException('API key not found');
        }
        return $apiKey;
    }


}
