<?php
/**
 * Fooman Common
 *
 * @package   Fooman_Common
 * @author    Kristof Ringleff <kristof@fooman.co.nz>
 * @copyright Copyright (c) 2012 Fooman Limited (http://www.fooman.co.nz)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Fooman_Common_Block_Adminhtml_Extensioninfo extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_hasSelftest = false;
    protected $_idString = '';
    protected $_moduleName = '';

    /**
     * return the selftest button if a selftest is supported for this extension
     *
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setTemplate('fooman/common/selftester.phtml');
        $this->setShowSelftestButton(false);
        if ($this->_hasSelftest) {
            if (Mage::getModel($this->_idString . '/selftester')) {
                $this->setShowSelftestButton(true);
                $this->setSelftestButtonUrl(
                    Mage::helper('adminhtml')->getUrl(
                        'adminhtml/selftester',
                        array(
                             'module'     => $this->_idString,
                             'moduleName' => $this->_moduleName
                        )
                    )
                );
                $element->setReadonly(true, true);
            }
        }
        $this->setConfigVersion((string)Mage::getConfig()->getModuleConfig($this->_moduleName)->version);

        return $this->_toHtml();
    }
}