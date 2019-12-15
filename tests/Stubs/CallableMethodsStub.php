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

namespace GustavoFabiane\Container\Tests\Stubs;

class CallableMethodsStub
{
    public function stub(): bool
    {
        return true;
    }

    public function stubWithArg(array $option = []): array
    {
        return $option;
    }

    public function setubWithMultipleArgs(array $option, \stdClass $object): array
    {
        $option['object'] = $object;
        return $option;
    }

    public function __invoke(array $option = []): array
    {
        return $this->stubWithArg($option);
    }
}
