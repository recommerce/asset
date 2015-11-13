<?php

namespace Recommerce\Asset\Adapter;

use Recommerce\Asset\AssetClient;
use Recommerce\Asset\AssetClientInterface;
use Recommerce\Asset\Exception\AssetMoveException;
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
        ftp_pasv($connection, true);

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
     * {@inheritdoc}
     *
     * @param string $assetFile
     * @param string $assetDir
     * @return bool
     * @throws AssetMoveException
     */
    protected function internalMove($assetFile, $assetDir)
    {
        $this->reconnectIfNeeded();

        $newAssetFile = $assetDir . DS . basename($assetFile);

        if (!ftp_rename($this->connection, $assetFile, $newAssetFile)) {
            throw new AssetMoveException(sprintf(
                "Unable to move '%s' to '%s'.",
                $assetFile,
                $newAssetFile
            ));
        }

        return $newAssetFile;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $localFile
     * @param string $assetFile
     * @return boolean true
     */
    protected function internalPut($localFile, $assetFile)
    {
        $this->reconnectIfNeeded();

        return ftp_put($this->connection, $assetFile, $localFile, FTP_BINARY);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetFile
     * @param string $localFile
     * @return string Nom du nouveau fichier en local
     * @throws AssetPutException
     */
    protected function internalGet($assetFile, $localFile)
    {
        $this->reconnectIfNeeded();

        return ftp_get($this->connection, $localFile, $assetFile, FTP_BINARY);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $dir
     * @return mixed False si le répertoire n'existe pas, une liste sinon
     */
    public function getFiles($dir)
    {
        $this->reconnectIfNeeded();

        return ftp_nlist($this->connection, $dir);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetFile
     * @return boolean
     */
    protected function internalRemove($assetFile)
    {
        $this->reconnectIfNeeded();

        return ftp_delete($this->connection, $assetFile);
    }
}
