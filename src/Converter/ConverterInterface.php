<?php

/**
 * This file is part of the backendtea/php-optimizer package.
 * Copyright (c) 2018 Gert de Pagter
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpOptimizer\Converter;

use PhpParser\Node;

interface ConverterInterface
{
    /**
     * Determines whether or not the current node should be converted.
     *
     * @param Node $node
     *
     * @return bool
     */
    public function shouldConvert(Node $node): bool;

    /**
     * Converts the current node into its desired outcome.
     *
     * @param Node $node
     *
     * @return Node|Node[]
     */
    public function convert(Node $node);
}
