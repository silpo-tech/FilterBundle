<?php

declare(strict_types=1);

namespace FilterBundle\Bridge\Doctrine\Common;

interface SearchFilterInterface
{
    /**
     * @var string Exact matching
     */
    public const STRATEGY_EXACT = 'exact';

    /**
     * @var string The value must be contained in the field
     */
    public const STRATEGY_PARTIAL = 'partial';

    /**
     * @var string Finds fields that are starting with the value
     */
    public const STRATEGY_START = 'start';

    /**
     * @var string Finds fields that are ending with the value
     */
    public const STRATEGY_END = 'end';

    /**
     * @var string Finds fields that are starting with the word
     */
    public const STRATEGY_WORD_START = 'word_start';

    /**
     * @var string Exact matching (Ignore case)
     */
    public const STRATEGY_IEXACT = 'iexact';

    /**
     * @var string The value must be contained in the field (Ignore case)
     */
    public const STRATEGY_IPARTIAL = 'ipartial';

    /**
     * @var string Finds fields that are starting with the value (Ignore case)
     */
    public const STRATEGY_ISTART = 'istart';

    /**
     * @var string Finds fields that are ending with the value (Ignore case)
     */
    public const STRATEGY_IEND = 'iend';

    /**
     * @var string Finds fields that are starting with the word
     */
    public const STRATEGY_IWORD_START = 'iword_start';
}
