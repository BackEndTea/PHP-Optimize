<?php

/**
 * This file is part of the backendtea/php-optimizer package.
 * Copyright (c) 2018 Gert de Pagter
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpOptimizer\Test\PhpParser\NodeVisitor;

use PhpOptimizer\PhpParser\NodeVisitor\ParentConnectorVisitor;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class ParentConnectorVisitorTest extends TestCase
{
    /**
     * @dataProvider providesNodesAndParentCombinations
     */
    public function test_it_adds_parents(array $nodes, string $nodeClass, string $parentClass): void
    {
        $traverser = new NodeTraverser();
        $spy = $this->createSpyVisitor($nodeClass);

        $traverser->addVisitor(new ParentConnectorVisitor());
        $traverser->addVisitor($spy);

        $traverser->traverse($nodes);

        $this->assertSame($parentClass, \get_class($spy->parent));
    }

    public function providesNodesAndParentCombinations(): \Generator
    {
        $lexer = new Lexer\Emulative();
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);

        yield 'It adds the class to constants' => [
            $parser->parse(
                <<<'PHP'
<?php

class Foo 
{
    const BAR = 4;
}
PHP
            ),
            Node\Stmt\ClassConst::class,
            Node\Stmt\Class_::class,
        ];
    }

    private function createSpyVisitor(string $nodeClass): NodeVisitorAbstract
    {
        return new class($nodeClass) extends NodeVisitorAbstract {
            private $nodeClassUnderTest;

            public $parent;

            public function __construct($nodeClass)
            {
                $this->nodeClassUnderTest = $nodeClass;
            }

            public function leaveNode(Node $node): void
            {
                if ($node instanceof $this->nodeClassUnderTest) {
                    $this->parent = $node->getAttribute(ParentConnectorVisitor::PARENT_KEY, null);
                }
            }
        };
    }
}
