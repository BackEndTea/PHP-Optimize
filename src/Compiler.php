<?php

namespace PhpOptimizer;

use Seld\PharUtils\Timestamps;
use Symfony\Component\Finder\Finder;

/**
 * Compiler class creates a PHAR file from this package
 *
 * Heavily inspired by Composer's Compiler class.
 */
class Compiler
{

    private $versionDate;

    public function compile(string $filename = 'build/php-optimize.phar')
    {
        // TODO figure out what php compatibility should be, now it's 7+ due to typehint

        if (file_exists($filename)) {
            unlink($filename);
        }

        $this->versionDate = new \DateTime('now', new \DateTimeZone('UTC'));

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
            ->in(__DIR__ . '/')
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
        if (file_exists(__DIR__ . '/../vendor/composer/include_paths.php')) {
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

    /**
     * @param \Phar $phar
     * @param \SplFileInfo $file
     * @param bool $strip
     */
    private function addFile($phar, $file, $strip = true)
    {
        $path = $this->getRelativeFilePath($file);
        $content = file_get_contents($file);

        if ($strip) {
            $content = $this->stripWhitespace($content);
        }

        $phar->addFromString($path, $content);
    }

    /**
     * @param \SplFileInfo $file
     * @return string
     */
    private function getRelativeFilePath($file)
    {
        $realPath = $file->getRealPath();
        $pathPrefix = dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR;
        $pos = strpos($realPath, $pathPrefix);
        $relativePath = ($pos !== false) ? substr_replace($realPath, '', $pos, strlen($pathPrefix)) : $realPath;
        return strtr($relativePath, '\\', '/');
    }

    /**
     * Removes whitespace from PHP source, while preserving line numbers
     *
     * @param string $source
     * @return string
     */
    private function stripWhitespace($source)
    {
        if (!function_exists('token_get_all')) {
            return $source;
        }
        $output = '';
        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $output .= $token;
            } elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
                $output .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                // reduce wide spaces
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                // normalize newlines to \n
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                // trim leading spaces
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);
                $output .= $whitespace;
            } else {
                $output .= $token[1];
            }
        }
        return $output;
    }

    /**
     * @param \Phar $phar
     */
    private function addBinExecutable($phar)
    {
        $content = file_get_contents(__DIR__ . '/../bin/php-optimizer');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/php-optimizer', $content);
    }

    private function getStub()
    {
        $stub = <<<'EOF'
Phar::mapPhar();
EOF;
        return $stub . <<<'EOF'
require 'phar://php-optimizer.phar/bin/php-optimizer';
__HALT_COMPILER();
EOF;
    }
}