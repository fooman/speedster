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
                case 'table':
                    $localError = $this->_dbCheckSqlTable($selftester, $field, $installer, $localError);
                    break;
                case 'constraint':
                    $localError = $this->_dbCheckConstraint($selftester, $field, $installer, $localError);
                    break;
                case 'row-data':
                    $localError = $this->_dbCheckDbRow($selftester, $field, $installer, $localError);
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
                    . htmlentities(Mage::app()->getRequest()->getServer('PHP_SELF', ''))
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
            if (!$installer->getConnection()->tableColumnExists($installer->getTable($field[1]), $field[2])) {
                throw new Exception(
                    sprintf('Did not find column %s in table %s', $field[2], $installer->getTable($field[1]))
                );
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

    /**
     * check the DB for an expected table
     *
     * @param Fooman_Common_Model_Selftester $selftester
     * @param                                $fields
     * @param                                $installer
     * @param                                $localError
     *
     * @return bool
     */
    protected function _dbCheckSqlTable(Fooman_Common_Model_Selftester $selftester, $fields, $installer, $localError)
    {
        try {
            $tables = $installer->getConnection()->listTables();

            if (!(array_search($installer->getTable($fields[1]), $tables))) {
                throw new Exception(
                    sprintf('Did not find table %s', $installer->getTable($fields[1]))
                );
            }
            $selftester->messages[] = "[OK] Table " . $fields[1]."";
        } catch (Exception $e) {
            if ($selftester->shouldFix()) {
                $selftester->messages[] = "Attempting fix for table " . $fields[1]."";

                $keys = array();
                $columns = array();

                foreach ($fields[2] as $item) {
                    switch ($item[0]) {
                        case 'sql-column':
                            $columns[] = '`'.$item[1].'` '.$item[2];
                            break;
                        case 'key':
                            $keys[] = $item[1] .' (`'.$item[2].'`)';
                            break;
                    }
                }
                $tableDetails = implode(",", array_merge($columns, $keys));
                $sql ="CREATE TABLE `{$installer->getTable( $fields[1])}` ("
                    .$tableDetails.") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
                try {
                    $installer->run($sql);
                    $selftester->messages[] = "[FIX OK] table " . $fields[1]." fixed";
                } catch (Exception $e) {
                    $selftester->messages[] = "[FAILED] fixing table " . $fields[1]."";
                    $this->_dbOkay = false;
                    $selftester->messages[] = $e->getMessage();
                    $localError = true;
                }
            } else {
                $selftester->messages[] = "[FAILED] table " . $fields[1]."";
                $this->_dbOkay = false;
                $selftester->messages[] = "[ERR] ".$e->getMessage();
                $localError = true;
            }
        }
        return $localError;

    }

    /**
     * check the DB for an expected constraint
     *
     * @param Fooman_Common_Model_Selftester $selftester
     * @param                                $fields
     * @param                                $installer
     * @param                                $localError
     *
     * @return bool
     */
    protected function _dbCheckConstraint(Fooman_Common_Model_Selftester $selftester, $fields, $installer, $localError)
    {
        try {
            $constraints = $installer->getConnection()->getKeyList($installer->getTable($fields[2]));
            if (!(isset($constraints[$installer->getTable($fields[1])]))) {
                throw new Exception(
                    sprintf('Did not find constraint %s', $installer->getTable($fields[1]))
                );
            }
            $selftester->messages[] = "[OK] Constraint " . $fields[1]."";
        } catch (Exception $e) {
            if ($selftester->shouldFix()) {
                $selftester->messages[] = "Attempting fix for constraint " . $fields[1]."";
                try {
                    $installer->getConnection()->addConstraint(
                        $fields[1],
                        $installer->getTable($fields[2]), $fields[3],
                        $installer->getTable($fields[4]), $fields[5],
                        $fields[6], $fields[7], $fields[8]
                    );
                    $selftester->messages[] = "[FIX OK] constraint " . $fields[1]." fixed";
                } catch (Exception $e) {
                    $selftester->messages[] = "[FAILED] fixing constraint " . $fields[1]."";
                    $this->_dbOkay = false;
                    $selftester->messages[] = $e->getMessage();
                    $localError = true;
                }
            } else {
                $selftester->messages[] = "[FAILED] constraint " . $fields[1]."";
                $this->_dbOkay = false;
                $selftester->messages[] = "[ERR] ".$e->getMessage();
                $localError = true;
            }
        }
        return $localError;
    }

    /**
     * check the DB for expected content
     *
     * @param Fooman_Common_Model_Selftester $selftester
     * @param                                $fields
     * @param                                $installer
     * @param                                $localError
     *
     * @return bool
     */
    protected function _dbCheckDbRow(Fooman_Common_Model_Selftester $selftester, $fields, $installer, $localError)
    {
        try {
            $where = array();
            foreach ($fields[2] as $key => $value) {
                $where[] = "`" . $key . "`='" . $value . "'";
            }
            $sql ="SELECT * FROM `{$installer->getTable( $fields[1])}` WHERE ".implode(' AND ', $where).";";

            $result = $installer->getConnection()->fetchOne($sql);
            if (!$result) {
                throw new Exception(
                    sprintf('Did not find content in %s', $installer->getTable($fields[1]))
                );
            }

            $selftester->messages[] = "[OK] Content " . $fields[1]."";
        } catch (Exception $e) {
            if ($selftester->shouldFix()) {
                $selftester->messages[] = "Attempting fix for content in " . $fields[1]."";
                try {
                    $installer->getConnection()->insert(
                        $installer->getTable($fields[1]),
                        $fields[2]
                    );
                    $selftester->messages[] = "[FIX OK] content in " . $fields[1]." fixed";
                } catch (Exception $e) {
                    $selftester->messages[] = "[FAILED] fixing content in " . $fields[1]."";
                    $this->_dbOkay = false;
                    $selftester->messages[] = $e->getMessage();
                    $localError = true;
                }
            } else {
                $selftester->messages[] = "[FAILED] content in " . $fields[1]."";
                $this->_dbOkay = false;
                $selftester->messages[] = "[ERR] ".$e->getMessage();
                $localError = true;
            }
        }
        return $localError;
    }
}
