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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RunPhpOptimizer
 * @package PhpOptimizer\Command
 *
 * @author Loek van der Linde <lind0077@hz.nl>
 */
class RunPhpOptimizerCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('php-optimize:run')
            ->setDescription('Run all of the available commands PHP-Optimize has to offer')
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
        // TODO Now we hard-code commands. Auto discovery would be nice
        $this->runConstantToValueCommand($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function runConstantToValueCommand(InputInterface $input, OutputInterface $output): void
    {
        $command = $this->getApplication()->find('php-optimize:constant:tovalue');

        $arguments = [
            'command' => 'php-optimze:constant:tovalue',
            'source_folder' => $input->getArgument('source_folder'),
            'build_folder' => $input->getArgument('build_folder')
        ];

        $constToValueInput = new ArrayInput($arguments);
        try {
            $command->run($constToValueInput, $output);
        } catch (\Throwable $e) {
            // TODO Decide what to do with exceptions
            $output->writeln($e->getFile());
            $output->writeln($e->getMessage());
            $output->writeln($e->getTraceAsString());
        }
    }
}