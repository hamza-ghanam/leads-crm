<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Store a log of operation.
     *
     * @param String $operation
     * @param Array $oldData
     * @param Array $newData
     */
    public static function createLog($operation, $className, $oldData, $newData)
    {
        $oldArray = [];
        $newArray = [];

        if ($operation === 'add') {
            $oldArray = null;
            $newArray = json_encode($newArray);
        } elseif ($operation === 'update') {
            foreach ($oldData as $key => $oldDatum) {
                if ($oldDatum != $newData[$key]) {
                    $oldArray += [$key => $oldDatum];
                    $newArray += [$key => $newData[$key]];
                }
            }

            $oldArray = json_encode($oldArray);
            $newArray = json_encode($newArray);
        } elseif ($operation === 'delete') {
            $oldArray = null;
            $newArray = null;
        }

        Log::create([
            'user_id' => auth()->user()->id,
            'operation' => $operation,
            'class_name' => $className,
            'old_data' => $oldArray,
            'new_data' => $newArray
        ]);
    }
}
