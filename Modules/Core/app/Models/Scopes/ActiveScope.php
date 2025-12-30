<?php

namespace Modules\Core\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class ActiveScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * to use this scope in model, add this line in model
     * type #[ScopedBy([ActiveScope::class])]
     * above the class definition
     */
    public function apply(Builder $builder, Model $model): void
    {
        // check if the guard is admin return
        if (auth()->guard('admin')->check()) {
            return;
        }

        $table = $model->getTable();
        $possibleColumns = ['is_active', 'status', 'active', 'visible', 'published'];

        foreach ($possibleColumns as $column) {
            if (Schema::hasColumn($table, $column)) {
                $builder->where($column, 1);

                return;
            }
        }

        // If no matching column is found, default to is_active
        $builder->where('is_active', 1);
    }
}
