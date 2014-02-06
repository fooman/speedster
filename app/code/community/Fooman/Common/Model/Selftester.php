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

class Fooman_Common_Model_Selftester extends Fooman_Common_Model_Selftester_Abstract
{
    public $messages = array();
    public $errorOccurred = false;
    protected $_fix = false;

    /**
     * Start the selftest
     *
     * @return $this
     */
    public function main ()
    {
        $this->messages[] = 'Starting ' . get_class($this);
        $failed = false;
        try {
            if (!$this->selfCheckLocation()) {
                $failed = true;
            }
            if (Mage::app()->getRequest()->getParam('fix') == 'true') {
                $this->_fix = true;
            }
            if (!$this->checkFileLocations()) {
                $failed = true;
            }
            if (!$this->magentoRewrites()) {
                $failed = true;
            }
            if (!$this->dbCheck()) {
                $failed = true;
            }
            if (!$this->hasSettings()) {
                $failed = true;
            }
            if (!$failed) {
                $this->messages[] = 'Result: success';
            } else {
                $this->messages[] = 'Result: failure';
                $this->errorOccurred = true;
            }
        } catch (Exception $e) {
            $this->errorOccurred = true;
            $this->messages[] = $e->getMessage();
        }
        $this->messages[] = 'Self-test finished';
        return $this;
    }

    /**
     * preliminary tests if the selftest can find a Magento instance
     *
     * @return bool
     * @throws Exception
     */
    public function selfCheckLocation()
    {
        if (file_exists('app' . DIRECTORY_SEPARATOR . 'Mage.php')) {
            require_once 'app' . DIRECTORY_SEPARATOR . 'Mage.php';
            Mage::app();
            $this->messages[] = "Default store loaded";
            $this->_getVersions();
        } else {
            $this->messages[] = 'Can\'t instantiate Magento. Is the file uploaded to your root Magento folder?';
            throw new Exception();
        }
        return true;
    }

    /**
     * return fix boolean flag
     *
     * @return bool
     */
    public function shouldFix()
    {
        return $this->_fix;
    }

    /**
     * test if all expected files exist and can be read/opened
     *
     * @return bool
     */
    public function checkFileLocations()
    {
        $returnVal = true;
        $this->messages[] = "Checking file locations";
        foreach ($this->_getFiles() as $currentRow) {

            if (empty($currentRow)) {
                continue;
            }
            try {
                if (!file_exists($currentRow)) {
                    throw new Exception('File ' . $currentRow . ' does not exist');
                }
                if (!is_readable($currentRow)) {
                    throw new Exception(
                        'Can\'t read file ' . $currentRow . ' - please check file permissions and file owner.'
                    );
                }

                $handleExtFile = fopen($currentRow, "r");
                if (!$handleExtFile) {
                    throw new Exception(
                        'Can\'t read file contents ' . $currentRow
                        . ' - please check if the file got corrupted in the upload process.'
                    );
                }
                fclose($handleExtFile);
            } catch (Exception $e) {
                $this->messages[] = $e->getMessage();
                $returnVal = false;
            }
        }
        return $returnVal;
    }

    /**
     * check that rewrites return expected classes
     *
     * @return bool
     */
    public function magentoRewrites ()
    {
        $returnVal = true;
        $this->messages[] = "Checking rewrites";

        foreach ($this->_getRewrites() as $currentRow) {

            if (empty($currentRow) || !$currentRow) {
                continue;
            }
            try {
                $this->_testRewriteRow($currentRow);
            } catch (Exception $e) {
                $this->messages[] = $e->getMessage();
                $returnVal = false;
            }
        }
        return $returnVal;
    }

    /**
     * check the database for expected tables, columns and attributes
     *
     * @return bool
     */
    public function dbCheck()
    {
        //we don't use getModel since the common extension might not yet be installed correctly
        $dbCheckModel = new Fooman_Common_Model_Selftester_Db();
        return $dbCheckModel->dbCheck($this);
    }

    /**
     * retrieve current database info relevant for debugging
     *
     * @return bool
     */
    public function hasSettings()
    {
        foreach ($this->_getSettings() as $table => $tableValues) {

            $this->messages[] = $table;
            foreach ($tableValues as $setting) {
                $msg = array();
                foreach ($setting as $key => $value) {
                    $msg[] = $key . ': ' . $value;
                }
                $this->messages[] = implode(' | ', $msg);
            }

        }
        return true;
    }

}
