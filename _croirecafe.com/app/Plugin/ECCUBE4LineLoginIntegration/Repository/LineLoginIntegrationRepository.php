<?php

namespace Plugin\ECCUBE4LineLoginIntegration\Repository;

use Eccube\Repository\AbstractRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Plugin\ECCUBE4LineLoginIntegration\Entity\LineLoginIntegration;

class LineLoginIntegrationRepository extends AbstractRepository
{

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LineLoginIntegration::class);
    }

    public function deleteLineAssociation($lineIntegration)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $em->remove($lineIntegration);
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            return false;
        }

        return true;
    }
}
