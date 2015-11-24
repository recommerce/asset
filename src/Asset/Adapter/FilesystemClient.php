<?php

namespace Recommerce\Asset\Adapter;

use Recommerce\Asset\AssetClient;
use Recommerce\Asset\AssetClientInterface;
use Recommerce\Asset\Exception\AssetPutException;

/**
 * @see Asset
 * @package AssetClient.FilesystemClient
 */
class FilesystemClient extends AssetClient implements AssetClientInterface
{

    /**
     * @var string
     */
    protected $repository;

    /**
     * @param string $repository
     * @param array $options
     */
    public function __construct($repository, array $options = [])
    {
        $this->repository = $repository;

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception Le paramètre ne correspond pas à un répertoire valide
     * @param string $assetAssetDir
     * @return array file list
     */
    protected function internalGetFiles($assetAssetDir)
    {
        $files = [];
        $fulldir = $this->repository . DS . $assetAssetDir;

        if (!is_dir($fulldir)) {
            throw new \Exception(
                sprintf("'%s' is not an existing directory.", $fulldir)
            );
        }

        foreach (scandir($fulldir) as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            $files[] = $assetAssetDir . DS . $file;
        }
        return $files;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     * @param string $assetFile
     * @param string $localFile
     * @return bool
     */
    protected function internalGet($assetFile, $localFile)
    {
        return copy(
            $this->repository . DS . $assetFile,
            $localFile
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param string $localFile
     * @param string $assetFile
     * @return boolean
     * @throws AssetPutException
     */
    protected function internalPut($localFile, $assetFile)
    {
        $assetFile = $this->repository . DS . $assetFile;
        $dir = dirname($assetFile);

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new AssetPutException(
                sprintf("Unable to create asset directory '%s'", $dir)
            );
        }

        return copy($localFile, $assetFile);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetAssetFile
     * @return boolean
     */
    protected function internalRemove($assetAssetFile)
    {
        return unlink($this->repository . DS . $assetAssetFile);
    }

    /**
     * Vérifie l'existence d'un fichier sur l'asset
     *
     * @param string $assetFile
     * @return boolean
     */
    public function exists($assetFile)
    {
        return is_file($this->repository . DS . $assetFile);
    }
}
