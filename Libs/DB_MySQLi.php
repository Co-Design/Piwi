<?php
/**
 * MySQLi Query Builder
 *
 * $auth           = new stdClass;
 * $auth->server   = 'localhost';
 * $auth->user     = 'user';
 * $auth->password = 'password';
 * $auth->databas  = 'database';
 * $db = new DB_MySQLi($auth);
 *
 * $db->get('table') // records of 'select * from table';
 *
 * $db->where('id', 1)->get('table') // records of 'select * from table where id = 1'
 *
 * $db->select('id, name')->where('id > 1')->get('table')  //records of 'select id, name from table where id > 1'
 *
 * $db->where('id', 1)->where('name', 'name')->where('mail', 'mail', 'or')->getRow('table') //records of 'select * from table where id = 1 and name = 'name' or mail = 'mail' limit 1 
 * 
 * $db->limit(1)->getRow('table') //records of  'select * from table limit 1'
 *  
 * $db->limit(1, 2)->get('table') //records of 'select * from table limit 1, 2'
 * 
 * Note: query results are stdClass objects
 *
 * @package     Piwi
 * @subpackage  DB_MySQLi
 * @author      a77icu5 <the.a77icu5@gmail.com>
 * @version     1.0
 */
class DB_MySQLi {

  /**
   * handler of the conection
   * 
   * @access  private
   * @var   object  $_con;
   */
  private $_con   = NULL;
  
  /**
   * sql string
   * 
   * @access  private
   * @var   string  $_query
   */
  private $_query   = NULL;
  
  /**
  * sql statenment
  *
  * @access private
  * @var    object  $_stmt
  */
  private $_stmt    = NULL;
  
  /**
   * string of the last query ejecuted
   * 
   * @access  private
   * @var   string  $_myQquery
   */
  private $_myQuery = NULL;
  
  /**
   * name of the table to use in the queries
   * 
   * @access  private
   * @var   string
   */
  private $_table   = NULL;
  
  /**
   * values to return 
   * 
   * @access  private
   * @var   string  $_values;
   */
  private $_values  = '*';
  
  /**
   * sql where condition
   * 
   * @access  private 
   * @var   string  $_where;
   */
  private $_where   = NULL;
  
  /**
   * sql order by option
   * 
   * @access  private
   * @var   string  $_order;
   */
  private $_order   = NULL;
  
  /**
   * sql group by option
   * 
   * @access  private
   * @var   string  $_group
   */
  private $_group   = NULL;
  
  /**
   * sql limit option
   * 
   * @access  private
   * @var   string $_limit;
   */
  private $_limit   = NULL;
  
  /**
   * sql inner join option
   * 
   * @access  private
   * @var   string  $_join;
   */
  private $_join    = NULL;
  
  /**
   * result of the sql statenment 
   * 
   * @access  private
   * @var   object  $_result
   */
  private $_result  = NULL;
  
  /**
   * Start the conection with the credentials defined
   * 
   * @access  public
   * @param object  $credentials
   * @return  void
   */
  public function  __construct($auth) {
    $this->_con = $this->connect($auth);
  }
  
  /**
   * connect with mysql and select the databse to use
   *
   * @access  public
   * @param object  $credentials
   * @return  object
   */
  public function connect($auth) {
    try  {
      $this->_con = new mysqli($auth->server, $auth->user, $auth->password, $auth->database);
      if (mysqli_connect_errno()) {
        throw new Exception('We can\'t connect using provided credentials.');
      }
      $this->_con->set_charset('utf8');
      return $this->_con;
    } catch (Exception $e) {
      echo 'PHP Exception(' 
        . $e->getCode() . '): ' 
        . $e->getMessage() . ' in ' 
        . $e->getFile() . ' at line '
        . $e->getLine();
      exit();
    }
  }
  
  /**
   * initialize custom query
   * 
   * @access  public
   * @param string  $query
   * @return  object
   */
  public function setQuery($query) {
    $this->_query = trim($query);
  }
  
  /**
   * insert new rows in the database
   * 
   * @access  public
   * @param array $values
   * @param string  $table[optional]
   * @see   method::scape()
   * @see   method::getTable()
   * @see   method::getLimit()
   * @see   method::execute()
   * @return  void
   */
  public function insert($tableData, $table = NULL) {
    $values = get_object_vars($tableData);
    $this->_query = 'INSERT INTO `';
    $this->_query .= ($table != NULL) ? trim($table) : $this->getTable();
    $this->_query .= '` (`' . implode('`,`', array_keys($values)) . '`) VALUES (';
    foreach ($values as $key => $value) {
      if(is_numeric($value)) {
        $this->_query .= ', ' . $this->scape($value);
      } else {
        $this->_query .= ', \''. $this->scape($value) . '\'';
      }
    }
    $this->_query = str_replace('(,', '(', $this->_query) . ') '. $this->getLimit();
    $this->execute();
  }
  
  /**
   * update records in the database
   * 
   * @access  public
   * @param array $values
   * @param string  $table[optional]
   * @see   method::getWhere()
   * @see   method::getLimit()
   * @see   method::execute()
   * @return  void 
   */
  public function update($tableData, $table = NULL) {
    $values = get_object_vars($tableData);
    $data = NULL;
    $this->_query = 'UPDATE `';
    $this->_query .= ($table != NULL) ? trim($table) : $this->getTable();
    $this->_query .= '` SET ';
    foreach ($values as $key => $value) {
      if ($key !== NULL) {
        $data .= '`' . $key . '` = ';
        if(!is_numeric($value)) {
          $data .= '\'' . $this->scape($value) . '\', '; 
        } else {
          $data .= $this->scape($value) . ', ';
        }
      }
    }
    $this->_query .= $data;
    $this->_query = substr($this->_query, 0, strlen($this->_query)-2) . $this->getWhere() . ' ' . $this->getLimit();
    $this->execute();
  }

  
  /**
   * delete records in the database
   * 
   * @access  public
   * @param string  $table[optional]
   * @see   method::getWhere()
   * @see   method::getLimit()
   * @see   method::execute()
   * @return  void
   */
  public function delete($table = NULL) {
    $this->_query = 'DELETE FROM `';
    $this->_query .= ($table != NULL) ? trim($table) : $this->getTable();
    $this->_query .= '` ' . $this->getWhere() . ' ' . $this->getLimit();
    $this->execute();
  }
  
  /**
   * define the table name to use in the queries
   * 
   * @access  public
   * @param string  $table
   * @return  object
   */
  public function table($table) {
    $this->_table = trim($table);
    return $this;
  }
  
  /**
   * define the values to return
   * 
   * @access  public
   * @param string  $values
   * @return  object
   */
  public function values($values = '*'){
    $this->_values = trim($values);
    return $this;
  }
  
  /**
   * define the where condition in the sql statement
   * 
   * @access  public
   * @param string  $key
   * @param mixed $value[optional]
   * @param string  $type[optional]
   * @see   method::scape()
   * @return  object
   */
  public function where($where, $value = NULL, $type = 'AND') {
    if ($this->_where != '') {
      if ($value === NULL) {
        $this->_where .= ' ' . trim($type) . ' ' . trim($where);
      } else {
        $this->_where .= ' ' . trim($type) . ' ' . trim($where) . ' = ';
        if(!is_numeric($value)) {
          $this->_where .= "'" . $this->scape($value) . "'";
        } else {
          $this->_where .= $this->scape($value);
        }
      }     
    } else {
      if ($value === NULL) {
        $this->_where = ' WHERE ' . trim($where);
      } else {
        $this->_where = ' WHERE ' . trim($where) . ' = ';
        if(!is_numeric($value)) {
          $this->_where .= "'" . $this->scape($value) . "'";
        } else {
          $this->_where .= $this->scape($value);
        }
      }
    }
    return $this;
  }
  
  /**
   * define the where like condition in the sql statement
   * 
   * @access  public
   * @param string  $key
   * @param mixed $like
   * @param string  $type[optional]
   * @return  object
   */
  public function like($where, $like, $type = 'AND') {
    if ($this->_where != '') {
      $this->_where .= ' ' . trim($type) . ' ' . trim($where) . ' LIKE ' . "'{$like}'";
    } else {
      $this->_where = ' WHERE ' . trim($where) . ' LIKE ' . "'{$like}'";
    }
    return $this;
  }
  
  /**
   * define the order by option
   * 
   * @access  public
   * @param string  $values
   * @param string  $type[optional]
   * @return  object
   */
  public function order($values, $type = 'desc') {
    $this->_order = ' ORDER BY ' . trim($values) . ' ' . trim($type);
    return $this;
  }
  /**
   * define the group by option
   * 
   * @access  public
   * @param string  $values
   * @return  object
   */
  public function group($values) {
    $this->_group = ' GROUP BY ' . trim($values);
    return $this;
  }
  
  /**
   * define the inner join option, if inner join  is already defined
   * then create another join option in the query
   * 
   * @access  public
   * @param string $table
   * @param string $condition
   * @param string $type[optional]
   * @return  object
   */
  public function tableJoin($table, $condition, $type) {
    if($this->_join != '') {
      $this->_join .= ' ' . strtoupper($type) . ' JOIN `' . trim($table) . '` ON ' . trim($condition);
    } else {
      $this->_join = ' ' . strtoupper($type) . ' JOIN `' . trim($table) . '` ON ' . trim($condition);
    }
    return $this;
  }
  
  /**
   * define the limit of rows returned
   * 
   * @access  public
   * @param string $offset
   * @param string $numrows
   * @return  object
   */
  public function limit($numrows = 0, $offset = 0) {
    if ($numrows > 0) {
      if($offset == 0) {
        $this->_limit =  ' LIMIT ' . $numrows;
      } else {
        $this->_limit =  ' LIMIT ' . $offset . ', ' . $numrows;
      }
    }
    return $this;
  }
  
  /**
   * return the table name to use in the queries
   * 
   * @access  public
   * @return  string
   */
  public function getTable() {
    return $this->_table;
  }
  
  /**
   * return the values defined
   * 
   * @access  public
   * @return  string;
   */
  public function getValues() {
    return $this->_values;
  }
  
  /**
   * return 'where' contidion of the query
   * 
   * @access  public
   * @return  string 
   */
  public function getWhere() {
    return $this->_where;
  }
  
  /**
   * return 'order by' option of the query
   * 
   * @access  public
   * @return  string
   */
  public function getOrder() {
    return  $this->_order;
  }
  
  /**
   * return 'group by' option of the query
   * 
   * @access  public
   * @return  string 
   */
  public function getGroup() {
    return $this->_group;
  }
  
  /**
   * return all 'the inner join' options of the query
   * 
   * @access  public
   * @return  string 
   */
  public function getTableJoin() {
    return $this->_join;
  }
  
  /**
   * return 'limit' option of the query
   * 
   * @access  public
   * @see   method::limit
   * @return  string 
   */
  public function getLimit(){
    return $this->_limit;
  }
  
  /**
   * return all the rows fetched in the query
   * 
   * @access  public
   * @param string $table
   * @see   method::getValues()
   * @see   method::getTableJoin()
   * @see   method::getWhere()
   * @see   method::getGroup()
   * @see   method::getOrder()
   * @see   method::getLimit()
   * @return  object
   */
  public function get($table = NULL){
    $this->_query = 'SELECT ' . $this->getValues() . ' FROM `';
    $this->_query .= ($table != NULL) ? trim($table) . '`' : $this->getTable() . '`';
    $this->_query .= $this->getTableJoin()
      . $this->getWhere() 
      . $this->getGroup() 
      . $this->getOrder()
      . $this->getLimit();
    $this->execute();
    return $this->fetchQuery();
  }
  
  /**
   * return only one row fetched in the query
   * 
   * @access  public
   * @param string $table
   * @see   method::getValues()
   * @see   method::getTableJoin()
   * @see   method::getWhere()
   * @see   method::getGroup()
   * @see   method::getOrder()
   * @see   method::getLimit()
   * @return  array 
   */
  public function getRow($table = NULL){
    $this->_query = 'SELECT ' . $this->getValues() . ' FROM `';
    $this->_query .= ($table != NULL) ? trim($table) . '`' : $this->getTable() . '`';
    $this->_query .= $this->getTableJoin()
      . $this->getWhere() 
      . $this->getGroup() 
      . $this->getOrder()
      . $this->getLimit();
    
    $this->execute();
    return $this->fetchQuery(TRUE);
  }
  
  /**
   * restore values of the atributes used in the query after ejecuted
   * 
   * @access  private
   * @return  void
   */
  private function _reset() {
    $this->_myQuery = $this->_query;
    $this->_query = NULL;
    $this->_values  = '*';
    $this->_where = NULL;
    $this->_group = NULL;
    $this->_order = NULL;
    $this->_join  = NULL;
    $this->_limit = NULL;
    $this->_fetched = array();
  }
  
  /**
   * count number rows in the current
   * query executed
   * 
   * @access  public
   * @return  integer 
   */
  public function numRows() {
    return $this->_result->num_rows;
  }
  
  /**
   * return the last ID generated by
   * the current query executed
   * 
   * @access  public
   * @return  integer 
   */
  public function insertId() {
    return $this->_con->insert_id;
  }
  
  /**
   * return number of rows afected by
   * the last sql statenment
   * 
   * @access  public
   * @return  integer 
   */
  public function rowsAfected() {
    return $this->_con->affected_rows;
  }
  
  /**
   * free mysql memory used in the current
   * select sql statenment 
   * 
   * @access  public
   * @return  void 
   */
  public function freeResult() {
    $this->_result->free();
  }
  
  /**
   * close the current conection
   * 
   * @access  public
   * @return  void 
   */
  public function disconnect() {
    $this->_con->close();
  }
  
  /**
   * return the current sql statenment
   * generated by the user
   * 
   * @access  public
   * @return  string 
   */
  public function myQuery() {
    return $this->_myQuery;
  }
  
  /**
   * execute sql statenments
   * 
   * @access  public
   * @return  boolean 
   */
  public function execute() {
    try {
      if (!$this->_result = $this->_con->query($this->_query)) {
        throw new Exception('Query error: ' . mysqli_error($this->_con));
      }
      $this->_reset();
    } catch (Exception $e) {
      echo 'PHP Exception(' 
        . $e->getCode() . '): ' 
        . $e->getMessage() . ' in ' 
        . $e->getFile() . ' at line ' 
        . $e->getLine();
      exit();
    } 
  }
  
  /**
   * create an array with the fetched rows produced by the query
   * 
   * @access  public
   * @return  object
   */
  public function fetchQuery($singleRow = FALSE){
    $fetched = array();
    if($singleRow) {
      return $this->_result->fetch_object();
    }
    while($row = $this->_result->fetch_object()) {
      $fetched[] = $row;
    }
    return $fetched;
  }
  
  /**
   * scape variables before insert or select in the database
   * 
   * @access  public
   * @param mixed $value
   * @return  mixed
   */
  public function scape($value){
    if(is_numeric($value)) {
      return stripslashes($value);
    }
    return $this->_con->real_escape_string($value);
  }
  
  /**
  * close current database connection
  * 
  * @access public
  * @return void
  */
  public function __destruct() {
    $this->disconnect();
  }
}