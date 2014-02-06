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

class Fooman_Common_Model_Selftester_Db extends Mage_Core_Model_Abstract
{
    public $messages = array();
    public $errorOccurred = false;
    protected $_dbOkay = true;


    /**
     * check the database for expected tables, columns and attributes
     *
     * @param Fooman_Common_Model_Selftester $selftester
     *
     * @return bool
     */
    public function dbCheck (Fooman_Common_Model_Selftester $selftester)
    {
        $localError = false;
        $selftester->messages[] = "Checking database";
        $installer = new Mage_Sales_Model_Mysql4_Setup('sales_setup');
        $installer->startSetup();

        foreach ($selftester->_getDbFields() as $field) {
            switch ($field[0]) {
                case 'eav':
                    $localError = $this->_dbCheckEav($selftester, $field, $installer, $localError);
                    break;
                case 'sql-column':
                    $localError = $this->_dbCheckSqlColumn($selftester, $field, $installer, $localError);
                    break;
            }
        }
        $installer->endSetup();
        if (empty($localError)) {
            return true;
        } else {
            if ($this->_dbOkay == false) {
                $selftester->messages[]
                    = "<p>The selftest has found some problems with your database install.
                    You can attempt to fix this by clicking this <a href=\""
                    . htmlentities(Mage::helper('core/http')->getServer('PHP_SELF', ''))
                    . "?fix=true\">link</a>.</p><p style=\"color:red;\"><em>A DATABASE BACKUP IS strongly
                    RECOMMENDED BEFORE ATTEMPTING THIS!</em></p>";
            }
            return false;
        }
    }

    /**
     * check the DB for an expected EAV attribute
     *
     * @param Fooman_Common_Model_Selftester $selftester
     * @param                                $field
     * @param                                $installer
     * @param                                $localError
     *
     * @return bool
     */
    protected function _dbCheckEav(Fooman_Common_Model_Selftester $selftester, $field, $installer, $localError)
    {
        try {
            $attribute = Mage::getModel('eav/entity_attribute')->loadByCode($field[1], $field[2]);
            if (!$attribute->getId() > 0) {
                $localError = true;
                throw new Exception('eav attribute ' . $field[2] . ' is not installed');
            }
            $selftester->messages[] = "[OK] eav attribute " . $field[2]." with id ".$attribute->getId()."";
        } catch (Exception $e) {
            if ($selftester->shouldFix()) {
                $selftester->messages[] = "Attempting fix for eav attribute " . $field[2]."";
                try {
                    $installer->addAttribute($field[1], $field[2], $field[3]);
                    $selftester->messages[] = "[FIX OK] eav attribute " . $field[2]." fixed";
                } catch (Exception $e) {
                    $selftester->messages[] = "[FAILED] fixing eav attribute " . $field[2]."";
                    $this->_dbOkay = false;
                    $selftester->messages[] = $e->getMessage();
                    $localError = true;
                }
            } else {
                $selftester->messages[] = "[FAILED] eav attribute " . $field[2] . "";
                $this->_dbOkay = false;
                $selftester->messages[] = "[ERR] ".$e->getMessage();
                $localError = true;
            }
        }
        return $localError;
    }


    /**
     * check the DB for an expected table column
     *
     * @param Fooman_Common_Model_Selftester $selftester
     * @param                                $field
     * @param                                $installer
     * @param                                $localError
     *
     * @return bool
     */
    protected function _dbCheckSqlColumn(Fooman_Common_Model_Selftester $selftester, $field, $installer, $localError)
    {
        try {
            if (!$installer->getConnection()->tableColumnExists($field[1], $field[2])) {
                throw new Exception(sprintf('Did not find column %s in table %s'), $field[2], $field[1]);
            }
            $selftester->messages[] = "[OK] column " . $field[2]."";
        } catch (Exception $e) {
            if ($selftester->shouldFix()) {
                $selftester->messages[] = "Attempting fix for column " . $field[2]."";
                try {
                    $installer->getConnection()->addColumn(
                        $installer->getTable($field[1]),
                        $field[2],
                        $field[3]
                    );
                    $selftester->messages[] = "[FIX OK] column " . $field[2]." fixed";
                } catch (Exception $e) {
                    $selftester->messages[] = "[FAILED] fixing column " . $field[2]."";
                    $this->_dbOkay = false;
                    $selftester->messages[] = $e->getMessage();
                    $localError = true;
                }
            } else {
                $selftester->messages[] = "[FAILED] column " . $field[2]."";
                $this->_dbOkay = false;
                $selftester->messages[] = "[ERR] ".$e->getMessage();
                $localError = true;
            }
        }
        return $localError;

    }

}
