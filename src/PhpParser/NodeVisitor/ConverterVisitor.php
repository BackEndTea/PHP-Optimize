<?php

/**
 * This file is part of the backendtea/php-optimizer package.
 * Copyright (c) 2018 Gert de Pagter
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpOptimizer\PhpParser\NodeVisitor;

use PhpOptimizer\Converter\ConverterInterface;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class ConverterVisitor extends NodeVisitorAbstract
{
    /**
     * @var ConverterInterface[]
     */
    private $converters;

    /**
     * @param ConverterInterface|ConverterInterface[] $converter
     */
    public function __construct($converter)
    {
        if (! \is_array($converter)) {
            $converter = [$converter];
        }

        $this->converters = $converter;
    }

    /**
     * @param Node $node
     *
     * @return null|Node|Node[]
     */
    public function leaveNode(Node $node)
    {
        foreach ($this->converters as $converter) {
            if ($converter->shouldConvert($node)) {
                return $converter->convert($node);
            }
        }
    }
}
