<?php
/**
 * Fooman Speedster
 *
 * @package   Fooman_Speedster
 * @author    Kristof Ringleff <kristof@fooman.co.nz>
 * @copyright Copyright (c) 2009 Fooman Limited (http://www.fooman.co.nz)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Extend Minify_Build so it returns the last modified timestamp only
 */


if (defined('COMPILER_INCLUDE_PATH')) {
    require_once COMPILER_INCLUDE_PATH . DS . 'minify' . DS . 'Minify' . DS .'Loader.php';
    //the below is required since Magento's autoloader emits a warning otherwise
    require_once COMPILER_INCLUDE_PATH . DS . 'minify' . DS . 'Minify' . DS .'Build.php';
} else {
    require_once BP . DS . 'lib' . DS . 'minify' . DS . 'Minify' . DS . 'Loader.php';
    //the below is required since Magento's autoloader emits a warning otherwise
    require_once BP . DS . 'lib' . DS . 'minify' . DS . 'Minify' . DS .'Build.php';
}
Minify_Loader::register();

class Fooman_Speedster_Model_BuildSpeedster extends Minify_Build
{

    /**
     * Create a build object
     *
     * @param array $arguments
     *
     * @return \Fooman_Speedster_Model_BuildSpeedster
     */
    public function __construct($arguments)
    {
        list($sources, $base) = $arguments;
        $max = 0;
        foreach ((array)$sources as $source) {
            if ($source instanceof Minify_Source) {
                $max = max($max, $source->lastModified);
            } elseif (is_string($source)) {
                if (0 === strpos($source, '//')) {
                    $source = $base . substr($source, 1);
                }
                if (is_file($source)) {
                    $max = max($max, filemtime($source));
                }
            }
        }
        $this->lastModified = $max;
        return $this;
    }


    /**
     * Get last modified
     *
     * @return string
     */
    public function getLastModified()
    {
        if (0 === stripos(PHP_OS, 'win')) {
            Minify::setDocRoot(); // we may be on IIS
        }
        return $this->lastModified;
    }
}