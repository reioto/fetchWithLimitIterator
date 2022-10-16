<?php

declare(strict_types=1);

namespace FetchWithLimitIterator;

/**
 * @template T
 */
interface FetchAllWithLimitInterface
{
    /**
     * @return iterable<T>
     */
    public function fetchAllWithLimit(int $limit, int $offset): iterable;
}
