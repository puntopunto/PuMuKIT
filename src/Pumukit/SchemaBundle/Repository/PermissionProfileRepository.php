<?php

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Pumukit\SchemaBundle\Document\PermissionProfile;

/**
 * PermissionProfileRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PermissionProfileRepository extends DocumentRepository
{
    /**
     * Change default.
     *
     * @param PermissionProfile $permissionProfile
     * @param bool              $default
     */
    public function changeDefault(PermissionProfile $permissionProfile, $default = true)
    {
        $this->createQueryBuilder()
            ->field('name')->notEqual($permissionProfile->getName())
            ->update()
            ->multiple(true)
            ->field('default')->set(!$default)
            ->field('default')->equals($default)
            ->getQuery()
            ->execute();
    }

    /**
     * Find candidate for being default.
     *
     * Candidate:
     * - Less amount of permissions
     *
     * @param int $totalPermissions
     */
    public function findDefaultCandidate($totalPermissions = 0)
    {
        $count = 0;
        $size = -1;
        do {
            if ($count > 0) {
                break;
            }
            ++$size;
            $count = $this->createQueryBuilder()
                ->field('permissions')->size($size)
                ->count()
                ->getQuery()
                ->execute();
        } while ($size <= $totalPermissions);

        if ($count > 0) {
            return $this->createQueryBuilder()
                ->field('permissions')->size($size)
                ->getQuery()
                ->getSingleResult();
        }

        return null;
    }
}
