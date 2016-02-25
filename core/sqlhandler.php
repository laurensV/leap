<?php
class SQLHandler
{
    protected $db;
    protected $result;

    /** Connects to database **/

    public function set_db($db)
    {
        $this->db = $db;
    }

    /** Disconnects from database **/
    public function disconnect()
    {
        if (@mysql_close($this->db) != 0) {
            return 1;
        }
        return 0;
    }

    public function has_connection(){
        return $this->db;
    }

    public function selectAll()
    {
        $query = 'select * from `' . $this->_table . '`';
        return $this->query($query);
    }

    public function select($id)
    {
        $query = 'select * from `' . $this->_table . '` where `id` = \'' . mysql_real_escape_string($id) . '\'';
        return $this->query($query, 1);
    }

    /** Custom SQL Query **/

    public function query($query, $singleResult = 0)
    {
        if($this->db){
            $this->result = mysql_query($query, $this->db);

            if (preg_match("/select/i", $query)) {
                $result      = array();
                $table       = array();
                $field       = array();
                $tempResults = array();
                $numOfFields = mysql_num_fields($this->result);
                for ($i = 0; $i < $numOfFields; ++$i) {
                    array_push($table, mysql_field_table($this->result, $i));
                    array_push($field, mysql_field_name($this->result, $i));
                }

                while ($row = mysql_fetch_row($this->result)) {
                    for ($i = 0; $i < $numOfFields; ++$i) {
                        $table[$i]                           = trim(ucfirst($table[$i]), "s");
                        $tempResults[$table[$i]][$field[$i]] = $row[$i];
                    }
                    if ($singleResult == 1) {
                        mysql_free_result($this->result);
                        return $tempResults;
                    }
                    array_push($result, $tempResults);
                }
                mysql_free_result($this->result);
                return ($result);
            } else {
                return $this->result;
            }
        } else {
            $this->result = 0;
        }

    }

    /** Get number of rows **/
    public function getNumRows()
    {
        return mysql_num_rows($this->result);
    }

    /** Free resources allocated by a query **/

    public function freeResult()
    {
        mysql_free_result($this->result);
    }

    /** Get error string **/

    public function getError()
    {
        if($this->db){
            return mysql_error($this->db);
        } else {
            return "Error: No database connection";
        }
    }
}
