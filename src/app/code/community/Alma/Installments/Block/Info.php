<?php
/**
 * Base payment iformation block
 *
 */
class Alma_Installments_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * Payment rendered specific information
     *
     * @var Varien_Object
     */

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('alma/checkout/info/default.phtml');
    }
}
