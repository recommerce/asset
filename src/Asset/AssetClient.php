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
     * @var string répertoire de stockage temporaire pour gérer le transite des fichiers
     */
    protected $tmpDir;

    /**
     * @param array $options
     */
    protected function __construct(array $options = [])
    {
        $this->tmpDir = (isset($options['tmpDir']))
            ? $options['tmpDir']
            : '/tmp';
    }

    /**
     * Récupère l'url du dépôt des assets
     *
     * @return string
     */
    public function getRootUrl()
    {
        return Configure::read('assets.url.root');
    }

    /**
     * Récupère l'url d'accès au fichier
     *
     * @param string Nom du fichier sur l'asset
     * @return string Url complète du fichier
     * @throws Exception
     */
    public function getUrl($filename)
    {
        $assetRoot = $this->getRootUrl();

        if (empty($assetRoot)) {
            throw new Exception(
                "Le paramètre de configuration 'url.assets.root' n'est pas défini."
            );
        }
        return $assetRoot . $filename;
    }

    /**
     * @param string $assetFile
     * @param string $assetDir
     * @return bool
     * @throws AssetMoveException
     */
    public function move($assetFile, $assetDir)
    {
        if (!$this->exists($assetFile)) {
            throw new AssetMoveException(sprintf(
                "Asset file (%s) does not exists",
                $assetFile
            ));
        }
        return $this->internalMove($assetFile, $assetDir);
    }

    /**
     * Beware, download file for uploading it with a new name. Exists for convenience,
     * override it for performance.
     *
     * @param string $assetFile
     * @param string $assetDir
     * @return bool
     */
    protected function internalMove($assetFile, $assetDir)
    {
        $newAssetFile = $assetDir . DS . basename($assetFile);
        $localFile = $this->get($assetFile);
        $this->put($localFile, $newAssetFile);
        $this->remove($assetFile);

        return $newAssetFile;
    }

    /**
     * Copie un fichier dans l'asset
     *
     * @param string $localFile
     * @param string $assetFile
     * @param boolean $toDelete
     * @return boolean true
     */
    public function put($localFile, $assetFile, $toDelete = false)
    {
        if ('http' === substr($localFile, 0, 4)) {
            $localFile = $this->getDistantFile($localFile, $assetFile);
            $toDelete = true;
        }

        $this->internalPut($localFile, $assetFile);

        if ($toDelete) {
            unlink($localFile);
        }
        return true;
    }

    /**
     * Copie un fichier http en local et retourne le nom du fichier créé
     *
     * @param string $localfile
     * @param string $assetFile
     * @return string
     * @throws AssetPutException Impossible de copier le fichier distant
     */
    protected function getDistantFile($localfile, $assetFile)
    {
        $tmpName = str_replace(['/', '\\'], '', $assetFile);
        $tmpFile = $this->tmpDir . DS . $tmpName;

        if (!copy($localfile, $tmpFile)) {
            throw new AssetPutException(
                sprintf(
                    "Impossible de copier le fichier distant '%s' vers '%s'.",
                    $localfile,
                    $tmpFile
                )
            );
        }
        return $tmpFile;
    }

    /**
     * Récupère un fichier de l'asset et le copie en local
     *
     * @param string $assetFile
     * @param string $localFile
     * @return string Nom du nouveau fichier en local
     * @throws AssetGetException
     */
    public function get($assetFile, $localFile = null)
    {
        if (null === $localFile) {
            $localFile = $this->tmpDir . DS . $assetFile;
        }

        $destinationDir = dirname($localFile);

        if (!is_dir($destinationDir) && !mkdir($destinationDir, 0755, true)) {
            throw new AssetGetException(sprintf(
                "Cannot create destination directory %s",
                $destinationDir
            ));
        }

        return $this->internalGet($assetFile, $localFile);
    }

    /**
     * @param string $dir
     * @param null $pattern
     * @return array
     */
    public function listFiles($dir, $pattern = null)
    {
        $matchingFiles = array();
        $files = $this->getFiles($dir);

        foreach ($files as $file) {
            if ($pattern && false === stripos($file, $pattern)) {
                continue;
            }
            $matchingFiles[] = $file;
        }
        return $matchingFiles;
    }

    /**
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
     * Supprime un fichier sur l'asset
     *
     * @param string $assetFile
     * @return boolean
     */
    public function remove($assetFile)
    {
        if ($this->exists($assetFile)) {
            return $this->internalRemove($assetFile);
        }
        return true;
    }

    /**
     * Vérifie l'existence d'un fichier sur l'asset
     *
     * @param string $assetFile
     * @return boolean
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
     * Copie un fichier dans l'asset
     *
     * @param string $localFile
     * @param string $assetFile
     * @return boolean true
     */
    abstract protected function internalPut($localFile, $assetFile);

    /**
     * Récupère un fichier de l'asset et le copie en local
     *
     * @param string $assetFile
     * @param string $localFile
     * @return string Nom du nouveau fichier en local
     */
    abstract protected function internalGet($assetFile, $localFile);

    /**
     * Récupère la liste de fichiers contenu dans un répertoire
     *
     * @param string $dir
     * @return mixed False si le répertoire n'existe pas, une liste sinon
     */
    abstract public function getFiles($dir);

    /**
     * Supprime un fichier sur l'asset
     *
     * @param string $assetFile
     * @return boolean
     */
    abstract protected function internalRemove($assetFile);
}
