<?php

namespace Recommerce\Asset;

class AssetClientTest extends \PHPUnit_Framework_TestCase
{
    private $rootUrl = 'http://recommerce.com';

    private function buildInstance(array $methods = [])
    {
        return $this
            ->getMockBuilder(AssetClient::class)
            ->setConstructorArgs([['rootUrl' => $this->rootUrl]])
            ->setMethods($methods)
            ->getMockForAbstractClass();
    }

    public function testGetUrl()
    {
        $assetFile = 'assetFile.txt';
        $expectedUrl = $this->rootUrl . '/' . $assetFile;

        $this->assertSame($expectedUrl, $this->buildInstance()->getUrl($assetFile));
    }

    /**
     * @expectedException \Exception
     */
    public function testGetUrlWithoutRoot()
    {
        $assetFile = 'assetFile.txt';

        $this
            ->getMockBuilder(AssetClient::class)
            ->getMockForAbstractClass()
            ->getUrl($assetFile);
    }

    public function testPut()
    {
        $localFile = '/tmp/anyfile.txt';
        $assetFile = 'anydir/assetfilename.txt';

        $instance = $this->buildInstance(['internalPut']);
        $instance
            ->expects($this->once())
            ->method('internalPut')
            ->with(
                $this->equalTo($localFile),
                $this->equalTo($assetFile)
            )
            ->willReturn(true);

        $this->assertTrue($instance->put($localFile, $assetFile));
    }

    /**
     * @expectedException \Recommerce\Asset\Exception\AssetPutException
     */
    public function testPutException()
    {
        $localFile = '/tmp/anyfile.txt';
        $assetFile = 'anydir/assetfilename.txt';

        $instance = $this->buildInstance(['internalPut']);
        $instance
            ->expects($this->once())
            ->method('internalPut')
            ->willReturn(false);

        $instance->put($localFile, $assetFile);
    }

    public function testGet()
    {
        $assetFile = 'anyfile.txt';
        $localFile = '/tmp/anyfile.txt';

        $instance = $this->buildInstance(['internalGet']);
        $instance
            ->expects($this->once())
            ->method('internalGet')
            ->willReturn(true);

        $this->assertSame($localFile, $instance->get($assetFile));
    }

    public function testGetWithLocalfile()
    {
        $assetFile = 'anyfile.txt';
        $localFile = '/tmp/newfilename.txt';

        $instance = $this->buildInstance(['internalGet']);
        $instance
            ->expects($this->once())
            ->method('internalGet')
            ->willReturn(true);

        $this->assertSame($localFile, $instance->get($assetFile, $localFile));
    }

    /**
     * @expectedException \Recommerce\Asset\Exception\AssetGetException
     */
    public function testGetWithTransferError()
    {
        $assetFile = 'anyfile.txt';
        $localFile = '/tmp/newfilename.txt';

        $instance = $this->buildInstance(['internalGet']);
        $instance
            ->expects($this->once())
            ->method('internalGet')
            ->willReturn(false);

        $this->assertSame($localFile, $instance->get($assetFile, $localFile));
    }

    public function testMove()
    {
        $oldAssetFile = 'anyfile.txt';
        $destAssetDir = 'dir';
        $newAssetFile = $destAssetDir . DIRECTORY_SEPARATOR . $oldAssetFile;

        $instance = $this->buildInstance(['exists', 'internalMove']);
        $instance
            ->method('exists')
            ->will($this->returnValueMap([
                [$oldAssetFile, true],
                [$newAssetFile, false]
            ]));

        $instance
            ->expects($this->once())
            ->method('internalMove')
            ->will($this->returnArgument(1));

        $this->assertSame(
            $newAssetFile,
            $instance->move($oldAssetFile, $destAssetDir)
        );
    }

    /**
     * @expectedException \Recommerce\Asset\Exception\AssetMoveException
     */
    public function testMoveNonExistingAssetFile()
    {
        $oldAssetFile = 'anyfile.txt';
        $destAssetDir = 'dir';

        $instance = $this->buildInstance(['exists']);
        $instance
            ->method('exists')
            ->will($this->returnValueMap([
                [$oldAssetFile, false]
            ]));

        $instance->move($oldAssetFile, $destAssetDir);
    }

    /**
     * @expectedException \Recommerce\Asset\Exception\AssetMoveException
     */
    public function testMoveExistingNewAssetFileException()
    {
        $oldAssetFile = 'anyfile.txt';
        $destAssetDir = 'dir';
        $newAssetFile = $destAssetDir . DIRECTORY_SEPARATOR . $oldAssetFile;

        $instance = $this->buildInstance(['exists', 'internalMove']);
        $instance
            ->expects($this->exactly(2))
            ->method('exists')
            ->will($this->returnValueMap([
                [$oldAssetFile, true],
                [$newAssetFile, true]
            ]));

        $instance->move($oldAssetFile, $destAssetDir);
    }

    public function testMoveExistingNewAssetFileRemove()
    {
        $oldAssetFile = 'anyfile.txt';
        $destAssetDir = 'dir';
        $newAssetFile = $destAssetDir . DIRECTORY_SEPARATOR . $oldAssetFile;

        $instance = $this->buildInstance(['exists', 'internalMove', 'remove']);
        $instance
            ->expects($this->exactly(2))
            ->method('exists')
            ->will($this->returnValueMap([
                [$oldAssetFile, true],
                [$newAssetFile, true]
            ]));

        $instance
            ->expects($this->once())
            ->method('remove')
            ->with($this->equalTo($newAssetFile));

        $instance
            ->expects($this->once())
            ->method('internalMove')
            ->will($this->returnArgument(1));

        $this->assertSame(
            $newAssetFile,
            $instance->move($oldAssetFile, $destAssetDir, AssetClientInterface::OVERWRITE)
        );
    }

    public function testListFiles()
    {
        $dir = 'someDir';

        $files = [
            'anyFile1.txt',
            'anyFile2.xml'
        ];

        $instance = $this->buildInstance(['internalGetFiles']);
        $instance
            ->method('internalGetFiles')
            ->with($this->equalTo($dir))
            ->willReturn($files);

        $this->assertSame(
            $files,
            $instance->listFiles('someDir')
        );
    }

    public function testListFilesWithPattern()
    {
        $dir = 'someDir';
        $pattern = 'File1';

        $files = [
            'someDir/anyFile1.txt',
            'someDir/anyFile2.xml'
        ];

        $instance = $this->buildInstance(['internalGetFiles']);
        $instance
            ->method('internalGetFiles')
            ->with($this->equalTo($dir))
            ->willReturn($files);

        $this->assertSame(
            ['someDir/anyFile1.txt'],
            $instance->listFiles('someDir', $pattern)
        );
    }

    public function testRemoveExistingFile()
    {
        $assetFile = 'anyFile1.txt';

        $instance = $this->buildInstance(['exists', 'internalRemove']);
        $instance
            ->expects($this->once())
            ->method('exists')
            ->with($this->equalTo($assetFile))
            ->willReturn(false);

        $this->assertTrue($instance->remove($assetFile));
    }

    public function testRemoveNonExistingFile()
    {
        $assetFile = 'anyFile1.txt';
        $internalRemoveReturn = true;

        $instance = $this->buildInstance(['exists', 'internalRemove']);
        $instance
            ->expects($this->once())
            ->method('exists')
            ->with($this->equalTo($assetFile))
            ->willReturn(true);
        $instance
            ->expects($this->once())
            ->method('internalRemove')
            ->with($this->equalTo($assetFile))
            ->willReturn($internalRemoveReturn);

        $this->assertSame($internalRemoveReturn, $instance->remove($assetFile));
    }

    public function testRemoveFiles()
    {
        $assetFiles = [
            'assetFile1.txt',
            'assetFile2.xml'
        ];

        $instance = $this->buildInstance(['remove']);
        $instance
            ->expects($this->exactly(count($assetFiles)))
            ->method('remove')
            ->withConsecutive(
                ['assetFile1.txt'],
                ['assetFile2.xml']
            );

        $this->assertTrue($instance->removeFiles($assetFiles));
    }

    public function testExists()
    {
        $assetFile = 'mydir/anyFile1.txt';

        $assetFiles = [
            'mydir/anyFile1.txt',
            'mydir/anyFile2.xml'
        ];

        $instance = $this->buildInstance(['getFiles']);
        $instance
            ->expects($this->once())
            ->method('getFiles')
            ->with($this->equalTo('mydir'))
            ->willReturn($assetFiles);

        $this->assertTrue($instance->exists($assetFile));
    }

    public function testDoesNotExist()
    {
        $assetFile = 'mydir/anyFile3.txt';

        $assetFiles = [
            'mydir/anyFile1.txt',
            'mydir/anyFile2.xml'
        ];

        $instance = $this->buildInstance(['getFiles']);
        $instance
            ->expects($this->once())
            ->method('getFiles')
            ->with($this->equalTo('mydir'))
            ->willReturn($assetFiles);

        $this->assertFalse($instance->exists($assetFile));
    }

    /**
     * @dataProvider getFilesAssetDirs
     */
    public function testGetFiles($assetDir, $expectedParameter)
    {
        $fileList = [
            'anyFile1.txt',
            'anyFile2.xml'
        ];

        $instance = $this->buildInstance(['internalGetFiles']);
        $instance
            ->expects($this->once())
            ->method('internalGetFiles')
            ->with($this->equalTo($expectedParameter))
            ->willReturn($fileList);

        $this->assertSame($fileList, $instance->getFiles($assetDir));
    }

    public function getFilesAssetDirs()
    {
        return [
            ['.', ''],
            ['someDir', 'someDir']
        ];
    }
}
