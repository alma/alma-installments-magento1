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

class Alma_Installments_Block_Eligibility extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface
{
    /** @var Alma_Installments_Helper_Eligibility  */
    private $eligibilityHelper;
    /** @var Alma_Installments_Helper_Availability  */
    private $availabilityHelper;
    /** @var Alma_Installments_Helper_Config */
    private $config;

    private $isWidget = false;

    public function __construct(array $args = array())
    {
        parent::__construct($args);

        $this->eligibilityHelper = Mage::helper('alma/eligibility');
        $this->availabilityHelper = Mage::helper('alma/availability');
        $this->config = Mage::helper('alma/config');

        $this->checkEligibility();
    }

    protected function _toHtml()
    {
        // We know that we're rendering as a widget when no template file is set yet
        if (empty($this->_template)) {
            $this->_template = "alma/cart/eligibility.phtml";
            $this->isWidget = true;
        }

        return parent::_toHtml();
    }

    public function checkEligibility()
    {
        $this->eligibilityHelper->checkEligibility();
    }

    public function isEligible()
    {
        return $this->eligibilityHelper->isEligible();
    }

    public function showEligibilityMessage()
    {
        return $this->shouldDisplay() && $this->config->showEligibilityMessage();
    }

    public function getEligibilityMessage()
    {
        return $this->eligibilityHelper->getMessage();
    }

    public function shouldDisplay() {
        return $this->availabilityHelper->isAvailable();
    }

    public function isWidget()
    {
        return $this->isWidget;
    }
}
