<?php

namespace Recommerce\Asset\Adapter;

use phpseclib\Net\SFTP;
use Recommerce\Asset\AssetClient;
use Recommerce\Asset\AssetClientInterface;
use Recommerce\Asset\Exception\AssetPutException;
use Recommerce\Asset\Exception\ConnectionException;

class SftpSecLib extends AssetClient implements AssetClientInterface
{

    /**
     * @return SFTP
     * @throws ConnectionException
     */
    protected function internalConnect()
    {
        $connection = new SFTP($this->host, $this->port);

        if (!$connection->login($this->login, $this->password)) {
            throw new ConnectionException(
                "Unable to connect to host for given parameter"
            );
        }

        return $connection;
    }

    /**
     * @return bool
     */
    public function disconnect()
    {
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
        if (!$this->connection->put($assetFile, $localFile, NET_SFTP_LOCAL_FILE)) {
            throw new AssetPutException(
                sprintf("Unable to put local file %s on asset %s", $localFile, $assetFile)
            );
        }

        return true;
    }

    /**
     * Récupère un fichier de l'asset et le copie en local
     *
     * @param string $assetFile
     * @param string $localFile
     * @return string Nom du nouveau fichier en local
     * @throws AssetPutException
     */
    protected function internalGet($assetFile, $localFile)
    {
        if (!$this->connection->get($assetFile, $localFile)) {
            throw new AssetPutException(
                sprintf(
                    "Unable to get asset file %s on local filesystem %s",
                    $assetFile,
                    $localFile
                )
            );
        }
        return $localFile;
    }

    /**
     * Récupère la liste de fichiers contenu dans un répertoire
     *
     * @param string $dir
     * @return mixed False si le répertoire n'existe pas, une liste sinon
     */
    public function getFiles($dir)
    {
        return $this->connection->nList($dir);
    }

    /**
     * Supprime un fichier sur l'asset
     *
     * @param string $assetFile
     * @return boolean
     */
    protected function internalRemove($assetFile)
    {
        return $this->connection->delete($assetFile);
    }
}
