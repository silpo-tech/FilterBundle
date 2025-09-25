<?php

declare(strict_types=1);

namespace App\Tests\Integration\Common\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'test')]
class TestEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $id;

    #[ORM\Column(type: Types::INTEGER)]
    private int $numeric;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $boolean;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private string $date;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private mixed $nullable;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private mixed $null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private mixed $locale;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private TestEntity $child;
}
