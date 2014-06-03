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

class Fooman_Speedster_Model_Selftester extends Fooman_Common_Model_Selftester
{

    const MANUAL_URL = 'http://cdn.fooman.co.nz/media/custom/upload/InstructionsandTroubleshooting-FoomanSpeedster.pdf';

    public function main()
    {
        $this->_checkModuleOutput();
        $this->_checkPermissions();
        $this->_verifyMinification();
        $this->_verifyURLRewriting();
        $this->_verifyFull();
        if ($this->errorOccurred) {
            $this->messages[] = $this->getManualLink();
        }
        return parent::main();
    }

    public function getManualLink()
    {
        return sprintf('<a href="%s">%s</a>', self::MANUAL_URL, 'Please read the manual for details.');
    }

    public function _getVersions()
    {
        parent::_getVersions();
        $this->messages[] = 'Speedster DB version: Not installed';
        $this->messages[] = 'Speedster Config version: ' .
            (string)Mage::getConfig()->getModuleConfig('Fooman_Speedster')->version;
    }

    public function _getRewrites()
    {
        return array(
            array("block", "page/html_head", "Fooman_Speedster_Block_Page_Html_Head"),
            array("block", "adminhtml/page_head", "Fooman_Speedster_Block_Adminhtml_Page_Head"),
        );
    }

    public function _getFiles()
    {
        //REPLACE
        return array(
            'skin/m/.htaccess',
            'app/code/community/Fooman/Speedster/sql/speedster_setup/mysql4-upgrade-2.9.9-3.0.0.php',
            'app/code/community/Fooman/Speedster/sql/speedster_setup/mysql4-install-3.0.0.php',
            'app/code/community/Fooman/Speedster/Block/Adminhtml/Extensioninfo.php',
            'app/code/community/Fooman/Speedster/Block/Adminhtml/Page/Head.php',
            'app/code/community/Fooman/Speedster/Block/Page/Html/Head.php',
            'app/code/community/Fooman/Speedster/etc/adminhtml.xml',
            'app/code/community/Fooman/Speedster/etc/config.xml',
            'app/code/community/Fooman/Speedster/etc/system.xml',
            'app/code/community/Fooman/Speedster/Helper/Data.php',
            'app/code/community/Fooman/Speedster/Model/Check.php',
            'app/code/community/Fooman/Speedster/Model/BuildSpeedster.php',
            'app/code/community/Fooman/Speedster/Model/Selftester.php',
            'app/code/community/Fooman/Speedster/LICENSE.txt',
            'app/etc/modules/Fooman_Speedster.xml',
            'lib/minify/m.php',
            'lib/minify/Minify.php',
            'lib/minify/SpeedsterMinify.php',
            'lib/minify/Minify/CommentPreserver.php',
            'lib/minify/Minify/Packer.php',
            'lib/minify/Minify/Build.php',
            'lib/minify/Minify/Lines.php',
            'lib/minify/Minify/HTML/Helper.php',
            'lib/minify/Minify/Logger.php',
            'lib/minify/Minify/Cache/XCache.php',
            'lib/minify/Minify/Cache/APC.php',
            'lib/minify/Minify/Cache/File.php',
            'lib/minify/Minify/Cache/Memcache.php',
            'lib/minify/Minify/Cache/ZendPlatform.php',
            'lib/minify/Minify/Loader.php',
            'lib/minify/Minify/CSS.php',
            'lib/minify/Minify/CSS/UriRewriter.php',
            'lib/minify/Minify/CSS/Compressor.php',
            'lib/minify/Minify/DebugDetector.php',
            'lib/minify/Minify/ClosureCompiler.php',
            'lib/minify/Minify/HTML.php',
            'lib/minify/Minify/YUICompressor.php',
            'lib/minify/Minify/Source.php',
            'lib/minify/Minify/ImportProcessor.php',
            'lib/minify/Minify/YUI/CssCompressor.java',
            'lib/minify/Minify/YUI/CssCompressor.php',
            'lib/minify/Minify/Controller/MinApp.php',
            'lib/minify/Minify/Controller/Files.php',
            'lib/minify/Minify/Controller/Base.php',
            'lib/minify/Minify/Controller/Page.php',
            'lib/minify/Minify/Controller/Groups.php',
            'lib/minify/Minify/Controller/Version1.php',
            'lib/minify/Minify/JS/ClosureCompiler.php',
            'lib/minify/JSMin.php',
            'lib/minify/HTTP/Encoder.php',
            'lib/minify/HTTP/ConditionalGet.php',
            'lib/minify/.htaccess',
            'lib/minify/CSSmin.php',
            'lib/minify/DooDigestAuth.php',
            'lib/minify/FirePHP.php',
            'lib/minify/MrClay/Cli.php',
            'lib/minify/MrClay/Cli/Arg.php',
            'lib/minify/JSMinPlus.php',
        );
        //REPLACE_END
    }

    protected function _checkModuleOutput()
    {
        $stepOk = true;
        try {
            $allStores = Mage::app()->getStores();
            foreach ($allStores as $storeId => $val) {
                if (Mage::getStoreConfigFlag('advanced/modules_disable_output/Fooman_Speedster', $storeId)) {
                    $this->messages[] = '"' . Mage::app()->getStore($storeId)->getName() .
                        '" store has output disabled for Speedster module (Step 1)';
                    $this->errorOccurred = true;
                    $stepOk = false;
                }
            }
        } catch (Exception $e) {
            $stepOk = false;
            $this->errorOccurred = true;
            $this->messages[] = $e->getMessage();
        }
        if ($stepOk) {
            $this->messages[] = 'Step 1 - OK';
        }
    }

    protected function _checkPermissions()
    {
        $stepOk = true;
        try {
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'lib/minify/m.php';
            $client = new Zend_Http_Client($url);
            $response = $client->request();

            if ($response->getBody() != 'HTTP/1.0 404 Not Found') {
                $this->messages[] = 'Verify Permission ERROR (Step 2)';
                $this->errorOccurred = true;
                $stepOk = false;
            }

        } catch (Exception $e) {
            $this->errorOccurred = true;
            $stepOk = false;
            $this->messages[] = $e->getMessage();
        }
        if ($stepOk) {
            $this->messages[] = 'Step 2 - OK';
        }
    }

    protected function _verifyMinification()
    {
        $stepOk = true;
        try {
            $varPath = Mage::getConfig()->getVarDir('minifycache');
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'skin/frontend/default' .
                '/default/css/print.css';
            $client = new Zend_Http_Client($url);
            $response = $client->request();
            $originalLength = $response->getHeader('Content-Length');

            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'lib/minify/m.php?f=/skin/frontend/default' .
                '/default/css/print.css';
            $client->setUri($url);
            $response = $client->request();
            $minifiedLength = $response->getHeader('Content-Length');


            if ($minifiedLength >= $originalLength) {
                $this->messages[] = 'Verify Minification ERROR: minification does not work (Step 3)';
                $this->errorOccurred = true;
                $stepOk = false;
            }

            if (!is_dir($varPath)) {
                $this->messages[] = 'Verify Minification ERROR: "' . $varPath . '" does not exist (Step 3)';
                $this->errorOccurred = true;
                $stepOk = false;
            }
            if (!is_dir_writeable($varPath)) {
                $this->messages[] = 'Verify Minification ERROR: "' . $varPath . '" is not writeable (Step 3)';
                $this->errorOccurred = true;
                $stepOk = false;
            }
            clearstatcache();
            if ($handle = opendir($varPath)) {
                $filesOK = false;
                while (false !== ($entry = readdir($handle))) {
                    if (strpos($entry, 'minify_') !== false) {
                        $filesOK = true;
                        break;
                    }
                }
                closedir($handle);
                if (!$filesOK) {
                    $this->messages[] = 'Verify Minification ERROR: there are no files starting with "minify_" in "' .
                        $varPath . '" (Step 3)';
                    $this->errorOccurred = true;
                    $stepOk = false;
                }
            } else {
                $this->messages[] = 'Verify Minification ERROR: can\'t open "' . $varPath . '" for reading (Step 3)';
                $this->errorOccurred = true;
                $stepOk = false;
            }

        } catch (Exception $e) {
            $this->errorOccurred = true;
            $stepOk = false;
            $this->messages[] = $e->getMessage();
        }
        if ($stepOk) {
            $this->messages[] = 'Step 3 - OK';
        }
    }

    protected function _verifyURLRewriting()
    {
        $stepOk = true;
        try {
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'skin/frontend/default' .
                '/default/css/print.css';
            $client = new Zend_Http_Client($url);
            $response = $client->request();
            $originalLength = $response->getHeader('Content-Length');

            $moduleVersion = (string)Mage::getConfig()->getNode()->modules->Fooman_Speedster->version;

            if (version_compare($moduleVersion, '2.0.0', '>=')) {
                $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) .
                    'skin/m/1232681243/skin/frontend/default/default/css/print.css';
            }

            if (version_compare($moduleVersion, '1.2.0', '<=')) {
                $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) .
                    'skin/m/1232681243/skin/frontend/default/default/css/print.css';
            }

            $client->setUri($url);
            $response = $client->request();
            $minifiedLength = $response->getHeader('Content-Length');

            if ($minifiedLength >= $originalLength) {
                $this->messages[]
                    = 'Verify URL Rewriting ERROR: The minified result is larger than the original (Step 4)';
                $this->errorOccurred = true;
                $stepOk = false;
            }

        } catch (Exception $e) {
            $stepOk = false;
            $this->errorOccurred = true;
            $this->messages[] = $e->getMessage();
        }
        if ($stepOk) {
            $this->messages[] = 'Step 4 - OK';
        }
    }

    protected function _verifyFull()
    {
        if (Mage::getStoreConfigFlag('foomanspeedster/settings/enabled')) {
            $stepOk = true;
            try {
                $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
                $client = new Zend_Http_Client($url);

                $cssLinks = array();
                preg_match_all(
                    '#<link[ a-zA-Z0-9="/]+type="text/css"[ a-zA-Z0-9="/]+href="(.+?)"#s', $client->request(), $cssLinks
                );
                foreach ($cssLinks[1] as $cssLink) {
                    if (strpos($cssLink, '/m/') === false) {
                        $this->messages[]
                            = 'Verify Full Speedster Process ERROR: "' . $cssLink . '" doesn\'t contain ' .
                            'minified functionality (Step 5)';
                        $this->errorOccurred = true;
                        $stepOk = false;
                    }
                }

            } catch (Exception $e) {
                $this->errorOccurred = true;
                $stepOk = false;
                $this->messages[] = $e->getMessage();
            }
            if ($stepOk) {
                $this->messages[] = 'Step 5 - OK';
            }
        }
    }

}


