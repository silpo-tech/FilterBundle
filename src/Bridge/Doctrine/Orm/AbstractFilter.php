<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Orm;

use Doctrine\Persistence\ManagerRegistry;
use FilterBundle\Bridge\Doctrine\Common\PropertyHelperTrait;
use Psr\Log\LoggerInterface;

abstract class AbstractFilter
{
    use PropertyHelperTrait;

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, ManagerRegistry $managerRegistry)
    {
        $this->logger = $logger;
        $this->managerRegistry = $managerRegistry;
    }
}
