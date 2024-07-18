<?php

namespace Cleantalk\Custom\Db;

class Db extends \Cleantalk\Common\Db\Db
{
    /**
     * Alternative constructor.
     * Initilize Database object and write it to property.
     * Set tables prefix.
     */
    protected function init()
    {
        $this->prefix = "xf_";
    }

    /**
     * Run any raw request
     *
     *
     * @param string $query
     * @param false $return_affected
     * @return bool|int Raw result
     */
    public function execute($query, $return_affected = false)
    {
        try {
            $this->result = \XF::db()->query($query);
        } catch ( \Exception $e ) {
            return false;
        }

        return true;
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
    public function fetch($query = false, $response_type = false)
    {
        if ( !$query ) {
            $query = $this->getQuery();
        }
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
    public function fetchAll($query = false, $response_type = false)
    {
        if ( !$query ) {
            $query = $this->getQuery();
        }
        $this->result = \XF::db()->fetchAll($query);

        return $this->result;
    }

    /**
     * Checks if the table exists
     *
     * @param $table_name
     *
     * @return bool
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function isTableExists($table_name)
    {
        return $this->execute('SHOW TABLES LIKE "' . $table_name . '"');
    }

    public function getAffectedRows()
    {
        // TODO: Implement getAffectedRows() method.
    }
}
