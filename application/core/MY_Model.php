<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Model extends CI_Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $hidden = [];
    protected $timestamps = true;
    protected $createdAtField = 'created_at';
    protected $updatedAtField = 'updated_at';

    public function __construct()
    {
        parent::__construct();
    }

    public function db()
    {
        $this->db;
        return $this;
    }
    public function count_all_results($reset = TRUE)
    {
        $this->db->count_all_results($this->table, $reset);
        return $this;
    }

    public function reset_query()
    {
        $this->db->reset_query();
        return $this;
    }

    /**
     * @param string $columns
     * @return $this
     */
    public function select($columns)
    {
        $this->db->select($columns);
        return $this;
    }

    /**
     * side default is both, options are before and after
     * @param mixed $field
     * @param string $match
     * @param string $side
     * @param mixed $escape
     * @return $this
     * 
     */
    public function like($field, $match = '', $side = 'both', $escape = NULL)
    {
        $this->db->like($field, $match, $side, $escape);
        return $this;
    }

    /**
     * side default is both, options are before and after
     * @param mixed $field
     * @param string $match
     * @param string $side
     * @param mixed $escape
     * @return $this
     * 
     */
    public function orLike($field, $match = '', $side = 'both', $escape = NULL)
    {
        $this->db->or_like($field, $match, $side, $escape);
        return $this;
    }

    /**
     * @param mixed $field
     * @param string $value
     * @return $this
     * Where condition field
     * 
     */
    public function where($field, $value = null)
    {
        $this->db->where($field, $value);
        return $this;
    }


    /**
     * @param mixed $field
     * @param string $value
     * @return $this
     * Or where condition field
     * 
     */
    public function orWhere($field, $value = null)
    {
        $this->db->or_where($field, $value);
        return $this;
    }


    /**
     * @param mixed $field
     * @param string $value
     * @return $this
     * Condition with field not equal value
     * 
     */
    public function whereNot($field, $value = null)
    {
        $this->db->where($field . ' !=', $value);
        return $this;
    }


    /**
     * @param mixed $field
     * @param string $value
     * @return $this
     * Condition with field not equal value or where condition is not equal value
     * 
     */
    public function orWhereNot($field, $value = null)
    {
        $this->db->or_where($field . ' !=', $value);
        return $this;
    }


    /**
     * @param mixed $field
     * @return $this
     * Condition with field is null
     * 
     */
    public function whereNull($field)
    {
        $this->db->where($field . ' IS NULL', null);
        return $this;
    }


    /**
     * @param mixed $field
     * @return $this
     * Condition with field is not null
     * 
     */
    public function whereNotNull($field)
    {
        $this->db->where($field . ' IS NOT NULL', null);
        return $this;
    }


    /**
     * @param mixed $field
     * @param mixed $min
     * @param mixed $max
     * @return $this
     * Condition with field form min value to max value
     */
    public function whereBetween($field, $min, $max)
    {
        $this->db->where("$field BETWEEN '$min' AND '$max'");
        // $this->db->where("$field >=", $min)->where("$field <=", $max);
        return $this;
    }



    /**
     * @param mixed $orderby field
     * @param string $direction
     * @param mixed $escape
     * @return $this
     */
    public function orderBy($orderby, $direction = '', $escape = NULL)
    {
        $this->db->order_by($orderby, $direction, $escape);
        return $this;
    }

    public function groupBy($by, $escape = NULL)
    {
        $this->db->query("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
        $this->db->group_by($by, $escape);
        return $this;
    }


    /**
     * @param mixed $id
     * @return instance
     */
    public function find($id)
    {
        return $this->hideFieldsWithItem($this->db->where($this->primaryKey, $id)->get($this->table)->row_array());
    }

    /**
     * @param mixed $id
     * @return instance
     */
    public function findOrFail($id)
    {
        $result = $this->find($id);
        if (!$result) {
            return false;
        }
        return $result;
    }

    /**
     * @return first row or first row with conditions
     */
    public function first()
    {
        $query = $this->db->limit(1)->get($this->table);
        return $this->hideFieldsWithItem($query->row_array());
    }

    /**
     * @param mixed $limit
     * @return mixed $offset
     * @return array
     * get rows or rows with conditions
     * get rows with limit and offset
     */
    public function get($limit = null, $offset = null)
    {
        $results = $this->db->get($this->table, $limit, $offset)->result_array();
        return $this->hideFields($results);
    }

    /**
     * @param mixed $column
     * @return array
     */
    public function pluck($column)
    {
        $this->db->select($column);
        $results = $this->db->get($this->table)->result_array();
        return array_column($results, $column);
    }

    /**
     * @param mixed $limit
     * @param int $offset
     * @return array
     */
    public function paginate($limit, $offset = 0)
    {
        return $this->get($limit, $offset);
    }

    /**
     * @param mixed $column
     * @return mixed
     * Count all elements in an array, or something in an object
     * count( mixed $array_or_countable [, int $mode = COUNT_NORMAL ]): int
     */
    public function count($column = null)
    {
        return $this->db->count_all_results($this->table);
    }


    /**
     * @param mixed $data
     * @return mixed
     */
    public function insert($data)
    {
        if ($this->timestamps) {
            $data[$this->createdAtField] = date('Y-m-d H:i:s');
            $data[$this->updatedAtField] = date('Y-m-d H:i:s');
        }
        $this->db->insert($this->table, $data);
        return $this->db->insert_id() ?? null;
    }

    /**
     * @param mixed $data
     * @return mixed
     * insert multiple
     */
    public function insertBatch($data)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        // Chèn các bản ghi vào cơ sở dữ liệu
        $inserted = $this->db->insert_batch($this->table, $data);

        if ($inserted) {
            return $this->db->affected_rows(); // Trả về số lượng bản ghi đã chèn
        }

        return false;
    }

    /**
     * @param mixed $data
     * @return \instance|null
     */
    public function create($data)
    {
        if ($this->timestamps) {
            $data[$this->createdAtField] = date('Y-m-d H:i:s');
            $data[$this->updatedAtField] = date('Y-m-d H:i:s');
        }
        $this->db->insert($this->table, $data);
        return $this->db->insert_id() ? $this->find($this->db->insert_id()) : null;
    }


    /**
     * @param mixed $val
     * @return $this
     */
    public function distinct($val = TRUE)
    {
        $this->db->distinct($val);
        return $this;
    }

    /**
     * @param array $data
     * @return boolean
     * 
     */
    public function update($data)
    {
        if ($this->timestamps) {
            $data[$this->updatedAtField] = date('Y-m-d H:i:s');
        }
        return $this->db->update($this->table, $data);
    }

    /**
     * @return boolean
     */
    public function delete()
    {
        // $this->db->where($this->primaryKey, $id);
        return $this->db->delete($this->table);
    }

    /**
     * Starts a query group.
     *
     * @param	string	$not	(Internal use only)
     * @param	string	$type	(Internal use only)
     * @return	CI_DB_query_builder
     */
    public function groupStart($not = '', $type = 'AND ')
    {
        $this->db->group_start($not, $type);
        return $this;
    }

    /**
     * Ends a query group
     *
     * @return	CI_DB_query_builder
     */
    public function groupEnd()
    {
        $this->db->group_end();
        return $this;
    }

    public function checkValueExists($column, $value)
    {
        $query = $this->db->query("SELECT 1 FROM {$this->table} WHERE {$column} = ? LIMIT 1", [$value]);
        return $query->num_rows() === 1;
    }

    public function checkTwoValueExists($column, $value, $otherColumn, $otherValue)
    {
        // Sử dụng thêm điều kiện WHERE với otherColumn và otherValue
        $query = $this->db->query(
            "SELECT 1 FROM {$this->table} WHERE {$column} = ? AND {$otherColumn} = ? LIMIT 1",
            [$value, $otherValue]
        );

        return $query->num_rows() === 1;
    }



    public function forceDelete()
    {
        return $this->delete();
    }

    // Relationship Methods
    public function with($relations)
    {
        foreach ($relations as $relation => $relation_id) {
            $this->db->join($relation, "$this->table.$relation_id = $relation.$this->primaryKey");
        }
        return $this;
    }

    /**
     * @return array
     * get all record
     */
    public function all()
    {
        return $this->get();
    }


    /**
     * @param mixed $field
     * @param array $values
     * @return $this
     */
    public function whereIn($field, $values)
    {
        $this->db->where_in($field, $values);
        return $this;
    }


    public function whereNotIn($key = NULL, $values = NULL, $escape = NULL)
    {
        $this->db->where_not_in($key, $values, $escape);
        return $this;
    }



    /**
     * @param mixed $relation
     * @param mixed $callback
     * @return array
     * this join with $callback
     */
    public function whereHas($relation, $callback)
    {
        $this->db->join($relation, $callback);
        return $this->get();
    }


    // Join Methods
    /**
     * @param mixed $table
     * @param mixed $condition
     * @param string $type
     * @return $this
     * this join on condition
     * example: $tableA_model->join('table_b', 'table_a.id', 'table_b.id'); 
     */
    public function join($table, $condition, $type = 'inner')
    {
        $this->db->join($table, $condition, $type);
        return $this;
    }

    /**
     * @param mixed $table
     * @param mixed $condition
     * @return $this
     * this left join on condition
     * example: $tableA_model->leftJoin('table_b', 'table_a.id', 'table_b.id'); 
     */
    public function leftJoin($table, $condition)
    {
        return $this->join($table, $condition, 'left');
    }

    /**
     * @param mixed $table
     * @param mixed $condition
     * @return $this
     * this right join on condition
     * example: $tableA_model->rightJoin('table_b', 'table_a.id', 'table_b.id'); 
     */
    public function rightJoin($table, $condition)
    {
        return $this->join($table, $condition, 'right');
    }

    // hidden field with data is array
    protected function hideFields($data)
    {
        foreach ($this->hidden as $field) {
            foreach ($data as &$item) {
                if (isset($item[$field])) {
                    unset($item[$field]);
                }
            }
        }
        return $data;
    }
    // hidden field with data is 1 row array
    protected function hideFieldsWithItem($data)
    {
        foreach ($this->hidden as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }
        return $data;
    }

    //query function
    public function query($sql, $binds = FALSE, $return_object = NULL)
    {
        $this->db->query($sql, $binds, $return_object);
        return $this;
    }

    // show all columns
    public function showColumns($excluded_columns = [])
    {
        $query = $this->db->query("SHOW COLUMNS FROM " . $this->table);
        $columns = $query->result_array();

        $filtered_columns = array_filter($columns, function ($column) use ($excluded_columns) {
            return !in_array($column['Field'], $excluded_columns);
        });

        return array_values($filtered_columns);
    }
}
