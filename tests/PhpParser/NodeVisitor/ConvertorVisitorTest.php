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

use PhpOptimizer\Converter\ConstToValueConverter;
use PhpOptimizer\PhpParser\NodeVisitor\ConverterVisitor;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class ConvertorVisitorTest extends TestCase
{
    /**
     * @dataProvider providesConvertableCode
     *
     * @param array  $constants
     * @param array  $inputNodes
     * @param string $expectedOutputCode
     */
    public function test_it_correctly_converts_constants(array $constants, array $inputNodes, string $expectedOutputCode): void
    {
        $convertor = new ConverterVisitor(new ConstToValueConverter($constants));
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($convertor);
        $traverser->traverse($inputNodes);

        $printer = new Standard();
        $output = $printer->prettyPrintFile($inputNodes);

        $this->assertSame($expectedOutputCode, $output);
    }

    public function providesConvertableCode(): \Generator
    {
        $lexer = new Lexer\Emulative();
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);

        yield 'It converts the use of a fully classified const' => [
            ['Foo\Bar\Baz' => new Node\Scalar\String_('A value')],
            $parser->parse(
                <<<'PHP'
<?php

return Foo\Bar::Baz;
PHP
            ),
        <<<'PHP'
<?php

return 'A value';
PHP
        ];

        yield 'It converts the use of a fully classified const within a class' => [
            ['Foo\Bar\Baz' => new Node\Scalar\String_('A value')],
            $parser->parse(
                <<<'PHP'
<?php

namespace Bar;

class Abc
{
    public function hello () { return \Foo\Bar::Baz;}
}
PHP
            ),
            <<<'PHP'
<?php

namespace Bar;

class Abc
{
    public function hello()
    {
        return 'A value';
    }
}
PHP
        ];

        yield 'It converts the use of a non fully classified const within a class' => [
            ['Foo\Bar\Baz' => new Node\Scalar\LNumber(3)],
            $parser->parse(
                <<<'PHP'
<?php

namespace Bar;

use Foo\Bar;

class Abc
{
    public function hello () { return Bar::Baz;}
}
PHP
            ),
            <<<'PHP'
<?php

namespace Bar;

use Foo\Bar;
class Abc
{
    public function hello()
    {
        return 3;
    }
}
PHP
        ];

        yield 'It converts the use of a non fully classified const within a class in the global namespace' => [
            ['Foo\Bar\Baz' => new Node\Scalar\LNumber(3)],
            $parser->parse(
                <<<'PHP'
<?php

use Foo\Bar;

class Abc
{
    public function hello () { return Bar::Baz;}
}
PHP
            ),
            <<<'PHP'
<?php

use Foo\Bar;
class Abc
{
    public function hello()
    {
        return 3;
    }
}
PHP
        ];
    }
}
