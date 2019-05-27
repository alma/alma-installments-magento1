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


use Alma\API\Entities\Instalment;
use Alma\API\Entities\Payment;


class AlmaPaymentValidationError extends \Exception {
    /**
     * @var string
     */
    private $returnPath;

    public function __construct($message = "", $returnPath = "checkout/onepage/failure", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->returnPath = $returnPath;
    }

    public function getReturnPath() {
        return $this->returnPath;
    }
}


class Alma_Installments_PaymentController extends Mage_Core_Controller_Front_Action
{
    private function cancelOrder($order, $error) {
        $order->registerCancellation($error)->save();
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    private function getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @return string
     * @throws AlmaPaymentValidationError
     */
    private function validatePayment()
    {
        /** @var AlmaLogger $logger */
        $logger = Mage::helper('alma/logger')->getLogger();

        $errorMessage = $this->__('There was an error when validating your payment. Please try again or contact us if the problem persists.');

        /** @var \Alma\API\Client $alma */
        $alma = Mage::helper('alma/AlmaClient')->getDefaultClient();
        $pid = $this->getRequest()->getParam('pid');

        try {
            $almaPayment = $alma->payments->fetch($pid);
        } catch (\Alma\API\RequestError $e) {
            $logger->error("Error fetching payment information ({$pid}) from Alma: {$e->getMessage()}");
            throw new AlmaPaymentValidationError($errorMessage, 'checkout/cart');
        }

        $order = Mage::getModel('sales/order')->load($almaPayment->custom_data["order_id"]);
        $payment = $order->getPayment();

        $quote = Mage::getModel('sales/quote')->load($almaPayment->custom_data["quote_id"]);
        $quoteId = $quote->getId();

        $this->getSession()->setLastQuoteId($quoteId);
        $this->getSession()->setLastOrderId($order->getId())->setLastRealOrderId($order->getIncrementId());

        if (in_array($order->getState(), array(Mage_Sales_Model_Order::STATE_NEW, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT))) {
            if (Alma_Installments_Helper_Functions::priceToCents($payment->getAmountAuthorized()) !== $almaPayment->purchase_amount) {
                $internalError = $this->__(
                    "Paid amount (%1) does not match due amount (%2) for order %3",
                    Functions::priceFromCents($almaPayment->purchase_amount),
                    $payment->getAmountAuthorized(),
                    $order->getIncrementId()
                );

                $logger->error($internalError);
                $this->cancelOrder($order, $internalError);

                try {
                    $alma->payments->flagAsPotentialFraud($almaPayment->id, $internalError);
                } catch (\Alma\API\RequestError $e) {
                }

                throw new AlmaPaymentValidationError($errorMessage, 'checkout/onepage/failure/');
            }

            $firstInstalment = $almaPayment->payment_plan[0];
            if (!in_array($almaPayment->state, array(Payment::STATE_IN_PROGRESS, Payment::STATE_PAID)) || $firstInstalment->state !== Instalment::STATE_PAID) {
                $internalError = $this->__(
                    "Payment state incorrect (%s & %s) for order %s",
                    $almaPayment->state,
                    $firstInstalment->state,
                    $order->getIncrementId()
                );

                $logger->error($internalError);
                $this->cancelOrder($order, $internalError);

                try {
                    $alma->payments->flagAsPotentialFraud($almaPayment->id, $internalError);
                } catch (\Alma\API\RequestError $e) {
                }

                throw new AlmaPaymentValidationError($errorMessage, 'checkout/onepage/failure/');
            }

            // Register successful capture to update order state and generate invoice
            $historyItem = $order->addStatusHistoryComment($this->__('First instalment captured successfully'));
            $order->setCanSendNewEmailFlag(true);
            $order->save();

            // notify customer
            $order->sendNewOrderEmail();

            if ($historyItem) {
                $historyItem->setIsCustomerNotified(1);
                $historyItem->save();
            }

            $payment->registerCaptureNotification($payment->getBaseAmountAuthorized());
            $payment->unsAdditionalInformation('payment_url');
            $payment->save();

            $this->getSession()->setLastSuccessQuoteId($quoteId);
            $order->save();

            return 'checkout/onepage/success';

        } elseif ($order->getState() == Mage_Sales_Model_Order::STATE_CANCELED) {
            throw new AlmaPaymentValidationError($this->__('Your order has been canceled'), 'checkout/onepage/failure/');

        } elseif (in_array($order->getState(), array(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_COMPLETE, Mage_Sales_Model_Order::STATE_HOLDED, Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW))) {
            $this->getSession()->setLastSuccessQuoteId($quoteId);
            $order->save();

            return 'checkout/onepage/success';
        }

        throw new AlmaPaymentValidationError(null, 'checkout/cart');
    }

    public function returnAction()
    {
        try {
            $redirect_to = $this->validatePayment();
        } catch (AlmaPaymentValidationError $e) {
            $this->getSession()->addError($e->getMessage());
            $redirect_to = $e->getReturnPath();
        }

        return $this->_redirect($redirect_to, array('_secure' => true));
    }

    public function ipnAction()
    {
        $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
        $body = Mage::helper('core')->jsonEncode(array('success' => true));

        try {
            $this->validatePayment();
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            $body = Mage::helper('core')->jsonEncode(array('error' => $e->getMessage()));
        }

        $this->getResponse()->setBody($body);
    }

    public function cancelAction()
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $this->getSession()->getLastRealOrder();
        if ($order) {
            $this->cancelOrder($order, $this->__("Order canceled by customer"));

            /** @var Mage_Sales_Model_Quote $quote */
            $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $this->getSession()->replaceQuote($quote);
            }
        }

        return $this->_redirect('checkout/cart', array('_secure' => true));
    }
}
