<?php

namespace App\Services;

use App\Enums\ApiErrorCode;
use App\Helpers\ApiResponse;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class LeadAccess
{
    /**
     * Returns a query builder scoped to leads visible to this user.
     */
    public function visibleLeadsQuery(User $user): Builder
    {
        $q = Ticket::query();

        // Full access roles
        if ($user->hasAnyRole(['super-admin', 'admin', 'accountant'])) {
            return $q;
        }

        // Sales-manager: self + direct reports
        if ($user->hasRole('sales-manager')) {
            $teamIds = User::where('manager_id', $user->id)->pluck('id')->toArray();
            $teamIds[] = $user->id;

            return $q->whereIn('user_id', $teamIds);
        }

        // Sales / Tele-sales: only current owned leads
        if ($user->hasAnyRole(['sale', 'tele-sale'])) {
            return $q->where('user_id', $user->id);
        }

        // Default: no access (safer)
        return $q->whereRaw('1 = 0');
    }

    /**
     * Throws 404 if lead is not visible.
     */
    public function findVisibleLeadOrFail(User $user, int $leadId): \Illuminate\Database\Eloquent\Model
    {
        return $this->visibleLeadsQuery($user)->whereKey($leadId)->firstOrFail();
    }

    /**
     * Whether this user has full access to paths (history) for any visible lead.
     */
    public function canSeeAllPaths(User $user): bool
    {
        return $user->hasAnyRole([
            'super-admin',
            'admin',
            'accountant',
            'sales-manager'
        ]);
    }

    /**
     * Sanitize/authorise "sale" filter (assigned_to) based on user role.
     * - sale/tele-sale: can only filter themselves (ignore others)
     * - sales-manager: can filter only within team/self
     * - admin/accountant/super-admin: no restriction
     */
    public function sanitiseAssignedToFilter(User $user, array &$filterParams): void
    {
        // Normalise
        $sale = $filterParams['sale'] ?? null;

        if ($sale === null || $sale === '' || $sale === 'all') {
            $filterParams['sale'] = null;
            return;
        }

        $saleId = (int)$sale;

        // Full access
        if ($user->hasAnyRole(['super-admin', 'admin', 'accountant'])) {
            $filterParams['sale'] = $saleId;
            return;
        }

        // Sales-manager: only team/self
        if ($user->hasRole('sales-manager')) {
            $teamIds = User::where('manager_id', $user->id)->pluck('id')->toArray();
            $teamIds[] = $user->id;

            $filterParams['sale'] = in_array($saleId, $teamIds, true) ? $saleId : null;
            return;
        }

        // sale / tele-sale: only self
        if ($user->hasAnyRole(['sale', 'tele-sale'])) {
            $filterParams['sale'] = ($saleId === (int)$user->id) ? $saleId : null;
            return;
        }

        // Default: deny
        $filterParams['sale'] = null;
    }

    public function assertCanActOnLeadOrFail(User $user, Ticket $lead): void
    {
        // sales & tele-sales must act only on their current leads
        if ($user->hasAnyRole(['sale', 'tele-sale'])) {
            if ((int)$lead->user_id !== (int)$user->id) {
                // return NOT_FOUND (same visibility logic)
                throw new ModelNotFoundException();
            }
        }

    }
}
