<?php

namespace Aayustho\QueryIndex\Database\Mongodb;

use Aayustho\QueryIndex\QueryIndexService;
use Exception;
use Jenssegers\Mongodb\Collection as MongodbCollection;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection as MongoCollection;
use MongoDB\Operation\Aggregate;
use MongoDB\Operation\Find;
use MongoDB\Driver\ReadPreference;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

use function MongoDB\is_last_pipeline_operator_write;
use function MongoDB\is_in_transaction;
use function MongoDB\select_server;
use function MongoDB\server_supports_feature;

class Collection extends MongodbCollection
{
    /** @var integer */
    private static $wireVersionForFindAndModifyWriteConcern = 4;

    /** @var integer */
    private static $wireVersionForReadConcern = 4;

    /** @var integer */
    private static $wireVersionForWritableCommandWriteConcern = 5;

    /** @var integer */
    private static $wireVersionForReadConcernWithWriteStage = 8;

    /**
     * @inheritdoc
     */
    public function __call($method, $parameters)
    {
        if ($this->collection->getCollectionName() === 'ticket') {
            if ($method === 'aggregate') {
                call_user_func_array([$this, 'explainAggregation'], $parameters);
            }
        }
        return parent::__call($method, $parameters);
    }

    private function explainFind(Find $operation)
    {
        return $this->explain($operation);
    }

    private function explainAggregation(array $pipeline, array $options = [])
    {
        $hasWriteStage = is_last_pipeline_operator_write($pipeline);

        if (!isset($options['readPreference']) && !is_in_transaction($options)) {
            $options['readPreference'] = $this->collection->getReadPreference();
        }

        if ($hasWriteStage) {
            $options['readPreference'] = new ReadPreference(ReadPreference::RP_PRIMARY);
        }

        $server = select_server($this->collection->getManager(), $options);

        /* MongoDB 4.2 and later supports a read concern when an $out stage is
         * being used, but earlier versions do not.
         *
         * A read concern is also not compatible with transactions.
         */
        if (
            !isset($options['readConcern']) &&
            server_supports_feature($server, self::$wireVersionForReadConcern) &&
            !is_in_transaction($options) &&
            (!$hasWriteStage || server_supports_feature($server, self::$wireVersionForReadConcernWithWriteStage))
        ) {
            $options['readConcern'] = $this->collection->getReadConcern();
        }

        if (!isset($options['typeMap'])) {
            $options['typeMap'] = $this->collection->getTypeMap();
        }

        if (
            $hasWriteStage &&
            !isset($options['writeConcern']) &&
            server_supports_feature($server, self::$wireVersionForWritableCommandWriteConcern) &&
            !is_in_transaction($options)
        ) {
            $options['writeConcern'] = $this->collection->getWriteConcern();
        }

        $operation = new Aggregate($this->collection->getDatabaseName(), $this->collection->getCollectionName(), $pipeline, $options);

        $this->parseReadableExplain($this->collection->explain($operation));
    }

    private function parseReadableExplain(array|object $explain)
    {
        $stages = $explain->stages;

        $queries = [];
        foreach ($stages as $stage) {
            $query = null;
            if (!empty($stage->{'$cursor'})) {
                $queryPlanner =  $stage->{'$cursor'}->queryPlanner;
                $query['query'] = $queryPlanner->parsedQuery;
                $query['indexed'] = $queryPlanner->winningPlan->inputStage->stage !== 'COLLSCAN';

                if ($query['indexed']) {
                    $query['index_pattern'] = $queryPlanner->winningPlan->inputStage->inputStage->keyPattern;
                    $query['index_name'] = $queryPlanner->winningPlan->inputStage->inputStage->indexName;
                }
            } else if (!empty($stage->{'$lookup'})) {
                $query['query'] = $stage->{'$lookup'};
                $query['indexed'] = count($stage->indexesUsed) > 0;

                if ($query['indexed']) {
                    $query['index_name'] = $stage->indexesUsed;
                }
            }

            if (!empty($query)) {
                $queries[] = $query;
            }
        }

        $pipeline = $explain->command->pipeline;
        $collection = $explain->command->aggregate;

        /**
         * @var QueryIndexService
         */
        $queryIndexService = app()->make(QueryIndexService::class);

        $test = $queryIndexService->getRecomendationIndexFromStages($queries);
        dd($test);
    }

    //     private function transformToArray(array|object $data)
    //     {
    //         foreach ($data as $key => $value) {
    //             if ($value instanceof BSONDocument) {
    //                 $data[$key] = $value->getArrayCopy()
    //             }
    //         }
    //     }
}
