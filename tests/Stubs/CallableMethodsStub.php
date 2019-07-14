<?php

/**
 * Simple Way PHP
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see https://simplewayphp.dev
 */

declare(strict_types=1);

namespace SimpleWay\Container\Tests\Stubs;

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
