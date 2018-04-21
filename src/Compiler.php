<?php

/**
 * This file is part of the backendtea/php-optimizer package.
 * Copyright (c) 2018 Gert de Pagter
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpOptimizer;

use Seld\PharUtils\Timestamps;
use Symfony\Component\Finder\Finder;

/**
 * Compiler class creates a PHAR file from this package. Heavily inspired by Composer's Compiler class.
 *
 * @author Loek van der Linde <lind0077@hz.nl>
 */
final class Compiler
{
    public const DEFAULT_PHAR_NAME = 'build/php-optimize.phar';

    /**
     * @var string
     */
    private $versionDate;

    public function compile(string $filename = self::DEFAULT_PHAR_NAME): void
    {
        if (\file_exists($filename)) {
            \unlink($filename);
        }

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->versionDate = $date->format('c');

        $phar = new \Phar($filename, 0, 'php-optimize.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        // PHAR is a stream, so open it
        $phar->startBuffering();

        $finder = new Finder();
        $finder
            ->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            ->in(__DIR__)
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->name('LICENSE')
            ->exclude('Tests')
            ->exclude('tests')
            ->exclude('docs')
            ->in(__DIR__ . '/../vendor/nikic/php-parser/')
            ->in(__DIR__ . '/../vendor/myclabs/deep-copy/')
            ->in(__DIR__ . '/../vendor/symfony/polyfill-mbstring/')
            ->in(__DIR__ . '/../vendor/symfony/console')
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../vendor/autoload.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../vendor/composer/autoload_namespaces.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../vendor/composer/autoload_psr4.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../vendor/composer/autoload_classmap.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../vendor/composer/autoload_files.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../vendor/composer/autoload_real.php'));
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../vendor/composer/autoload_static.php'));
        if (\file_exists(__DIR__ . '/../vendor/composer/include_paths.php')) {
            $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../vendor/composer/include_paths.php'));
        }
        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../vendor/composer/ClassLoader.php'));

        $this->addFile($phar, new \SplFileInfo(__DIR__ . '/../LICENSE'), false);

        $this->addBinExecutable($phar);
        $phar->setStub($this->getStub());

        $phar->stopBuffering();

        unset($phar);

        // re-sign the phar with reproducible timestamp / signature
        $util = new Timestamps($filename);
        $util->updateTimestamps($this->versionDate);
        $util->save($filename, \Phar::SHA1);
    }

    private function addFile(\Phar $phar, \SplFileInfo $file, bool $strip = true): void
    {
        $path = $this->getRelativeFilePath($file);
        $content = \file_get_contents($file->getPathname());

        if ($strip) {
            $content = $this->stripWhitespace($content);
        }

        $phar->addFromString($path, $content);
    }

    private function getRelativeFilePath(\SplFileInfo $file): string
    {
        $realPath = $file->getRealPath();
        $pathPrefix = (\dirname(__DIR__)) . DIRECTORY_SEPARATOR;
        $pos = \strpos($realPath, $pathPrefix);
        $relativePath = ($pos !== false) ? \substr_replace($realPath, '', $pos, \strlen($pathPrefix)) : $realPath;

        return \strtr($relativePath, '\\', '/');
    }

    private function stripWhitespace(string $source): string
    {
        if (! \function_exists('token_get_all')) {
            return $source;
        }
        $output = '';
        foreach (\token_get_all($source) as $token) {
            if (\is_string($token)) {
                $output .= $token;
            } elseif (\in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= \str_repeat("\n", \substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = \preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = \preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = \preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }

        return $output;
    }

    private function addBinExecutable(\Phar $phar): void
    {
        $content = \file_get_contents(__DIR__ . '/../bin/php-optimize');
        $content = \preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/php-optimize', $content);
    }

    /**
     * PHP forces every PHAR to end with a stub.
     *
     * @return string
     */
    private function getStub(): string
    {
        $stub = <<<'EOF'
#!/usr/bin/env php
<?php
Phar::mapPhar('php-optimize.phar');
require 'phar://php-optimize.phar/bin/php-optimize';
__HALT_COMPILER(); ?>
EOF;

        return $stub;
    }
}
