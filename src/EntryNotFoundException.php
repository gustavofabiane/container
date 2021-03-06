<?php

/**
 * Container
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Gustavo Fabiane (c) 2019
 */

declare(strict_types=1);

namespace GustavoFabiane\Container;

use Psr\Container\NotFoundExceptionInterface;
use LogicException;

class EntryNotFoundException extends LogicException implements NotFoundExceptionInterface
{
    /**
     * The exception message format
     */
    public const EXCEPTION_MESSAGE_FORMAT = 'Entry [%s] was not found in container';

    /**
     * Create a new EntryNotFoundException instance
     *
     * @param string $entry
     */
    public function __construct(string $entry)
    {
        parent::__construct(
            sprintf(static::EXCEPTION_MESSAGE_FORMAT, $entry)
        );
    }
}
