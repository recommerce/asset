<?php

namespace Recommerce\Asset;

use Recommerce\Asset\Exception\AssetGetException;
use Recommerce\Asset\Exception\AssetMoveException;
use Recommerce\Asset\Exception\AssetPutException;
use Recommerce\Asset\Exception\AssetRemoveException;

/**
 * AssetClient est une interface permettant le stockage de fichiers à des fins d'accès via
 * le protocole HTTP.
 * Il fournit les fonctions classiques de gestion de fichier.
 *
 * @package AssetClient
 */
interface AssetClientInterface
{
    const THROW_EXCEPTION = 0x01;
    const OVERWRITE = 0x02;

    /**
     * @param string $oldAssetFile
     * @param string $destAssetDir
     * @param int $behaviorIfDestExists
     * @return string
     * @throws AssetMoveException
     */
    public function move($oldAssetFile, $destAssetDir, $behaviorIfDestExists = self::THROW_EXCEPTION);

    /**
     * Copie un fichier dans l'asset
     *
     * @param string $localFile
     * @param string $assetFile
     * @param boolean $toDelete
     * @return boolean true
     * @throws AssetPutException
     */
    public function put($localFile, $assetFile, $toDelete = false);

    /**
     * Récupère un fichier de l'asset et le copie en local
     *
     * @param string $assetFile
     * @param string $localFile
     * @return string Nom du nouveau fichier en local
     * @throws AssetGetException
     */
    public function get($assetFile, $localFile);

    /**
     * @param string $dir
     * @param null $pattern
     * @return array
     */
    public function listFiles($dir, $pattern = null);

    /**
     * Supprime un fichier sur l'asset
     *
     * @param string $assetFile
     * @return boolean
     * @throws AssetRemoveException
     */
    public function remove($assetFile);

    /**
     * @param array $assetFiles
     * @return bool
     * @throws AssetRemoveException
     */
    public function removeFiles(array $assetFiles);

    /**
     * Vérifie l'existence d'un fichier sur l'asset
     *
     * @param string $assetFile
     * @return boolean
     */
    public function exists($assetFile);
}
