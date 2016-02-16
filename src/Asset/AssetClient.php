<?php

namespace Recommerce\Asset;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

use Recommerce\Asset\Exception\AssetGetException;
use Recommerce\Asset\Exception\AssetMoveException;
use Recommerce\Asset\Exception\AssetPutException;

/**
 * AssetClient est une interface permettant le stockage de fichiers à des fins d'accès via
 * le protocole HTTP.
 * Il fournit les fonctions classiques de gestion de fichier.
 *
 * @group functional_testing
 * @package AssetClient
 */
abstract class AssetClient implements AssetClientInterface
{

    /**
     * Directory where files are temporarily stocked to manage files transfer
     *
     * @var string
     */
    protected $tmpDir;

    /**
     * Asset repository url
     *
     * @var string
     */
    protected $rootUrl;

    /**
     * List of errors
     *
     * @var array
     */
    private $errors = [];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->tmpDir = (isset($options['tmpDir']))
            ? $options['tmpDir']
            : '/tmp';

        $this->rootUrl = (isset($options['rootUrl']))
            ? $options['rootUrl']
            : '';
    }

    /**
     * @return string
     */
    public function getRootUrl()
    {
        return $this->rootUrl;
    }

    /**
     * Get file access URI
     *
     * @param string $assetFile
     * @return string $url
     * @throws \Exception
     */
    public function getUrl($assetFile)
    {
        $assetRoot = $this->getRootUrl();

        if (empty($assetRoot)) {
            throw new \Exception(
                "Le paramètre de configuration 'url.assets.root' n'est pas défini."
            );
        }
        return $assetRoot . '/' . $assetFile;
    }

    /**
     * @param string $oldAssetFile
     * @param string $destAssetDir
     * @param int $behaviorIfDestExists
     * @return string
     * @throws AssetMoveException
     */
    public function move($oldAssetFile, $destAssetDir, $behaviorIfDestExists = self::THROW_EXCEPTION)
    {
        if (!$this->exists($oldAssetFile)) {
            throw new AssetMoveException(sprintf(
                "Asset file '%s' does not exist",
                $oldAssetFile
            ));
        }

        $newAssetFile = $destAssetDir . DS . basename($oldAssetFile);

        if ($this->exists($newAssetFile)) {
            if (($behaviorIfDestExists === self::THROW_EXCEPTION)) {
                throw new AssetMoveException(sprintf(
                    "Destination file '%s' already exists",
                    $newAssetFile
                ));
            }
            $this->remove($newAssetFile);
        }

        return $this->internalMove($oldAssetFile, $newAssetFile);
    }

    /**
     * Beware, download file for uploading it with a new name. Exists for convenience,
     * override it for performance.
     *
     * @param string $oldAssetFile
     * @param string $newAssetFile
     * @return string
     */
    protected function internalMove($oldAssetFile, $newAssetFile)
    {
        $localFile = $this->get($oldAssetFile);
        $this->put($localFile, $newAssetFile);
        $this->remove($oldAssetFile);

        return $newAssetFile;
    }

    /**
     * Put given local file on asset
     *
     * @param string $localFile
     * @param string $assetFile
     * @param bool $toDelete
     * @return bool true
     * @throws AssetPutException
     */
    public function put($localFile, $assetFile, $toDelete = false)
    {
        if ('http' === substr($localFile, 0, 4)) {
            $localFile = $this->uploadRemoteFile($localFile, $assetFile);
            $toDelete = true;
        }

        if (!$this->internalPut($localFile, $assetFile)) {
            throw new AssetPutException(
                sprintf("Unable to put local file %s on asset %s", $localFile, $assetFile)
            );
        }

        if ($toDelete) {
            unlink($localFile);
        }
        return true;
    }

    /**
     * @param string $localfile
     * @param string $assetFile
     * @return string
     * @throws AssetPutException
     */
    protected function uploadRemoteFile($localfile, $assetFile)
    {
        $tmpName = str_replace(['/', '\\'], '', $assetFile);
        $tmpFile = $this->tmpDir . DS . $tmpName;

        if (!copy($localfile, $tmpFile)) {
            throw new AssetPutException(
                sprintf(
                    "Unable to copy remote file '%s' to '%s'.",
                    $localfile,
                    $tmpFile
                )
            );
        }
        return $tmpFile;
    }

    /**
     * Get asset file and copy it on local system
     *
     * @param string $assetFile
     * @param string $localFile
     * @return string New local file name
     * @throws AssetGetException
     */
    public function get($assetFile, $localFile = null)
    {
        if (null === $localFile) {
            $localFile = $this->tmpDir . DS . $assetFile;
        }

        $destinationDir = dirname($localFile);

        if (!is_dir($destinationDir) && !mkdir($destinationDir, 0755, true)) {
            throw new AssetGetException(
                sprintf(
                    "Cannot create destination directory %s",
                    $destinationDir
                )
            );
        }

        if (!$this->internalGet($assetFile, $localFile)) {
            throw new AssetGetException(
                sprintf(
                    "Unable to get asset file %s to local file %s",
                    $assetFile,
                    $localFile
                )
            );
        }

        return $localFile;
    }

    /**
     * List asset directory's files. A pattern can be given to filter results.
     *
     * @param string $dir
     * @param null $pattern
     * @return array File list (full named)
     */
    public function listFiles($dir, $pattern = null)
    {
        if (substr($dir, -1) === '/') {
            $dir = substr($dir, 0, -1);
        }

        $matchingFiles = [];
        $files = $this->internalGetFiles($dir);

        foreach ($files as $file) {
            if ($pattern && false === stripos($file, $pattern)) {
                continue;
            }

            // if relative path, add dir
            if (0 !== strpos($file, $dir)) {
                $file = $dir . '/' . $file;
            }

            $matchingFiles[] = $file;
        }
        return $matchingFiles;
    }

    /**
     * Remove given file list from asset
     *
     * @param array $assetFiles
     * @return bool
     */
    public function removeFiles(array $assetFiles)
    {
        foreach ($assetFiles as $assetFile) {
            $removed = $this->remove($assetFile);

            if (!$removed) {
                $this->errors[] = sprintf("Unable to remove %s", $assetFile);
            }
        }
        return true;
    }

    /**
     * Remove given asset file from asset
     *
     * @param string $assetFile
     * @return bool
     */
    public function remove($assetFile)
    {
        if ($this->exists($assetFile)) {
            return $this->internalRemove($assetFile);
        }
        return true;
    }

    /**
     * Check if given file exists on asset
     *
     * @param string $assetFile
     * @return bool
     */
    public function exists($assetFile)
    {
        $files = $this->getFiles(dirname($assetFile));

        foreach ($files as $file) {
            if ($file === $assetFile) {
                return true;
            }
        }
        return false;
    }

    /**
     * Client specific operation to list asset directory's files
     *
     * @param string $assetAssetDir
     * @return array $fileList
     */
    public function getFiles($assetAssetDir)
    {
        if ($assetAssetDir === '.') {
            $assetAssetDir = '';
        }
        return $this->internalGetFiles($assetAssetDir);
    }


    /**
     * Client specific opration to put file on asset
     *
     * @param string $localFile
     * @param string $assetFile
     * @return bool
     */
    abstract protected function internalPut($localFile, $assetFile);

    /**
     * Client specific operation to get asset file
     *
     * @param string $assetFile
     * @param string $localFile
     * @return bool
     */
    abstract protected function internalGet($assetFile, $localFile);

    /**
     * Client specific operation to list asset directory's files
     *
     * @param string $assetDir
     * @return array $fileList
     */
    abstract protected function internalGetFiles($assetDir);

    /**
     * Client specific operation to remove asset file
     *
     * @param string $assetFile
     * @return bool
     */
    abstract protected function internalRemove($assetFile);
}
