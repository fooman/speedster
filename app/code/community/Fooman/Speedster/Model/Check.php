<?php
/**
 * Fooman Speedster
 *
 * @package   Fooman_Speedster
 * @author    Kristof Ringleff <kristof@fooman.co.nz>
 * @copyright Copyright (c) 2013 Fooman Limited (http://www.fooman.co.nz)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Fooman_Speedster_Model_Check extends Mage_Core_Model_Config_Data
{

    /**
     * run selftest before enabling
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        if ($this->getValue()) {
            $selftester = Mage::getModel('speedster/selftester');
            $selftester->main();
            if ($selftester->errorOccurred) {
                $msg = Mage::helper('core')->__(
                    'Selftest failed with: %s',
                    implode('<br/>', $selftester->messages)
                );
                Mage::throwException($msg);
            }
        }
        return parent::_beforeSave();
    }
}