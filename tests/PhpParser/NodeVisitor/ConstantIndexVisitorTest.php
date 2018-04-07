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

use PhpOptimizer\PhpParser\NodeVisitor\ConstantIndexVisitor;
use PhpOptimizer\PhpParser\NodeVisitor\ParentConnectorVisitor;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

final class ConstantIndexVisitorTest extends TestCase
{
    /**
     * @dataProvider providesNodesAndConstants
     *
     * @param array $nodes
     * @param array $expectedConstants Key-value pair of FQN const and its value
     */
    public function test_it_gathers_all_constants_within_the_code(array $nodes, array $expectedConstants): void
    {
        $traverser = $this->createTraverser();
        $constantsVisitor = new ConstantIndexVisitor();
        $traverser->addVisitor($constantsVisitor);
        $traverser->traverse($nodes);

        $realConstants = $constantsVisitor->getConstants();

        foreach ($expectedConstants as $constant => $value) {
            $this->assertArrayHasKey(
                $constant,
                $realConstants,
                'Expected a constant ' . $constant . ' but it wasn\'t present in the found constants.'
            );

            //This logic is only needed for tests to make sure we have the correct values, normally we just keep the entire value of the code
            if ($realConstants[$constant] instanceof Node\Scalar) {
                $this->assertSame($value, $realConstants[$constant]->value);
            } elseif ($realConstants[$constant] instanceof Node\Expr\ConstFetch) {
                $this->assertSame($value, \constant((string) $realConstants[$constant]->name));
            } elseif ($realConstants[$constant] instanceof Node\Expr\Array_) {
                //Logic to The node representation of arrays with real arrays is too much for now
                //TODO: Add proper test to make sure arrays are handled.
            }
        }

        $this->assertSameSize(
            $expectedConstants,
            $realConstants,
            'All expected constants are present, but more constants were found.'
        );
    }

    public function providesNodesAndConstants(): \Generator
    {
        $lexer = new Lexer\Emulative();
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);

        yield 'It correctly sets a single constant within a namespaced class' => [
          $parser->parse(
              <<<'PHP'
<?php

namespace Example\Name;

class ExampleClass 
{
    const FIVE = 5;
}
PHP
          ),
            ['Example\Name\ExampleClass\FIVE' => 5],
        ];

        yield 'It correctly sets a single constant within a non namespaced class' => [
            $parser->parse(
                <<<'PHP'
<?php

class ExampleClass 
{
    const FIVE = 5;
}
PHP
            ),
            ['ExampleClass\FIVE' => 5],
        ];

        yield 'It stays empty when no constants are found' => [
            $parser->parse(
                <<<'PHP'
<?php

class ExampleClass 
{
}
PHP
            ),
            [],
        ];

        yield 'It correctly sets different types of constant within a namespaced class' => [
            $parser->parse(
                <<<'PHP'
<?php

class ExampleClass 
{
    const FIVE = 5;
    const HELLO = 'hello';
    const FOO = 3.5;
    const REAL = true;
    const EMPTY = null;
    const MULTIPLE = [4, 5, 'test'];
}
PHP
            ),
            [
                'ExampleClass\FIVE' => 5,
                'ExampleClass\HELLO' => 'hello',
                'ExampleClass\FOO' => 3.5,
                'ExampleClass\REAL' => true,
                'ExampleClass\EMPTY' => null,
                'ExampleClass\MULTIPLE' => [4, 5, 'test'],
            ],
        ];

        yield 'It does not index usage of constants' => [
            $parser->parse(
                <<<'PHP'
<?php

class ExampleClass 
{
    private function foo() { return Bar::FAKE_CONST;}
}
PHP
            ),
            [],
        ];

        yield 'It correctly deals with multiple classes' => [
            $parser->parse(
                <<<'PHP'
<?php

namespace Example\Name;

class ExampleClass 
{
    const FIVE = 5;
}

class SecondClass
{
    const SIX = '6';
}
PHP
            ),
            [
                'Example\Name\ExampleClass\FIVE' => 5,
                'Example\Name\SecondClass\SIX' => '6',
            ],
        ];
    }

    private function createTraverser(): NodeTraverser
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor(new ParentConnectorVisitor());

        return $traverser;
    }
}
