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

class Alma_Installments_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    const CODE = 'alma_installments';
    protected $_code = self::CODE;

    protected $_canManageRecurringProfiles  = false;
    protected $_isInitializeNeeded          = true;

    protected $_formBlockType = 'alma/PaymentForm';

    /** @var AlmaLogger */
    private $logger;
    /** @var \Alma\API\Client */
    private $alma;

    public function __construct()
    {
        $this->logger = Mage::helper('alma/logger')->getLogger();
        $this->alma = Mage::helper('alma/AlmaClient')->getDefaultClient();
    }

    public function assignData($data)
    {
        $this->getInfoInstance()->setAdditionalInformation('installments_count', $data->getData('installments_count'));
        return parent::assignData($data);
    }

    public function canUseForCurrency($currencyCode)
    {
        return $currencyCode == 'EUR';
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     * @return Alma_Installments_Model_PaymentMethod
     * @throws Mage_Core_Exception
     */
    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $quote = $order->getQuote();

        $payment->setAmountAuthorized($order->getTotalDue());
        $payment->setBaseAmountAuthorized($order->getBaseTotalDue());

        $order->setCanSendNewEmailFlag(false);
        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'pending_payment', '', false);

        $stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);

        $data = array(
            "payment" => array(
                "return_url" => Mage::getUrl('alma/payment/return'),
                "installments_count" => (int)$payment->getAdditionalInformation('installments_count'),
                "ipn_callback_url" => Mage::getUrl('alma/payment/ipn'),
                "customer_cancel_url" => Mage::getUrl('alma/payment/cancel'),
                "purchase_amount" => Alma_Installments_Helper_Functions::priceToCents((float)$order->getTotalDue()),
                "shipping_address" => Alma_Installments_Model_Data_Address::dataFromAddress($order->getShippingAddress()),
                "billing_address" => Alma_Installments_Model_Data_Address::dataFromAddress($order->getBillingAddress()),
                "custom_data" => array(
                    "order_id" => $order->getId(),
                    "quote_id" => $quote->getId()
                )
            ),
        );

        $customerId = $order->getCustomerId();
        $customer = null;
        if ($customerId) {
            $customer = Mage::getModel('customer/customer')->load($customerId);
        }

        $data["customer"] = Alma_Installments_Model_Data_Customer::dataFromCustomer(
            $customer,
            array($order->getBillingAddress(), $order->getShippingAddress())
        );

        try {
            $almaPayment = $this->alma->payments->create($data);
        } catch (\Alma\API\RequestError $e) {
            $this->logger->error("Error creating payment: {$e->getMessage()}");
            $this->_cancelOrder();
            Mage::throwException(sprintf($this->_getHelper()->__("Error while processing your order: %s"), $e->getMessage()));
        }

        $quotePayment = $quote->getPayment();
        $quotePayment->setAdditionalInformation('payment_url', $almaPayment->url);
        $quotePayment->save();

        return $this;
    }

    public function getOrderPlaceRedirectUrl()
    {
        // Get **quote** payment info and reload it to get the payment url
        $payment = $this->getInfoInstance();
        $payment = Mage::getModel('sales/quote_payment')->load($payment->getId());

        $url = $payment->getAdditionalInformation('payment_url');

        if (!$url) {
            $this->_cancelOrder();
            $this->logger->error("Error redirecting to payment: cannot get payment info instance");
            Mage::throwException($this->_getHelper()->__("There was an error processing your order. Please try again later or contact us if the problem persists"));
        }

        return $url;
    }

    private function _cancelOrder()
    {
        $quote = $this->getCheckoutSession()->getQuote();
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($quote->getReservedOrderId());
        $order->registerCancellation('Canceled because of technical issue/customer cancellation')->save();
    }

    public function isAvailable($quote = null)
    {
        $isAvailable = parent::isAvailable($quote);

        if ($isAvailable) {
            $available = Mage::helper('alma/availability')->isAvailable();
            $eligible = Mage::helper('alma/eligibility')->checkEligibility();

            $this->toto = 'yay';

            $isAvailable = $available && $eligible;
        }

        return $isAvailable;
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    private function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
