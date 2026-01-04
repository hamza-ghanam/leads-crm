<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait AppliesFilteringAndSorting
{
    /**
     * Apply filtering and sorting to a query builder.
     *
     * @param Builder $query
     * @param Request $request
     * @param array $allowedFilters
     * @param array $allowedSorts
     * @param string $defaultSort
     * @return Builder
     */
    protected function applyFilteringAndSorting(
        Builder $query,
        Request $request,
        array   $allowedFilters = [],
        array   $allowedSorts = [],
        string  $defaultSort = '-created_at'
    ): Builder
    {
        // Apply filters (AND logic only)
        foreach ($request->get('filter', []) as $field => $value) {
            if (in_array($field, $allowedFilters, true)) {
                $query->where($field, $value);
            }
        }

        // Apply sorting
        $sort = $request->get('sort');

        if ($sort) {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');

            if (in_array($field, $allowedSorts, true)) {
                $query->orderBy($field, $direction);
                return $query;
            }
        }

        // Apply default sorting if no valid sort provided
        $defaultDirection = str_starts_with($defaultSort, '-') ? 'desc' : 'asc';
        $defaultField = ltrim($defaultSort, '-');

        return $query->orderBy($defaultField, $defaultDirection);
    }
}
