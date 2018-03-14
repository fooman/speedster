<?php 
error_reporting(E_ALL);
ini_set('display_errors', 'on');

require_once('vendor_minify/autoload.php'); 

define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);
$baseDir = dirname(dirname(dirname(__FILE__)));
$min_documentRoot = '';

$minifyCachePath = $baseDir.DS.'var'.DS.'minifycache';
$serveExtensions = array('css', 'js');

$rootdir = '';
$dir = explode("/lib/minify/m.php", htmlentities($_SERVER['SCRIPT_NAME']));
if (strlen($dir[0]) == 0) {
    $min_symlinks = array('//' => $baseDir);
} else {
    $rootdir = preg_replace('!' . $dir[0] . '$!', '', $baseDir);
    $min_symlinks = array('//' => $rootdir);
}

// setup Minify
$cache = new Minify_Cache_File($minifyCachePath);

/*
$memcache = new Memcache;
$memcache->connect('localhost', 11211);
$min_cachePath = new Minify_Cache_Memcache($memcache);
*/
$minify = new Minify($cache);
$env = new Minify_Env();
$sourceFactory = new Minify_Source_Factory($env, [], $cache);
$controller = new Minify_Controller_Files($env, $sourceFactory);

if (isset($_GET['f'])) {
    $_GET['f'] = str_replace("\x00", '', (string)$_GET['f']);
    $filenames = explode(",", $_GET['f']);
    $filenamePattern = '/[^\'"\\/\\\\]+\\.(?:' . implode('|', $serveExtensions) . ')$/';

    $_SERVER['DOCUMENT_ROOT'] = $min_documentRoot;

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

    // setup serve and controller options
    $options = [
        'quiet'            => false,
        'rewriteCssUris'   => true,
        'files' => $servefiles,
        'maxAge' => 86400,
        'bubbleCssImports' => 'true'
    ];

    //include option for symlinks and merge with $serveOptions
    $min_serveOptions['minifierOptions'][Minify::TYPE_CSS]['symlinks'] = $min_symlinks;
    if (!empty($rootdir)) {
        $min_serveOptions['minifierOptions'][Minify::TYPE_CSS]['prependRelativePath'] = $rootdir;
    }
    $options = array_merge($options, $min_serveOptions);

    # https://github.com/mrclay/minify/blob/master/docs/CustomSource.wiki.md
    $minify->serve($controller, $options);
    exit();
}
/*
function src1_fetch() {
    return file_get_contents('http://example.org/javascript.php');
}

$sources[] = new Minify_Source([
    'id' => 'source1',
    'getContentFunc' => 'src1_fetch',
    'contentType' => Minify::TYPE_JS,
    'lastModified' => max(
        filemtime('/path/to/javascript.php'),
        filemtime('/path/to/javascript_input.js')
    ),
]);
*/
header("HTTP/1.0 404 Not Found");
echo "HTTP/1.0 404 Not Found";
