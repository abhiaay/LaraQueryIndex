<?php

namespace Aayustho\QueryIndex\Database\Mongodb;

use Jenssegers\Mongodb\Eloquent\Model;

class QueryIndex extends Model
{
    protected $collection = 'query_index';
    protected $primaryKey = '_id';

    public static function add(string $collectionName, array $pipeline, array $explain)
    {
        $queryIndex = new self();

        $queryIndex->collection_name = $collectionName;
        $queryIndex->filter = $filter;
        $queryIndex->isLookup = $isLookup;
        $queryIndex->parent_id = $parent;

        $queryIndex->save();

        return $queryIndex;
    }
}
