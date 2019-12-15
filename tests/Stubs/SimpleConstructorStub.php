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

class SimpleConstructorStub
{
    public $a;
    public $b;
    public $c;

    public function __construct(int $a, int $b, int $c = 3)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }
}
