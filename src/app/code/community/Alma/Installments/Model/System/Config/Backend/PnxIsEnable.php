<?php



class Alma_Installments_Model_System_Config_Backend_PnxIsEnable  extends Mage_Core_Block_Html_Select
{
    private $columnName;

    /**
     * Render output
     *
     * @return string
     */
    protected function _toHtml()
    {
        $html = '<select name='.$this->getName().' style="width:55px">';
        foreach (Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray() as $option) {
            $html.=$this->_optionToHtml($option);
        }
        $html.='</select>';

        return  $html;
    }
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}