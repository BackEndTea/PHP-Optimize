<?php

/**
 * This file is part of the backendtea/php-optimizer package.
 * Copyright (c) 2018 Gert de Pagter
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

class PharContext implements Context
{
    public function __construct()
    {
        $readonlyValue = \ini_get('phar.readonly');
        if ($readonlyValue === '1') {
            throw new Exception('Can\'t test phar creation if `phar.readonly` is set to false in your ini');
        }
    }

    /**
     * @Given the :arg1 folder does not contain :arg2
     */
    public function theFolderDoesNotContain($folder, $file): void
    {
        Assert::assertDirectoryExists($folder);
        $path = $folder . DIRECTORY_SEPARATOR . $file;
        if (\file_exists($path)) {
            \unlink($path);
        }
    }

    /**
     * @When i run the shell command :arg1
     */
    public function iRunTheShellCommand($shellcommand): void
    {
        $ref = null;
        \exec($shellcommand, $ref, $exitCode);
        unset($ref);
        Assert::assertSame(0, $exitCode);
    }

    /**
     * @Then i should see the file :arg1 in the :arg2 folder
     */
    public function iShouldSeeTheFileInTheFolder($file, $folder): void
    {
        Assert::assertFileExists($folder . DIRECTORY_SEPARATOR . $file);
    }
}
