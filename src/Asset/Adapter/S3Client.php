<?php

namespace Recommerce\Asset\Adapter;

use Aws\Common\Client\AwsClientInterface;
use Aws\S3\Enum\CannedAcl;
use Recommerce\Asset\AssetClient;
use Recommerce\Asset\AssetClientInterface;
use Recommerce\Asset\Exception\AssetPutException;
use Recommerce\Asset\Exception\AssetRemoveException;

/**
 * S3Client uses AwsS3Client SDK.
 *
 * @see http://docs.amazonwebservices.com/AWSSDKforPHP/latest/#m=AmazonS3
 * @see Asset
 * @package AssetClient.S3Client
 */
class S3Client extends AssetClient implements AssetClientInterface
{

    /**
     * @var AwsClientInterface
     */
    protected $s3Client;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var boolean
     */
    protected $isPrivate;

    /**
     * @param AwsClientInterface $s3Client
     * @param string $bucket
     * @param array $options
     */
    public function __construct(AwsClientInterface $s3Client, $bucket, array $options = [])
    {
        $this->s3Client = $s3Client;
        $this->bucket = $bucket;

        $this->isPrivate = (isset($options['isPrivate']))
            ? $options['isPrivate']
            : false;

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetDir
     * @return array $fileList
     */
    protected function internalGetFiles($dir)
    {
        $resultObject = $this->s3Client->listObjects(
            [
                'Bucket' => $this->bucket,
                'Prefix' => $dir
            ]
        );

        $fileList = [];
        if (isset($resultObject->toArray()['Contents'])) {
            $fileList = array_map(
                function ($element) {
                    return $element['Key'];
                },
                $resultObject->toArray()['Contents']
            );
        }

        return $fileList;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetFile
     * @param string $localFile
     * @return string
     * @throws AssetPutException
     */
    protected function internalGet($assetFile, $localFile)
    {
        try {
            $this->s3Client->getObject(
                [
                    'Bucket' => $this->bucket,
                    'Key' => $assetFile,
                    'SaveAs' => $localFile
                ]
            );
        } catch (\Exception $e) {
            throw new AssetPutException("An error occurs", 0, $e);
        }

        return $localFile;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     * @param string $localFile
     * @param string $assetFile
     * @param boolean $aclPublic
     * @return boolean true
     */
    protected function internalPut($localFile, $assetFile)
    {
        $acl = ($this->isPrivate)
            ? CannedAcl::PRIVATE_ACCESS
            : CannedAcl::PUBLIC_READ;

        $this->s3Client->putObject(
            [
                'Bucket' => $this->bucket,
                'Key' => $assetFile,
                'SourceFile' => $localFile,
                'ACL' => $acl
            ]
        );

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetAssetFile
     * @return boolean
     * @throws AssetRemoveException
     */
    protected function internalRemove($assetAssetFile)
    {
        try {
            $this->s3Client->deleteObject(
                [
                    'Bucket' => $this->bucket,
                    'Key' => $assetAssetFile
                ]
            );
        } catch (\Exception $e) {
            throw new AssetRemoveException("An error occurs", 0, $e);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetAssetFile
     * @return boolean
     */
    public function exists($assetAssetFile)
    {
        return $this->s3Client->doesObjectExist($this->bucket, $assetAssetFile);
    }
}
