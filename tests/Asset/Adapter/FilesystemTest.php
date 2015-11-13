<?php

namespace Recommerce\Asset\Adapter;

class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FilesystemClient
     */
    private $instance;

    private $localDir = '/tmp';

    private $assetRepository = '/tmp/repository';

    private $relativeAssetDir = 'test';

    private $assetFiles = [];

    private $file1 = 'a.txt';

    private $file2 = 'b.png';

    private $localFile;

    public function setUp()
    {
        $localFile = 'localfile.txt';

        $this->assetFiles = [
            $this->relativeAssetDir . DIRECTORY_SEPARATOR . $this->file1,
            $this->relativeAssetDir . DIRECTORY_SEPARATOR . $this->file2
        ];

        $assetFullDir = $this->assetRepository . DIRECTORY_SEPARATOR . $this->relativeAssetDir;

        is_dir($assetFullDir) && $this->rmTree($assetFullDir);

        if (!mkdir($assetFullDir, 0755, true)) {
            throw new \Exception("Unable to create test directory");
        }

        $filesToCreate = $this->assetFiles;
        $filesToCreate[] = $localFile;

        foreach ($filesToCreate as $file) {
            $this->createFile($this->assetRepository . DIRECTORY_SEPARATOR . $file);
        }

        $this->localFile = $this->localDir . DIRECTORY_SEPARATOR . $localFile;
        $this->createFile($this->localFile);
        $this->instance = new FilesystemClient($this->assetRepository);
    }

    public function testFileExists()
    {
        $existingFile = $this->relativeAssetDir . DIRECTORY_SEPARATOR . $this->file1;

        $this->assertTrue($this->instance->exists($existingFile));
        $this->assertFalse($this->instance->exists('gnagnagna'));
    }

    /**
     * @expectedException \Recommerce\Asset\Exception\AssetMoveException
     */
    public function testMoveNonExistingFile()
    {
        $this
            ->instance
            ->move('non_existing_file.txt', $this->relativeAssetDir);
    }

    public function testMove()
    {
        $assetFile = $this->assetFiles[0];

        $this->assertSame(
            'anotherdir' . DS . $this->file1,
            $this->instance->move($assetFile, 'anotherdir')
        );
    }

    public function testGetFiles()
    {
        $this->assertSame(
            $this->assetFiles,
            $this->instance->getFiles($this->relativeAssetDir)
        );
    }

    public function testGetFile()
    {
        $uploadedFile = $this->instance->get(
            $this->relativeAssetDir . DIRECTORY_SEPARATOR . $this->file1
        );

        $this->assertFileExists($uploadedFile);
    }

    public function testPutFile()
    {
        $this->assertTrue(
            $this->instance->put(
                $this->localFile,
                $this->relativeAssetDir . DIRECTORY_SEPARATOR . basename(
                    $this->localFile
                )
            )
        );
    }

    public function testDeleteFile()
    {
        $this->assertTrue(
            $this->instance->remove(
                $this->relativeAssetDir . DIRECTORY_SEPARATOR . $this->file1
            )
        );
    }

    private function createFile($file) {
        if (!touch($file)) {
            throw new \Exception("Unable to create test file");
        }
    }

    private function rmTree($dir)
    {
        $files = array_diff(
            scandir($dir),
            [
                '.',
                '..'
            ]
        );

        foreach ($files as $file) {
            (is_dir("$dir/$file") && !is_link($dir))
                ? delTree("$dir/$file")
                : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}
