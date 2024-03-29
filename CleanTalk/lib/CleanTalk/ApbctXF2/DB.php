<?php

namespace CleanTalk\ApbctXF2;

class DB extends \CleanTalk\Common\DB {
    /**
     * Alternative constructor.
     * Initilize Database object and write it to property.
     * Set tables prefix.
     */
    protected function init() {
        $this->prefix = "xf_";
    }

    /**
     * Set $this->query string for next uses
     *
     * @param $query
     * @return $this
     */
    public function set_query( $query ) {
        $this->query = $query;
        return $this;
    }

    /**
     * Safely replace place holders
     *
     * @param string $query
     * @param array  $vars
     *
     * @return $this
     */
    public function prepare( $query, $vars = array() ) {

    }

    /**
     * Run any raw request
     *
     * @param $query
     *
     * @return bool|int Raw result
     */
    public function execute( $query ) {

        $this->db_result =\XF::db()->query($query);
        
        return $this->db_result;
    }

    /**
     * Fetchs first column from query.
     * May receive raw or prepared query.
     *
     * @param bool $query
     * @param bool $response_type
     *
     * @return array|object|void|null
     */
    public function fetch( $query = false, $response_type = false ) {

        $this->result = \XF::db()->fetchRow($query);
        
        return $this->result;
    }

    /**
     * Fetchs all result from query.
     * May receive raw or prepared query.
     *
     * @param bool $query
     * @param bool $response_type
     *
     * @return array|object|null
     */
    public function fetch_all( $query = false, $response_type = false ) {

        $this->result = \XF::db()->fetchAll($query);

        return $this->result;
    }

    public function get_last_error() {

    }
    
    /**
     * Checks if the table exists
     *
     * @param $table_name
     *
     * @return bool
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function isTableExists( $table_name ){
        return (bool) $this->execute( 'SHOW TABLES LIKE "' . $table_name . '"' );
    }
}