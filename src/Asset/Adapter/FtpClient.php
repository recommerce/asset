<?php

namespace Recommerce\Asset\Adapter;

use Recommerce\Asset\AssetClient;
use Recommerce\Asset\AssetClientInterface;
use Recommerce\Asset\Exception\AssetPutException;

class FtpClient extends AssetClient implements AssetClientInterface
{
    use ConnectedClientTrait;

    /**
     * @param string $host
     * @param string $login
     * @param string $password
     * @param int $port
     * @param array $options
     * @throws ConnectionException
     */
    public function __construct($host, $login, $password, $port = 21, array $options = [])
    {
        parent::__construct($options);

        $this->host = $host;
        $this->login = $login;
        $this->password = $password;
        $this->port = $port;

        $this->maxTry = (isset($options['maxTry']) && $options['maxTry'] > 0)
            ? $options['maxTry']
            : 5;

        $this->tryInterval = (isset($options['tryInterval']) && $options['tryInterval'] > 0)
            ? $options['tryInterval']
            : 60;

        $this->connection = $this->connectWithTry();
    }

    /**
     * Connect to ftp server
     *
     * @return resource
     * @throws ConnectionException
     */
    protected function internalConnect()
    {
        $connection = ftp_connect($this->host, $this->port);

        if (!$connection || !ftp_login($connection, $this->login, $this->password)) {
            return null;
        }

        return $connection;
    }

    /**
     * @return bool
     */
    public function disconnect()
    {
        return ftp_close($this->connection);
    }

    /**
     * @param string $assetFile
     * @param string $assetDir
     * @return bool
     */
    protected function internalMove($assetFile, $assetDir)
    {
        $newAssetFile = $assetDir . DS . basename($assetFile);

        return ftp_rename($this->connection, $assetFile, $newAssetFile);
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
        ftp_pasv($this->connection, true);

        if (!ftp_put($this->connection, $assetFile, $localFile, FTP_BINARY)) {
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
        ftp_pasv($this->connection, true);

        if (!ftp_get($this->connection, $localFile, $assetFile, FTP_BINARY)) {
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
        ftp_pasv($this->connection, true);
        return ftp_nlist($this->connection, $dir);
    }

    /**
     * Supprime un fichier sur l'asset
     *
     * @param string $assetFile
     * @return boolean
     */
    protected function internalRemove($assetFile)
    {
        ftp_pasv($this->connection, true);
        return ftp_delete($this->connection, $assetFile);
    }
}
