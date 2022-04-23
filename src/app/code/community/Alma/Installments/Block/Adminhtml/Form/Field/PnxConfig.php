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

class Alma_Installments_Block_Adminhtml_Form_Field_PnxConfig extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $isAllowRenderer;

    public function __construct()
    {
        $this->setTemplate('system/config/form/field/array.phtml');
    }

    protected function _prepareToRender()
    {
        $this->addColumn('pnx_label', array(
            'label' => Mage::helper('alma')->__('Payment plan'),
            'style' => 'width:175px;font-weight:bold;padding:0 0 15px 0',
            'renderer' => new Alma_Installments_Model_System_Config_Backend_PnxStringRender(),
        ));
        $this->addColumn('enable', array(
            'label' => Mage::helper('alma')->__('enable'),
            'renderer' => new Alma_Installments_Model_System_Config_Backend_PnxIsEnable(),
        ));
        $this->addColumn('min_purchase_amount', array(
            'label' => Mage::helper('alma')->__('Min purchase amount'),
            'style' => 'width:50px',
            'renderer' => new Alma_Installments_Model_System_Config_Backend_PnxStringRender(),
        ));
        $this->addColumn('custom_min_purchase_amount', array(
            'label' => Mage::helper('alma')->__('Min display amount'),
            'style' => 'width:50px',
        ));
        $this->addColumn('custom_max_purchase_amount', array(
            'label' => Mage::helper('alma')->__('Max display amount'),
            'style' => 'width:50px',
        ));
        $this->addColumn('max_purchase_amount', array(
            'label' => Mage::helper('alma')->__('Max purchase amount'),
            'style' => 'width:50px',
            'renderer' => new Alma_Installments_Model_System_Config_Backend_PnxStringRender(),
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('alma')->__('Add');
    }
}
