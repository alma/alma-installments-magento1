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

class Alma_Installments_Block_Widget_Product extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface
{
    /** @var Alma_Installments_Helper_Eligibility  */
    private $_productHelper;
    /** @var Alma_Installments_Helper_Availability  */
    private $_availabilityHelper;
    /** @var Alma_Installments_Helper_Config */
    private $_config;

    private $_product;

    public function __construct(array $args = array())
    {
        parent::__construct($args);
        $this->_productHelper = Mage::helper('alma/product');
        $this->_availabilityHelper = Mage::helper('alma/availability');
        $this->_config = Mage::helper('alma/config');
        $this->_product = Mage::registry('product');
        $this->checkEligibility();
    }

    protected function _toHtml()
    {
        // We know that we're rendering as a widget when no template file is set yet
        if (empty($this->_template)) {
            $this->_template = 'alma/product/eligibility.phtml';
            $this->isWidget = true;
        }
        return parent::_toHtml();
    }

    public function checkEligibility()
    {
        $this->_productHelper->checkEligibility($this->_product);
    }

    public function isEligible()
    {
        return $this->_productHelper->isEligible();
    }

    public function showEligibilityMessage()
    {
        return $this->shouldDisplay() && $this->_config->showProductEligibilityMessage();
    }

    public function showProductPlan()
    {
        return $this->shouldDisplay() && $this->_config->showProductPlan();
    }

    public function getEligibilityMessage()
    {
        return $this->_productHelper->getMessage();
    }

    public function shouldDisplay() {
        return $this->_availabilityHelper->isAvailable();
    }

    public function getPlan() {
        return $this->_productHelper->getPlan();
    }
}
