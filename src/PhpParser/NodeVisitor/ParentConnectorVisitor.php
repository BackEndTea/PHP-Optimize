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

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Copy pasted from infection.
 *
 * @see https://github.com/infection/infection
 */
final class ParentConnectorVisitor extends NodeVisitorAbstract
{
    public const PARENT_KEY = 'parent';

    private $stack;

    public function beforeTraverse(array $nodes): void
    {
        $this->stack = [];
    }

    public function enterNode(Node $node): void
    {
        if (! empty($this->stack)) {
            $node->setAttribute(self::PARENT_KEY, $this->stack[\count($this->stack) - 1]);
        }

        $this->stack[] = $node;
    }

    public function leaveNode(Node $node): void
    {
        \array_pop($this->stack);
    }
}
