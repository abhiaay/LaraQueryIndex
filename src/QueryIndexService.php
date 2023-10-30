<?php

namespace Aayustho\QueryIndex;

use ArrayObject;

class QueryIndexService
{
    public function getRecomendationIndexFromStages(array $stages)
    {
        // $myArray = [
        //     true,
        //     ['abc', 'def', false],
        //     false,
        //     1,
        //     'ghi',
        //     2,
        //     [
        //         ['HELLO', 'WORLD', true],
        //         [123, 456, false, 'false'],
        //     ],
        // ];

        // array_walk_recursive(
        //     $myArray,
        //     function (&$value) {
        //         if (is_bool($value)) {
        //             $value = 'I AM A BOOLEAN';
        //         }
        //     }
        // );

        // dd($myArray);

        foreach ($stages as $stage) {
        }
        $queries = $stages[0]['query'];
        $suc = array_walk_recursive($queries, function (&$query, $key) {
            // if ($stage instanceof ArrayObject) {
            //     $stage = $stage->getArrayCopy();
            // } else {
            // }
            dd($key);
            $stage = 'lorem';
        });

        // dd()
        dd($suc, 'as', $stages);
        return $stages;
    }

    public function getRecomendationIndexFromStage(mixed $stage)
    {
        return 'lorem';
    }
}
