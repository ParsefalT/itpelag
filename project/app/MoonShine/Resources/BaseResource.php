<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Ability;

abstract class BaseResource extends ModelResource
{
    protected function isCan(Ability $ability): bool
    {
        if (! parent::isCan($ability)) {
            return false;
        }

        $user = MoonShineAuth::getGuard()->user();

        if ($user === null) {
            return true;
        }

        $isAdmin = method_exists($user, 'isSuperUser') && $user->isSuperUser();

        if ($isAdmin) {
            return true;
        }

        return in_array($ability, [Ability::VIEW, Ability::VIEW_ANY], true);
    }
}
