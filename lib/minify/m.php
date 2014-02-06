<?php
// Minify Entry Point for Magento Extension Fooman Speedster
define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);
//detect if run from a modman installation
if(strpos(__FILE__,'.modman') !== false){
    $baseDir = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
} else {
    $baseDir = dirname(dirname(dirname(__FILE__)));
}

require_once dirname(dirname(dirname(__FILE__))) . DS . 'lib' . DS . 'minify' . DS . 'Minify' . DS . 'Loader.php';
Minify_Loader::register();

/**
 * Leave an empty string to use PHP's $_SERVER['DOCUMENT_ROOT'].
 *
 * On some servers, this value may be misconfigured or missing. If so, set this
 * to your full document root path with no trailing slash.
 * E.g. '/home/accountname/public_html' or 'c:\\xampp\\htdocs'
 *
 * If /min/ is directly inside your document root, just uncomment the
 * second line. The third line might work on some Apache servers.
 */
$min_documentRoot = '';
//$min_documentRoot = substr(__FILE__, 0, strlen(__FILE__) - 15);
//$min_documentRoot = $_SERVER['SUBDOMAIN_DOCUMENT_ROOT'];

// try to disable output_compression (may not have an effect)
ini_set('zlib.output_compression', '0');

// Set $minifyCachePath to a PHP-writeable path to enable server-side caching
$minifyCachePath = $baseDir.DS.'var'.DS.'minifycache';

// The Files controller only "knows" CSS, and JS files.
$serveExtensions = array('css', 'js');

/**
 * Handle Multiple Stores - symlinked directories
 */

// Figure out if we are run from a subdirectory
$rootdir = '';
$dir = explode("/lib/minify/m.php", htmlentities($_SERVER['SCRIPT_NAME']));
if (strlen($dir[0]) == 0) {
    // we are in webroot
    $min_symlinks = array('//' => $baseDir);
} else {
    // we are in a subdirectory adjust symlink
    $rootdir = preg_replace('!' . $dir[0] . '$!', '', $baseDir);
    $min_symlinks = array('//' => $rootdir);
    //use this for ~user apache installs
    //$min_symlinks=array( '/'.$dir[0]=>$rootdir);
}

// Serve
if (isset($_GET['f'])) {
    $_GET['f'] = str_replace("\x00", '', (string)$_GET['f']);
    $filenames = explode(",", $_GET['f']);
    $filenamePattern = '/[^\'"\\/\\\\]+\\.(?:' . implode('|', $serveExtensions) . ')$/';

    if ($minifyCachePath) {
        SpeedsterMinify::setCache($minifyCachePath);
    }

    if ($min_documentRoot) {
        $_SERVER['DOCUMENT_ROOT'] = $min_documentRoot;
    } elseif (0 === stripos(PHP_OS, 'win')) {
        SpeedsterMinify::setDocRoot(); // IIS may need help
    }

    //on some apache installs this is needed
    if (array_key_exists('SUBDOMAIN_DOCUMENT_ROOT', $_SERVER)) {
        $_SERVER['DOCUMENT_ROOT'] = $_SERVER['SUBDOMAIN_DOCUMENT_ROOT'];
    }

    //check if requested files exists and add to minify request
    $servefiles = array();
    foreach ($filenames as $filename) {
        if (preg_match($filenamePattern, $filename)
            && file_exists($baseDir . $filename)
        ) {
            $servefiles[] = $baseDir . $filename;
        }
    }

    //options for minify request
    $serveOptions = array(
        'quiet'            => false,
        'rewriteCssUris'   => true,
        'files'            => $servefiles,
        'maxAge'           => 31536000, // now + 1 yr
        'bubbleCssImports' => 'true'
    );

    //include option for symlinks and merge with $serveOptions
    $min_serveOptions['minifierOptions']['text/css']['symlinks'] = $min_symlinks;
    if (!empty($rootdir)) {
        $min_serveOptions['minifierOptions']['text/css']['prependRelativePath'] = $rootdir;
    }
    $serveOptions = array_merge($serveOptions, $min_serveOptions);

    //and SERVE
    SpeedsterMinify::serve('Files', $serveOptions);
    exit();
}

header("HTTP/1.0 404 Not Found");
echo "HTTP/1.0 404 Not Found";
