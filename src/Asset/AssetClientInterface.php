<?php

namespace Recommerce\Asset;

/**
 * AssetClient est une interface permettant le stockage de fichiers à des fins d'accès via
 * le protocole HTTP.
 * Il fournit les fonctions classiques de gestion de fichier.
 *
 * @package AssetClient
 */
interface AssetClientInterface
{
    /**
     * Copie un fichier dans l'asset
     *
     * @param string $localFile
     * @param string $assetFile
     * @param boolean $toDelete
     * @return boolean true
     */
    public function put($localFile, $assetFile, $toDelete = false);

    /**
     * Récupère un fichier de l'asset et le copie en local
     *
     * @param string $assetFile
     * @param string $localFile
     * @return string Nom du nouveau fichier en local
     */
    public function get($assetFile, $localFile);

    /**
     * Récupère la liste de fichiers contenu dans un répertoire
     *
     * @param string $dir
     * @return mixed False si le répertoire n'existe pas, une liste sinon
     */
    public function getFiles($dir);

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
     */
    public function remove($assetFile);

    /**
     * @param array $assetFiles
     * @return bool
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
