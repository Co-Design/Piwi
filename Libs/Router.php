<?php
/**
 * URL Router
 *
 * example.com/index.php/controller/method/param/param
 * example.com/subfolder/index.php/controller/method/param/param
 *
 * $router = new Router();
 *
 * $router->getElement(0) display 'controller'
 *
 * echo '<pre>'; print_r($router->getElements()); echo '</pre>';
 * Array
 * (
 *   [0] => controller
 *   [1] => method
 *   [2] => param
 *   [3] => param
 * ) 
 *
 * @package     Piwi
 * @subpackage  Router
 * @author      a77icu5 <the.a77icu5@gmail.com>
 * @version     1.0
 */
class Router {
  
  /**
  * string of request uri 
  * 
  * @access private
  * @var    string
  */
  private $_url   = NULL;
  
  /**
  * array of elements of the request url
  * 
  * @access private
  * @var    array
  */
  private $_elements  = array();
  
  /**
  * define and create the final url string
  *  
  * @access public
  * @return void
  */
  public function __construct() {
    if(strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) === 0) {
      $this->_url = trim( substr($_SERVER['REQUEST_URI'], strlen($_SERVER['SCRIPT_NAME'])) );
    } else {
      $this->_url = trim($_SERVER['REQUEST_URI']);
    }
    $this->_parse();
  }
  
  /**
  * return specific item in the url if exists
  * 
  * @access public
  * @param  integer $index
  * @return mixed
  */
  public function element($index) {
    if (!isset($this->_elements[$index])) {
      return '';
    }
    return $this->_elements[$index];
  }
  
  /**
  * return the request URI without before clean
  * 
  * @access public
  * @return string
  */
  public function getUrl() {
    return $this->_url;
  }
  
  /**
  * return array of elements of the current URL request
  * 
  * @access public
  * @return array
  */
  public function getElements() {
    return $this->_elements;
  }

  /**
  * check if the url contains valid characters
  * 
  * @access private
  * @return void
  */
  private function _parse() {
    $replace = array('/', '.', '//');
    if($this->_url != '/') {
      try {
        if ( !preg_match("|^[a-z A-Z 0-9 \- \_]+$|i", str_replace($replace, '', $this->_url)) ) {
          throw new Exception('URL contains invalid chars.');
        }
        $this->_createElements();
      } catch(Exception $e) {
        $e->getMessage();
      }       
    }
  }
  
  /**
  * remove sufix and create the array with the url elements
  * 
  * @access private
  * @return void
  */
  private function _createElements() {
    $elements = explode('/', $this->_url);
    for ($x = 1; $x < sizeof($elements); $x++) {
      $this->_elements[] = $elements[$x];
    }
  }
}