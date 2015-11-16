<?php

namespace Recommerce\Asset;

class AssetClientTest extends \PHPUnit_Framework_TestCase
{
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

        $instance = $this->buildInstance(['internalGet']);
        $instance
            ->expects($this->once())
            ->method('internalGet')
            ->willReturn(true);

        $this->assertSame(DIRECTORY_SEPARATOR . $assetFile, $instance->get($assetFile));
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

    private function buildInstance(array $methods = [])
    {
        return $this
            ->getMockBuilder(AssetClient::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMockForAbstractClass();
    }
}
