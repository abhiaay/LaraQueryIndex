<?php

namespace Aayustho\QueryIndex\Database;

use Aayustho\QueryIndex\Database\Mongodb\Collection;
use Jenssegers\Mongodb\Connection as BaseConnection;

class Connection extends BaseConnection
{
    /**
     * Create a new database connection instance.
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

    public function getCollection($name)
    {
        return new Collection($this, $this->db->selectCollection($name));
    }
}
