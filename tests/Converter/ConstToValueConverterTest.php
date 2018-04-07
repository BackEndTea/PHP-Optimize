<?php

/**
 * This file is part of the backendtea/php-optimizer package.
 * Copyright (c) 2018 Gert de Pagter
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpOptimizer\Test\Converter;

use PhpOptimizer\Converter\ConstToValueConverter;
use PhpParser\Node;
use PHPUnit\Framework\TestCase;

final class ConstToValueConverterTest extends TestCase
{
    public function test_it_correctly_converts(): void
    {
        $node = new Node\Expr\ClassConstFetch(
            new Node\Name(['Foo', 'Bar']),
            'Baz'
        );
        $converter = new ConstToValueConverter(['Foo\Bar\Baz' => 3]);

        $this->assertTrue($converter->shouldConvert($node));
        $this->assertSame(3, $converter->convert($node));
    }

    public function test_it_should_not_convert_if_const_is_not_added(): void
    {
        $node = new Node\Expr\ClassConstFetch(
            new Node\Name(['Foo', 'Bar']),
            'Baz'
        );
        $converter = new ConstToValueConverter([]);

        $this->assertFalse($converter->shouldConvert($node));
    }

    public function test_it_should_not_convert_if_item_is_not_a_const_fetch(): void
    {
        $node = new Node\Stmt\Nop();
        $converter = new ConstToValueConverter([]);

        $this->assertFalse($converter->shouldConvert($node));
    }
}
