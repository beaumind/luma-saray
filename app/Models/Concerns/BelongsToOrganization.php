<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes a model to the currently authenticated user's organization.
 *
 * - Adds a global scope so every query is automatically filtered by
 *   organization_id, guaranteeing tenant isolation.
 * - Auto-fills organization_id on create from the authenticated user.
 *
 * When no user is authenticated (console, seeders, login) neither the
 * scope nor the auto-fill apply, so those flows must set organization_id
 * explicitly.
 */
trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            $organizationId = self::currentOrganizationId();

            if ($organizationId !== null) {
                $builder->where(
                    $builder->getModel()->getTable().'.organization_id',
                    $organizationId
                );
            }
        });

        static::creating(function (Model $model) {
            if ($model->getAttribute('organization_id') === null) {
                $organizationId = self::currentOrganizationId();

                if ($organizationId !== null) {
                    $model->setAttribute('organization_id', $organizationId);
                }
            }
        });
    }

    protected static function currentOrganizationId(): ?int
    {
        if (! auth()->hasUser()) {
            return null;
        }

        return auth()->user()->organization_id;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
