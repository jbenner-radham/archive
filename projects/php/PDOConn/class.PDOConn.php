<?php

class PDOConn extends PDO {

  protected $db_type;

  /* ANSI standard apparently supports double quotes to separate values instead of back ticks or brackets, look into ANSI mode... */

  /* $db = new PDOConn(); */

  //protected $db = FALSE; ??

  public function __construct($db_driver, $db_host, $db_name, $db_user, $db_pass) {
    $this->db_type = $db_driver;
    if($db_driver != 'sqlite'):
      if($db_driver == 'sqlsrv'):
        $host_arg = 'server';
        $name_arg = 'database';
      else:
        $host_arg = 'host';
        $name_arg = 'dbname';
      endif;
      $dsn = $db_driver . ':' . $host_arg . '=' . $db_host . ';' . $name_arg . '=' . $db_name;
      try
      {
        parent::__construct($dsn, $db_user, $db_pass);
      }
      catch(PDOException $e)
      {
        echo '<pre>';
        printf("Returned error: %s", $e);
        echo '</pre>';
      } // End try/catch.
    endif;
  }

  public function forceInt($var) {
    if(is_int($var)) return $var;
    elseif(!is_int($var)) return $var = (int)$var;
  }

  protected function prep($sql) {
    $stmt = parent::prepare($sql);
    $stmt->execute();
    // Cannot return $stmt->execute(), must return $stmt seperately.
    return $stmt;
  }
  
  public function get($sql, $obj = FALSE) {
    $stmt = $this->prep($sql);
    if($this->countRow($sql) === 0) return FALSE;
    elseif ($obj === TRUE) return $stmt->fetchAll(PDO::FETCH_OBJ);
    elseif ($obj === FALSE) return $stmt->fetchAll(PDO::FETCH_ASSOC);
    else return FALSE;
  }
  
  public function getCol($sql) {
    $stmt = $this->prep($sql);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  }
  
  public function getJSON($sql, $bind_result = NULL, $bind_param = NULL) {
    $result = $this->get($sql, $bind_result, $bind_param);
    // The 'DEV' constant is defined in the _inc/vars.php file.
    return DEV && PHP_VERSION >= 5.4? json_encode($result, JSON_PRETTY_PRINT):json_encode($result);
  }
  
  public function getOne($sql) {
    $stmt = $this->prep($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
  
  public function countRow($sql) {
    $stmt = $this->prep($sql);
    return $stmt->rowCount();
  }
  
  /* --------------------------------------------------------\
  |  put Function example syntax:                            |
  |  ------------------------------------------------------- |
  |  $table = 'table_name';                                  |
  |  $items = array('column_name'   => 'insertion_data',     |
  |                 'column2_name'  => 'more_data');         |
  |  // As of PHP 5.4+ array shorthand is available e.g.     |
  |  // $items = ['column_name'     => 'insertion_data'      |
  |  //           'column2_name'    => 'more_data'];         |
  |  $db->put($table, $items);                               |
  \ --------------------------------------------------------*/
  
  public function put($table, array $items) {
    return $this->prepPut($table, $items);
  }
  
  /* prepPut Function extends the put function. */
  protected function prepPut($table, array $items) {
    $cols_str = $prep_str = NULL;
    $count = count($items);
    $i = 1;
    foreach($items as $key => $value):
      if($this->db_type == 'mysql') $cols_str .= '`' . $key . '`';
      elseif($this->db_type == 'sqlsrv') $cols_str .= '[' . $key . ']';
      else $cols_str .= $key;
      $prep_str .= ':' . $key;
      if($i++ < $count):
        $cols_str .= ', ';
        $prep_str .= ', ';
      endif;
    endforeach;
    // Build the prepared SQL query.
    $sql = 'INSERT INTO ' . $table . ' (' . $cols_str . ') VALUES (' . $prep_str . ')';
    $stmt = $this->prepare($sql);
    foreach($items as $key => $value) {
      $stmt->bindValue(':' . $key, $value);
    }
    if ($stmt->execute()) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function update($table, array $items = NULL, $where_logic) {
    $cols_str = $prep_str = NULL;
    $count = count($items);
    $i = 1;
    foreach($items as $key => $value):
      if($this->db_type == 'mysql') $cols_str .= '`' . $key . '` = "' . $value . '"';
      elseif($this->db_type == 'sqlsrv') $cols_str .= '[' . $key . ']';
      else $cols_str .= $key;
      $prep_str .= ':' . $key;
      if($i++ < $count):
        $cols_str .= ', ';
        $prep_str .= ', ';
      endif;
    endforeach;
    $sql = 'UPDATE ' . $table . ' SET ' . $cols_str . ' WHERE ' . $where_logic;
    //echo $sql . PHP_EOL;
    $stmt = $this->prep($sql);
    return $stmt;
  }

  public function delRow($table, $where_logic) {
    // This function is untested...
    $sql = 'DELETE FROM ' . $table . ' WHERE ' . $where_logic;
    $stmt = parent::exec($sql);
    return $stmt;
  }

  protected function queryParser($table, $query_type, array $cols = NULL, $where_logic = FALSE) {
    $cols_str = $prep_str = NULL;
    $count = count($cols);
    $i = 1;
    foreach($cols as $value):
      if($this->db_type == 'mysql') $cols_str .= '`' . $value . '`';
      elseif($this->db_type == 'sqlsrv') $cols_str .= '[' . $value . ']';
      else $cols_str .= $value;
      $prep_str .= ':' . $value;
      if($i++ < $count):
        $cols_str .= ', ';
        $prep_str .= ', ';
      endif;
    endforeach;
    if ($query_type === 'SELECT'):
      $sql = self::querySelParser($table, $cols_str, $where_logic);
    endif;
    return $sql;
  }

  protected static function querySelParser($table, $cols_str, $where_logic) {
    $sql = 'SELECT ' . $cols_str . ' FROM `' . $table . '`';
    if ($where_logic !== FALSE):
      if (is_array($where_logic)):
        $where_str = NULL;
        foreach ($where_logic as $logic => $logic_val):
          if ($logic == 'col'):
            $where_str .= '`' . $logic_val . '`';
          elseif ($logic == 'opr'):
            $where_str .= ' ' . $logic_val . ' ';
          elseif ($logic == 'cnd'):
            if (is_int($logic_val)):
              $where_str .= '"' . $logic_val . '"';
            else:
              $where_str .= $logic_val;
            endif;
          endif;
        endforeach;
      endif;
      $sql .= ' WHERE ' . $where_str;
    endif;
    return $sql;
  }

  public function select($table, array $cols = NULL, $where_logic = FALSE, $sort = FALSE) {
    $sql = $this->queryParser($table, 'SELECT', $cols, $where_logic);
    // Elaborate on this method later...
    if ($sort !== FALSE) $sql .= ' ORDER BY ' . $sort;
    return $this->get($sql, TRUE);
  }

  public function getTblCols($table) {
    $sql = 'SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` = "' . $table . '"';
    return $this->getCol($sql, FALSE); /* Change this to return an array instead of an object, fix later for compat. */
  }

  public function getPriKey($table, $db_name = FALSE) {
    $db_name || $db_name = DB_NAME;
    $sql = 'SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = "' . $db_name . '"
            AND `TABLE_NAME` = "' . $table . '"
            AND `COLUMN_KEY` = "PRI"';
    $row = $this->getOne($sql);
    return $row['COLUMN_NAME'];
  }

} // End of PDOConn class.


class MySQLConn extends PDOConn {

  public function __construct($db_host, $db_name, $db_user, $db_pass) {
    $dsn = 'mysql' . ':host=' . $db_host . ';dbname=' . $db_name;
    try {
      parent::__construct($dsn, $db_user, $db_pass);
    } catch(PDOException $e) {
      echo '<pre>';
      printf("Returned error: %s", $e);
      echo '</pre>';
    } // End try/catch.
  }

  public function getDBList() {
    return parent::getCol('SHOW DATABASES');
  }

} // End of MySQLConn class.

//if (isset($db) && is_object($db)) unset($db);

?>
