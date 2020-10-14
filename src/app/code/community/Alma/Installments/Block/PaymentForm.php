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


class Alma_Installments_Block_PaymentForm extends Mage_Payment_Block_Form
{
    /**
     * @var Alma_Installments_Helper_Config
     */
    private $config;
	private $eligibilityHelper;

	protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('alma/payment_form.phtml');

        $this->config = Mage::helper('alma/config');
        $this->eligibilityHelper = Mage::helper('alma/eligibility');
    }

    public function availableInstallmentsCounts()
    {
    	$availableInstallmentsCounts = array();
    	foreach ($this->config->enabledInstallmentsCounts() as $n) {
			if ($this->eligibilityHelper->isEligible($n)) {
				$availableInstallmentsCounts[] = $n;
			}
		}

    	return $availableInstallmentsCounts;
    }

	public function defaultInstallmentsCount()
	{
		$availableInstallmentsCounts = $this->availableInstallmentsCounts();

		if (in_array(3, $availableInstallmentsCounts)) {
			return 3;
		} else {
			$n = $availableInstallmentsCounts[count($availableInstallmentsCounts) - 1];
			return $n;
		}
    }
}
