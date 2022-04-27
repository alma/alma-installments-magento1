<?php



class Alma_Installments_Model_System_Config_Backend_PnxStringRender  extends Mage_Core_Block_Abstract
{
    /**
     * Render output
     *
     * @return string
     */
    protected function _toHtml()
    {
        $html = '<div style="'.$this->getColumn()['style'].'">';
        $html .= '#{' . $this->getColumnName() .'}';
        $html .= '</div>';
        return  $html;
    }
}