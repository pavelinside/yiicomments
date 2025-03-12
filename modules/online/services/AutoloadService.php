<?php
namespace app\modules\online\services;
/**
 * loader multiple base directories for a single namespace prefix.
 * Given a foo-bar package of classes in the file Encoder at the following paths ...
 *     /path/to/packages/foo-bar/
 *         src/
 *             Baz.php             # Foo\Bar\Baz
 *             Qux/
 *                 Quux.php        # Foo\Bar\Qux\Quux
 *         tests/
 *             BazTest.php         # Foo\Bar\BazTest
 *             Qux/
 *                 QuuxTest.php    # Foo\Bar\Qux\QuuxTest
 *
 * ... add the path to the class files for the \Foo\Bar\ namespace prefix as follows:
 *
 *      // instantiate the loader
 *      $loader = new \Loader\Loader;
 *      // register the autoloader
 *      $loader->register();
 *      // register the base directories for the namespace prefix
 *      $loader->addNamespace('Foo\Bar', '/path/to/packages/foo-bar/src');
 *      $loader->addNamespace('Foo\Bar', '/path/to/packages/foo-bar/tests');
 *
 * \Foo\Bar\Qux\Quux class from /path/to/packages/foo-bar/src/Qux/Quux.php:
 *      new \Foo\Bar\Qux\Quux;
 * \Foo\Bar\Qux\QuuxTest class from /path/to/packages/foo-bar/tests/Qux/QuuxTest.php:
 *      new \Foo\Bar\Qux\QuuxTest;
 */
class AutoloadService {
    /**
     * associative array
     * key - namespace prefix
     * value - array of base directories for classes in that namespace.
     * @var array
     */
    protected $namespaces = [];

    /**
     * associative array
     * key - namespace prefix
     * value - array of base directories for public files.
     * @var array
     */
    protected $_public = [];

    protected $_publicPath = '';
    protected $_publicURL = 'public/';

    public function __construct() {
        $this->_publicPath = __DIR__ . "/public/";
    }

    /**
     * Adds a base directory for a namespace prefix.
     * @param string $namespaceprefix The namespace prefix.
     * @param string $base_dir A base directory for class files in the namespace.
     * @param bool $prepend
     * 		If true, prepend the base directory to the stack instead of appending it;
     * 		this causes it to be searched first rather than last.
     * @return void
     */
    public function addNamespace($prefix, $basedir){
        // normalize namespace prefix
        $namespaceprefix = trim($prefix, '\\').'\\';

        // initialize the namespace prefix array
        if(isset($this->namespaces[$namespaceprefix]) === false){
            $this->namespaces[$namespaceprefix] = [];
        }

        // normalize the base directory with a trailing separator
        $base_dir = rtrim($basedir, DIRECTORY_SEPARATOR) . '/';

        // retain the base directory for the namespace prefix
        $this->namespaces[$namespaceprefix] []= $base_dir;
    }

    /**
     * Adds a directory for a resources prefix.
     * @param string $prefix
     * @param string $dir
     */
    public function addPublic($prefix, $dir, $url){
        $prefix = trim($prefix);
        if(isset($this->_public[$prefix]) === false){
            $this->_public[$prefix] = [];
        }
        $dir = rtrim($dir, DIRECTORY_SEPARATOR) . '/';
        $this->_public[$prefix] []= ['path' => $dir, 'URL' => $url];
    }

    /**
     * Loads the class file for a given class name.
     * @param string $class The fully-qualified class name.
     * @return mixed The mapped file name on success, or boolean false on failure.
     */
    public function loadClass($class){
        // the current namespace prefix
        $prefix = $class;

        do{
            $pos = strrpos($prefix, '\\');

            if($pos !== false){
                // retain the trailing namespace separator in the prefix
                $prefix = substr($class, 0, $pos + 1);
                // the rest is the relative class name
                $relative_class = substr($class, $pos + 1);
            } else {
                // load class without namespace
                $prefix = $class;
                $relative_class = $class;
            }

            // are there any base directories for this namespace prefix?
            if(isset($this->namespaces[$prefix]) !== false){
                // try to load a mapped file for the prefix and relative class
                foreach($this->namespaces[$prefix] as $base_dir){
                    // replace the namespace prefix with the base directory, append with .php
                    $file = str_replace('\\', '/', $base_dir.$relative_class) . '.php';

                    // require mapped file
                    if(file_exists($file)){
                        //echo $file.'->';
                        require_once $file;
                        return $file;
                    }
                }
            }

            // remove the trailing namespace separator for the next iteration of strrpos()
            $prefix = rtrim($prefix, '\\');
        } while(false !== $pos);

        return false;
    }

    /**
     * include res files from folder 'res' (js, css)
     * @param $namespace
     * @param array $files
     * @param array $paths
     * @return string				TODO return all paths on disk
     */
    private function _loadPublic($namespace, array &$files, array &$paths){
        if(!$files || !$namespace){
            return '';
        }
        $namespaceDir = $namespace === '*' ? '' : $namespace;
        if($namespace !== '*'){
            $namespaceDir .= '/';
        }

        foreach($files as $file){
            $pi = pathinfo($file);
            $html = '';
            $extension = strtolower($pi['extension']);

            $publicDir = '';
            switch($extension){
                case 'css':
                    $href = $this->_publicURL.$namespaceDir.$extension.'/'.$file;
                    $publicDir = $extension.DIRECTORY_SEPARATOR;
                    $html = "<link rel='Stylesheet' type='text/css' href='$href' />\n";
                    break;
                case 'js':
                    $href = $this->_publicURL.$namespaceDir.$extension.'/'.$file;
                    $publicDir = $extension.DIRECTORY_SEPARATOR;
                    $html = "<script type='text/javascript' src='$href'></script>\n";
                    break;
                case 'jpg':
                case 'jpeg':
                case 'gif':
                case 'png':
                    $href = $this->_publicURL.$namespaceDir.'img/'.$file;
                    $publicDir = 'img'.DIRECTORY_SEPARATOR;
                    $html = $href;
                    break;
            }
            if($publicDir) {
                $srcFile = $this->_publicPath . $namespaceDir . $publicDir . $file;
                $srcFile = str_replace('\\', '/', $srcFile);
                if (file_exists($srcFile)) {
                    $paths [$file] = ['html' => $html, 'path' => $srcFile];
                } else if (isset($this->_public[$namespace])) {
                    // TODO рекурсия search in additional folders
                    foreach ($this->_public[$namespace] as $directory) {
                        if(!isset($directory['path'], $directory['URL'])){
                            continue;
                        }
                        $srcFile = $directory['path'] . $file;
                        if (file_exists($srcFile)) {
                            switch ($extension) {
                                case 'css':
                                    $html = "<link rel='Stylesheet' type='text/css' href='{$directory['URL']}/$file' />\n";
                                    break;
                                case 'js':
                                    $html = "<script type='text/javascript' src='{$directory['URL']}/$file'></script>\n";
                                    break;
                                case 'jpg':
                                case 'jpeg':
                                case 'gif':
                                case 'png':
                                    $html = $directory['URL'] . $file;
                                    break;
                            }
                            $paths [$file] = ['html' => $html, 'path' => $srcFile];
                        }
                    }
                }
            }
        }

        $res = '';
        $toremove = [];
        foreach($files as $file){
            if(array_key_exists($file, $paths)){
                $res .= $paths [$file]['html'];
                $toremove []= $file;
            }
        }
        $files = array_diff($files, $toremove);
        return $res;
    }

    /**
     * include res files from folder 'res' (js, css)
     * @param array $files
     * @param array $modules
     * @return string
     */
    public function loadPublic(array $files, array $prefixes = ['*']){
        $files = array_unique($files);
        if(!$files){
            return '';
        }

        $paths = [];
        $res = '';
        foreach($prefixes as $findprefix){
            $prefix = $findprefix."\\";
            if($findprefix === '*'){
                $pts = [];
                $res .= $this->_loadPublic($findprefix, $files, $pts);
                $paths = array_merge($paths, $pts);
                continue;
            }
            if(!isset($this->namespaces[$prefix])){
                continue;
            }
            foreach($this->namespaces[$prefix] as $base_dir){
                $dir = str_replace('\\', '/', $base_dir);
                if(!is_dir($dir)){
                    continue;
                }

                $pts = [];
                $res .= $this->_loadPublic($findprefix, $files, $pts);
                $paths = array_merge($paths, $pts);
            }
        }

        // TODO error
        if($files){
            print_r($files);
        }

        return $res;
    }

    /**
     * get Resourse paths
     * @param string $prefix
     * @return multitype:|boolean
     */
    public function getResoursePath($prefix){
        if(isset($this->_public[$prefix])){
            return $this->_public[$prefix];
        }
        return false;
    }

    /**
     * @param string $publicPath
     */
    public function setPublicPath($publicPath) {
        $this->_publicPath = $publicPath;
    }

    /**
     * @param string $publicURL
     */
    public function setPublicURL($publicURL) {
        $this->_publicURL = $publicURL;
    }

    /**
     * Register loader with SPL autoloader stack.
     * @return void
     */
    public function register(){
        spl_autoload_register(array($this,'loadClass'));
    }
}