<?php

/**
 * @author     Kristof Ringleff
 * @package    Fooman_Common
 * @copyright  Copyright (c) 2009 Fooman Limited (http://www.fooman.co.nz)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Fooman_Common_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Helper function to create a hash from a base64 Fooman serial number
     *
     * @param $serial
     *
     * @return string
     */
    public function convertSerialToId($serial)
    {
        return hash('sha256', str_replace(array("\r\n", "\n", "\r", " ", PHP_EOL), '', $serial));
    }

    /**
     * return css file path to include for overlay window css - version dependent
     *
     * @return string
     */
    public function getOverlayFileName()
    {
        if (file_exists($this->_getNewOverlayFileLocation())) {
            return 'lib/prototype/windows/themes/magento.css';
        } else {
            return 'prototype/windows/themes/magento.css';
        }
    }

    /**
     * return css file type to include for overlay window css - version dependent
     *
     * @return string
     */
    public function getOverlayFileType()
    {
        if (file_exists($this->_getNewOverlayFileLocation())) {
            return 'skin_css';
        } else {
            return 'js_css';
        }
    }

    /**
     * path of new magento.css file
     *
     * @return string
     */
    protected function _getNewOverlayFileLocation()
    {
        return BP . DS . 'skin' . DS . 'adminhtml' . DS . 'default' . DS . 'default'
        . DS . 'lib' . DS . 'prototype' . DS . 'windows' . DS . 'themes' . DS . 'magento.css';
    }
}