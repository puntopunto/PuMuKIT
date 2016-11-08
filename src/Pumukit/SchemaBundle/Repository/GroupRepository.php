<?php

namespace Pumukit\SchemaBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * GroupRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GroupRepository extends DocumentRepository
{
    /**
     * Find groups not in
     * the given array.
     *
     * @param array $ids
     *
     * @return Cursor
     */
    public function findByIdNotIn($ids = array())
    {
        return $this->createQueryBuilder()
            ->field('_id')->notIn($ids)
            ->getQuery()
            ->execute();
    }
}
