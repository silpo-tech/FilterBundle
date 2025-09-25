<?php

declare(strict_types=1);

namespace FilterBundle\Dto;

class OrderItem
{
    public string $field;

    public string $direction;

    public function __construct(string $field, string $direction)
    {
        $this->field = $field;
        $this->direction = $direction;
    }
}
