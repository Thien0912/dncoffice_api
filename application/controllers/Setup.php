<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setup extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->dbforge();
    }

    public function migrate_login_logs() {
        if (!$this->db->table_exists('sys_login_logs')) {
            $this->dbforge->add_field(array(
                'id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => TRUE,
                    'auto_increment' => TRUE
                ),
                'ql_nguoi_dung_id' => array(
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => FALSE,
                ),
                'ip_address' => array(
                    'type' => 'VARCHAR',
                    'constraint' => '45',
                    'null' => TRUE,
                ),
                'user_agent' => array(
                    'type' => 'TEXT',
                    'null' => TRUE,
                ),
                'created_at' => array(
                    'type' => 'DATETIME',
                    'null' => TRUE,
                    'default' => NULL
                ),
            ));
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table('sys_login_logs');
            echo "Table sys_login_logs created successfully.";
        } else {
            echo "Table sys_login_logs already exists.";
        }
    }
}
