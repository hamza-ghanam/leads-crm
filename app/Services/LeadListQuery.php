<?php


namespace App\Services;

use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeadListQuery
{
    public function build(User $user, array $filterParams = [], ?int $leadId = null): Builder
    {
        $query = Ticket::query();

        // 1) Status filter input (slug)
        $statusSlug = $filterParams['status'] ?? null;

        if ($statusSlug && $statusSlug !== 'all') {
            $statusId = Status::where('slug', $statusSlug)->value('id');
            if ($statusId) {
                $query->where('status_id', $statusId);
            }
        }

        // 2) Role-based visibility (same as web)
        $this->applyRoleScope($query, $user, $statusSlug);

        // 3) ID filter
        if ($leadId) {
            $query->where('id', $leadId);
        }

        // 4) Sale filter (dropdown)
        if (!empty($filterParams['sale']) && $filterParams['sale'] !== 'all') {
            $query->where('user_id', (int)$filterParams['sale']);
        }

        // 5) Exclude duplicated/dead for non-super-admin
        $this->excludeHiddenStatuses($query, $user);

        // 6) Eager loads + ordering (API/Web both need these)
        return $query->with(['user', 'latestFollowUpPath', 'status', 'source', 'assigner'])
            ->orderByDesc('created_at');
    }

    private function applyRoleScope(Builder $query, User $user, ?string $statusSlug): void
    {
        if ($user->hasRole('admin')) {
            // admin sees only booking + rejected (same as web)
            $ids = Status::whereIn('slug', ['booking', 'rejected'])->pluck('id')->all();
            $query->whereIn('status_id', $ids);
            return;
        }

        if ($user->hasRole('accountant')) {
            // accountant sees approved + sold (same as web, regardless of status filter)
            $ids = Status::whereIn('slug', ['approved', 'sold'])->pluck('id')->all();
            $query->whereIn('status_id', $ids);
            return;
        }

        if ($user->hasRole('sales-manager')) {
            // sales-manager sees own + employees
            $employeeIds = User::where('manager_id', $user->id)->pluck('id')->all();
            $employeeIds[] = $user->id;

            $query->whereIn('user_id', $employeeIds);
            return;
        }

        if ($user->hasAnyRole(['sale', 'tele-sale'])) {
            // sales sees only own
            $query->where('user_id', $user->id);

            // web rule: in "all" status case, exclude rejected
            if (!$statusSlug || $statusSlug === 'all') {
                $rejectedId = Status::whereSlug('rejected')->value('id');
                if ($rejectedId) {
                    $query->where('status_id', '!=', $rejectedId);
                }
            }

            return;
        }

        // super-admin: no restriction
    }

    private function excludeHiddenStatuses(Builder $query, User $user): void
    {
        if ($user->hasRole('super-admin')) {
            return;
        }

        $dupId = Status::whereSlug('duplicated')->value('id');
        $deadId = Status::whereSlug('dead')->value('id');

        if ($dupId) {
            $query->where('status_id', '!=', $dupId);
        }
        if ($deadId) {
            $query->where('status_id', '!=', $deadId);
        }
    }

    public function sqlWithBindings($query)
    {
        $sql = $query->toSql();

        foreach ($query->getBindings() as $binding) {
            $value = is_numeric($binding)
                ? $binding
                : "'" . addslashes($binding) . "'";
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return $sql;
    }
}
