<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    /**
     * @var string
     */
    private $assetFileRoot = '/tmp/asset';

    /**
     * @var mixed
     */
    private $output;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given an empty asset
     */
    public function anEmptyAsset()
    {
        if (is_dir($this->assetFileRoot)) {
            $this->recursiveRmDir($this->assetFileRoot);
        }

        if (!mkdir($this->assetFileRoot, 0755, true)) {
            throw new PendingException(sprintf(
                "Directory %s does not exist et cannot be created",
                $this->assetFileRoot
            ));
        }
    }

    private function recursiveRmDir($dir)
    {
        $files = array_diff(scandir($dir), array('.','..'));

        foreach ($files as $file) {
            (is_dir("$dir/$file"))
                ? $this->recursiveRmDir("$dir/$file")
                : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * @Given repository :arg1 exists
     */
    public function repositoryExists($dir)
    {
        $fullDirectory = $this->assetFileRoot . DIRECTORY_SEPARATOR . $dir;

        if (!is_dir($fullDirectory)) {
            if (!mkdir($fullDirectory, 0755, true)) {
                throw new PendingException(sprintf(
                    "Directory %s does not exist et cannot be created",
                    $fullDirectory
                ));
            }
        }
    }

    /**
     * @Given repository :arg1 does not exists
     */
    public function repositoryDoesNotExists($dir)
    {
        $fullDirectory = $this->assetFileRoot . DIRECTORY_SEPARATOR . $dir;

        if (is_dir($fullDirectory) && !rmdir($fullDirectory)) {
            throw new PendingException(sprintf(
                "Directory %s exists and cannot be removed",
                $fullDirectory
            ));
        }
    }

    /**
     * @Given I have a file :arg1
     */
    public function iHaveAFile($file)
    {
        $fileFullName = $this->assetFileRoot . DIRECTORY_SEPARATOR . $file;

        if (!is_file($fileFullName)) {
            if (!touch($fileFullName)) {
                throw new PendingException(sprintf("Cannot create file %s", $fileFullName));
            }
        }
    }

    /**
     * @When I list :arg1
     */
    public function iList($dir)
    {
        $assetClient = new \Recommerce\Asset\Adapter\FilesystemClient($this->assetFileRoot);

        try {
            $this->output = $assetClient->listFiles($dir);
        } catch (\Exception $e) {
            $this->output = $e;
        }
    }

    /**
     * @When I list :arg1 with pattern :arg2
     */
    public function iListWithPattern($dir, $pattern)
    {
        $assetClient = new \Recommerce\Asset\Adapter\FilesystemClient($this->assetFileRoot);

        try {
            $this->output = $assetClient->listFiles($dir, $pattern);
        } catch (\Exception $e) {
            $this->output = $e;
        }
    }

    /**
     * @Then I should get:
     */
    public function iShouldGet(PyStringNode $expected)
    {
        if ($this->output !== $expected->getStrings()) {
            throw new PendingException(sprintf(
                "Result does not match expected result : %s - %s",
                implode(', ', $this->output),
                implode(', ', $expected->getStrings())
            ));
        }
    }

    /**
     * @Then I should get Exception :arg1
     */
    public function iShouldGetException($expectedExceptionClass)
    {
        if (!is_a($this->output, $expectedExceptionClass)) {
            throw new PendingException(sprintf(
                "Result does not match expected exception : %s - %s",
                get_class($this->output),
                $expectedExceptionClass
            ));
        }
    }
}
