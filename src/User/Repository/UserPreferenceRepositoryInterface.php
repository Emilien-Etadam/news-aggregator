<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;
use App\User\Entity\UserPreference;

interface UserPreferenceRepositoryInterface
{
    public function findByUserAndKey(User $user, string $key): ?UserPreference;

    public function save(UserPreference $preference, bool $flush = false): void;

    public function flush(): void;
}
