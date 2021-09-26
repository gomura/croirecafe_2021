<?php

namespace Plugin\ECCUBE4LineLoginIntegration\Repository;

use Eccube\Repository\AbstractRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Plugin\ECCUBE4LineLoginIntegration\Entity\LineLoginIntegrationSetting;

class LineLoginIntegrationSettingRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LineLoginIntegrationSetting::class);
    }

    public function get($id = 1)
    {
        return $this->find($id);
    }
}
