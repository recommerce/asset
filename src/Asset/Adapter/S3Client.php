<?php

namespace Recommerce\Asset\Adapter;

use Aws\Common\Client\AwsClientInterface;
use Aws\S3\Enum\CannedAcl;
use Recommerce\Asset\AssetClient;
use Recommerce\Asset\AssetClientInterface;
use Recommerce\Asset\Exception\AssetPutException;
use Recommerce\Asset\Exception\AssetRemoveException;

/**
 * S3Client est une interface utilisant le SDK d'amazone S3Client.
 * Pour plus d'informations, se reporter à la documentation de référence :
 * http://docs.amazonwebservices.com/AWSSDKforPHP/latest/#m=AmazonS3
 *
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
     * @param AwsClientInterface $s3Client
     * @param $bucket
     * @param array $options
     */
    public function __construct(AwsClientInterface $s3Client, $bucket, array $options = [])
    {
        $this->s3Client = $s3Client;
        $this->bucket = $bucket;

        parent::__construct($options);
    }

    /**
     * Récupère la liste de fichiers contenu dans un répertoire
     *
     * @param string $dir
     * @return mixed        false si le répertoire n'existe pas, une liste sinon
     */
    public function getFiles($dir)
    {
        $resultObject = $this->s3Client->listObjects(
            [
                'Bucket' => $this->bucket,
                'Prefix' => $dir
            ]
        );

        return array_map(
            function ($element) {
                return $element['Key'];
            },
            $resultObject->toArray()['Contents']
        );
    }

    /**
     * Récupère un fichier de l'asset et le copie en local
     *
     * @param string $assetFile
     * @param string $localFileDir
     * @param null $toFileName
     * @return string Nom du nouveau fichier en local
     *
     * @param string $assetFile
     * @param string $localFile
     * @return string Nom du nouveau fichier en local
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
     * Copie un fichier dans l'asset
     *
     * @throws \Exception
     * @param string $localFile
     * @param string $assetFile
     * @param boolean $aclPublic
     * @return boolean true
     */
    protected function internalPut($localFile, $assetFile, $aclPublic = true)
    {
        $acl = ($aclPublic)
            ? CannedAcl::PUBLIC_READ
            : CannedAcl::PRIVATE_ACCESS;

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
     * Supprime un fichier sur l'asset
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
     * Vérifie l'existence d'un fichier sur l'asset
     *
     * @param string $assetAssetFile
     * @return boolean
     */
    public function exists($assetAssetFile)
    {
        return $this->s3Client->doesObjectExist($this->bucket, $assetAssetFile);
    }
}
