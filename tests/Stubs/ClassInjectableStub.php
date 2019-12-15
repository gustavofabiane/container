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

class ClassInjectableStub
{
    public $service;
    public $x;

    public function __construct(ServiceStub $service, $x = null)
    {
        $this->service = $service;
        $this->x = $x;
    }
}
