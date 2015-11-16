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
     * {@inheritdoc}
     *
     * @param string $localFile
     * @param string $assetFile
     * @return boolean true
     * @throws AssetPutException
     */
    protected function internalPut($localFile, $assetFile)
    {
        return $this
            ->connection
            ->put($assetFile, $localFile, NET_SFTP_LOCAL_FILE);
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
        return $this->connection->get($assetFile, $localFile);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetAssetDir
     * @return mixed False si le répertoire n'existe pas, une liste sinon
     */
    protected function internalGetFiles($assetAssetDir)
    {
        return $this->connection->nList($assetAssetDir);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetFile
     * @return boolean
     */
    protected function internalRemove($assetFile)
    {
        return $this->connection->delete($assetFile);
    }
}
