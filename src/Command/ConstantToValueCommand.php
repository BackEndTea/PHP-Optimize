<?php

/**
 * This file is part of the backendtea/php-optimizer package.
 * Copyright (c) 2018 Gert de Pagter
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpOptimizer\Command;

use PhpOptimizer\Converter\ConstToValueConverter;
use PhpOptimizer\PhpParser\NodeVisitor\ConstantIndexVisitor;
use PhpOptimizer\PhpParser\NodeVisitor\ConverterVisitor;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as StandardPrettyPrinter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ConstantToValue
 * @package PhpOptimizer\Command
 *
 * @author Loek van der Linde <lind0077@hz.nl>
 */
class ConstantToValueCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('php-optimize:constant:tovalue')
            ->setDescription('Converts all constants to their value')
            ->addArgument(
                'source_folder',
                InputArgument::REQUIRED,
                'Folder in which to run PHP-Optimize'
            )
            ->addArgument(
                'build_folder',
                InputArgument::REQUIRED,
                'Folder in which to place optimized files'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directoryToScan = getcwd() . '/' . $input->getArgument('source_folder');
        $directoryToBuild = getcwd() . '/' . $input->getArgument('build_folder');

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $traverser = new NodeTraverser();

        $constantsVisitor = new ConstantIndexVisitor();

        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor($constantsVisitor);

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directoryToScan));
        $files = new \RegexIterator($files, '/\.php$/');

        foreach ($files as $file) {
            try {
                $code = file_get_contents($file->getPathName());
                $stmts = $parser->parse($code);
                $traverser->traverse($stmts);

                var_dump($file->getPathName());
                var_dump($constantsVisitor->getConstants());
            } catch (Error $e) {
                echo 'Parse Error: ', $e->getMessage();
            }
        }

        $constants = $constantsVisitor->getConstants();

        var_dump($constants);

        $convertor = new ConverterVisitor(new ConstToValueConverter($constants));
        $traverser->addVisitor($convertor);

        $prettyPrinter = new StandardPrettyPrinter();

        // TODO Now we're looping twice, this should be easier to do

        foreach ($files as $file) {
            try {
                $code = file_get_contents($file->getPathName());
                $stmts = $parser->parse($code);
                $stmts = $traverser->traverse($stmts);

                $code = $prettyPrinter->prettyPrintFile($stmts);

                file_put_contents(
                    substr_replace(
                        $file->getPathname(),
                        $directoryToBuild,
                        0,
                        strlen($directoryToScan)
                    ),
                    $code
                );
            } catch (Error $e) {
                echo 'Parse Error: ', $e->getMessage();
            }
        }
    }
}