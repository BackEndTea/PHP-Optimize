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

final class ConstantIndexVisitor extends NodeVisitorAbstract
{
    /**
     * @var Node\Expr[]
     */
    private $constants = [];

    public function leaveNode(Node $node): void
    {
        if (! $node instanceof Node\Stmt\ClassConst) {
            return;
        }
        $namespace = $this->getCurrentFqn($node);

        foreach ($node->consts as $const) {
            $this->constants[$namespace . $const->name] = $const->value;
        }
    }

    public function getConstants(): array
    {
        return $this->constants;
    }

    private function getCurrentFqn(Node $node): string
    {
        $parent = $node->getAttribute(ParentConnectorVisitor::PARENT_KEY);
        while ($parent) {
            if (isset($parent->namespacedName)) {
                return $parent->namespacedName . '\\';
            }
            $parent = $parent->getAttribute(ParentConnectorVisitor::PARENT_KEY);
        }

        return '';
    }
}
