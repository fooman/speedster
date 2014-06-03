<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Mage
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com) (original implementation)
 * @copyright  Copyright (c) 2008 Fooman (http://www.fooman.co.nz) (use of Minify Library)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Speedster Html Head Block
 *
 * @package Fooman_Speedster
 * @author  Kristof Ringleff <kristof@fooman.co.nz>
 */
class Fooman_Speedster_Block_Page_Html_Head extends Mage_Page_Block_Html_Head
{

    public function getCssJsHtml()
    {
        if (!Mage::getStoreConfigFlag('foomanspeedster/settings/enabled')) {
            return parent::getCssJsHtml();
        }
        $webroot = "/";

        $lines = array();

        $baseJs = Mage::getBaseUrl('js');
        $baseJsFast = Mage::getBaseUrl('skin') . 'm/';
        Mage::getConfig()->getVarDir('minifycache');
        $html = '';
        //$html = "<!--".BP."-->\n";
        $script = '<script type="text/javascript" src="%s" %s></script>';
        $stylesheet = '<link type="text/css" rel="stylesheet" href="%s" %s />';
        $alternate = '<link rel="alternate" type="%s" href="%s" %s />';

        foreach ($this->_data['items'] as $item) {
            if (!is_null($item['cond']) && !$this->getData($item['cond'])) {
                continue;
            }
            $if = !empty($item['if']) ? $item['if'] : '';
            switch ($item['type']) {
                case 'js':
                    if (strpos($item['name'], 'packaging.js') !== false) {
                        $item['name'] = $baseJs . $item['name'];
                        $lines[$if]['script_direct'][] = $item;
                    } else {
                        $lines[$if]['script']['global'][] = "/" . $webroot . "js/" . $item['name'];
                    }
                    break;

                case 'script_direct':
                    $lines[$if]['script_direct'][] = $item;
                    break;

                case 'css_direct':
                    $lines[$if]['css_direct'][] = $item;
                    break;

                case 'js_css':
                    $lines[$if]['other'][] = sprintf($stylesheet, $baseJs . $item['name'], $item['params']);
                    break;

                case 'skin_js':
                    $chunks = explode('/skin', $this->getSkinUrl($item['name']), 2);
                    $skinJsURL = "/" . $webroot . "skin" . $chunks[1];

                    if (strpos($item['name'], '.min.js') !== false) {
                        $skinJsLoc = BP . DS . "skin" . $chunks[1];
                        $skinJsContent = file_get_contents($skinJsLoc);
                        if (preg_match('/@ sourceMappingURL=([^\s]*)/s', $skinJsContent, $matches)) {
                            //create a file without source map
                            $md5 = md5($skinJsContent);
                            $skinJsLoc = str_replace('.min.js', '.' . $md5 . '.min.js', $skinJsLoc);
                            $skinJsURL = str_replace('.min.js', '.' . $md5 . '.min.js', $skinJsURL);
                            if (!file_exists($skinJsLoc)) {
                                file_put_contents(
                                    $skinJsLoc,
                                    str_replace(
                                        $matches[0], 'orig file with source map: ' . $this->getSkinUrl($item['name']),
                                        $skinJsContent
                                    )
                                );
                            }
                        }
                    }

                    $lines[$if]['script']['skin'][] = $skinJsURL;
                    break;

                case 'skin_css':
                    if ($item['params'] == 'media="all"') {
                        $chunks = explode('/skin', $this->getSkinUrl($item['name']), 2);
                        $lines[$if]['stylesheet'][] = "/" . $webroot . "skin" . $chunks[1];
                    } elseif ($item['params'] == 'media="print"') {
                        $chunks = explode('/skin', $this->getSkinUrl($item['name']), 2);
                        $lines[$if]['stylesheet_print'][] = "/" . $webroot . "skin" . $chunks[1];
                    } else {
                        $lines[$if]['other'][] = sprintf(
                            $stylesheet, $this->getSkinUrl($item['name']), $item['params']
                        );
                    }
                    break;

                case 'rss':
                    $lines[$if]['other'][] = sprintf(
                        $alternate, 'application/rss+xml' /*'text/xml' for IE?*/, $item['name'], $item['params']
                    );
                    break;

                case 'link_rel':
                    $lines[$if]['other'][] = sprintf('<link %s href="%s" />', $item['params'], $item['name']);
                    break;

                case 'ext_js':
                default:
                    $lines[$if]['other'][] = sprintf(
                        '<script type="text/javascript" src="%s"></script>', $item['name']
                    );
                    break;

            }
        }

        foreach ($lines as $if => $items) {
            if (!empty($if)) {
                // open !IE conditional using raw value
                if (strpos($if, "><!-->") !== false) {
                    $html .= $if . "\n";
                } else {
                    $html .= '<!--[if '.$if.']>' . "\n";
                }
            }
            if (!empty($items['stylesheet'])) {
                $cssBuild = Mage::getModel('speedster/buildSpeedster', array($items['stylesheet'], BP));
                foreach (
                    $this->getChunkedItems($items['stylesheet'], $baseJsFast . $cssBuild->getLastModified()) as $item
                ) {
                    $html .= sprintf($stylesheet, $item, 'media="all"') . "\n";
                }
            }
            if (!empty($items['script']['global']) || !empty($items['script']['skin'])) {
                if (!empty($items['script']['global']) && !empty($items['script']['skin'])) {
                    $mergedScriptItems = array_merge($items['script']['global'], $items['script']['skin']);
                } elseif (!empty($items['script']['global']) && empty($items['script']['skin'])) {
                    $mergedScriptItems = $items['script']['global'];
                } else {
                    $mergedScriptItems = $items['script']['skin'];
                }
                $jsBuild = Mage::getModel('speedster/buildSpeedster', array($mergedScriptItems, BP));
                $chunkedItems = $this->getChunkedItems($mergedScriptItems, $baseJsFast . $jsBuild->getLastModified());
                foreach ($chunkedItems as $item) {
                    $html .= sprintf($script, $item, '') . "\n";
                }
            }
            if (!empty($items['css_direct'])) {
                foreach ($items['css_direct'] as $item) {
                    $html .= sprintf($stylesheet, $item['name']) . "\n";
                }
            }
            if (!empty($items['script_direct'])) {
                foreach ($items['script_direct'] as $item) {
                    $html .= sprintf($script, $item['name'], '') . "\n";
                }
            }
            if (!empty($items['stylesheet_print'])) {
                $cssBuild = Mage::getModel('speedster/buildSpeedster', array($items['stylesheet_print'], BP));
                foreach (
                    $this->getChunkedItems($items['stylesheet_print'], $baseJsFast . $cssBuild->getLastModified()) as
                    $item
                ) {
                    $html .= sprintf($stylesheet, $item, 'media="print"') . "\n";
                }
            }
            if (!empty($items['other'])) {
                $html .= join("\n", $items['other']) . "\n";
            }
            if (!empty($if)) {
                // close !IE conditional comments correctly
                if (strpos($if, "><!-->") !== false) {
                    $html .= '<!--<![endif]-->' . "\n";
                } else {
                    $html .= '<![endif]-->' . "\n";
                }
            }
        }
        return $html;
    }

    public function getChunkedItems($items, $prefix = '', $maxLen = 2000)
    {
        if (!Mage::getStoreConfigFlag('foomanspeedster/settings/enabled')) {
            return parent::getChunkedItems($items, $prefix, 450);
        }

        $chunks = array();
        $chunk = $prefix;

        foreach ($items as $item) {
            if (strlen($chunk . ',' . $item) > $maxLen) {
                $chunks[] = $chunk;
                $chunk = $prefix;
            }
            //this is the first item
            if ($chunk === $prefix) {
                //remove first slash, only needed to create double slash for minify shortcut to document root
                $chunk .= substr($item, 1);
            } else {
                //remove first slash, only needed to create double slash for minify shortcut to document root
                $chunk .= ',' . substr($item, 1);
            }
        }

        $chunks[] = $chunk;
        return $chunks;
    }


}
