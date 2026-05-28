<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;
use App\User\Entity\UserPreference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPreference>
 */
final class UserPreferenceRepository extends ServiceEntityRepository implements UserPreferenceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPreference::class);
    }

    public function findByUserAndKey(User $user, string $key): ?UserPreference
    {
        return $this->findOneBy([
            'user' => $user,
            'key' => $key,
        ]);
    }

    public function save(UserPreference $preference, bool $flush = false): void
    {
        $this->getEntityManager()->persist($preference);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
