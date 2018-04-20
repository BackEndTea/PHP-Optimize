<?php

/**
 * This file is part of the backendtea/php-optimizer package.
 * Copyright (c) 2018 Gert de Pagter
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpOptimizer\Test;

use PhpOptimizer\Compiler;
use PHPUnit\Framework\TestCase;

final class CompilerTest extends TestCase
{
    public function test_it_creates_phar(): void
    {
        $filename = '/tmp/test-phar.phar';

        $compiler = new Compiler();
        $compiler->compile($filename);

        $this->assertFileExists($filename);

        \unlink($filename);
    }
}
