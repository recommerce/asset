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
     * Récupère la liste de fichiers contenu dans un répertoire
     *
     * @throws \Exception Le paramètre ne correspond pas à un répertoire valide
     * @param string $dir
     * @return mixed False si le répertoire n'existe pas, une liste sinon
     */
    public function getFiles($dir)
    {
        $files = [];
        $fulldir = $this->repository . DS . $dir;

        if (!is_dir($fulldir)) {
            throw new \Exception(
                sprintf("'%s' n'est pas un répertoire.", $fulldir)
            );
        }

        foreach (scandir($fulldir) as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            $files[] = $dir . DS . $file;
        }
        return $files;
    }

    /**
     * Récupère un fichier de l'asset et le copie en local
     *
     * @throws \Exception
     * @param string $assetFile
     * @param string $localFile
     * @return string Nom du nouveau fichier en local
     */
    protected function internalGet($assetFile, $localFile)
    {
        $assetFile = $this->repository . DS . $assetFile;

        if (!copy($assetFile, $localFile)) {
            throw new \Exception(
                sprintf(
                    "Impossible de copier le fichier %s vers %s",
                    $assetFile,
                    $localFile
                )
            );
        }
        return $localFile;
    }

    /**
     * Copie un fichier dans l'asset
     *
     * @param string $localFile
     * @param string $assetFile
     * @return boolean true
     * @throws AssetPutException
     */
    protected function internalPut($localFile, $assetFile)
    {
        $assetFile = $this->repository . DS . $assetFile;
        $dir = dirname($assetFile);

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new AssetPutException(
                sprintf("Impossible de créer le répertoire '%s'", $dir)
            );
        }

        if (!copy($localFile, $assetFile)) {
            throw new AssetPutException(
                sprintf(
                    "Erreur lors de la copie du fichier '%s' vers le dépot '%s'",
                    $localFile,
                    $assetFile
                )
            );
        }
        return true;
    }

    /**
     * Supprime un fichier sur l'asset
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
