<?php

namespace Recommerce\Asset\Adapter;

use Recommerce\Asset\AssetClient;
use Recommerce\Asset\AssetClientInterface;

class SftpClient extends AssetClient implements AssetClientInterface
{
    /**
     * @var FtpStream
     */
    private $stream;

    /**
     * @var array
     */
    private $methods = [];

    /**
     * @param string $host
     * @param string $login
     * @param string $password
     * @param int $port
     * @param array $options
     * @throws ConnectionException
     */
    public function __construct($host, $login, $password, $port = 22, array $methods = [], array $options = [])
    {
        parent::__construct($options);

        $this->host = $host;
        $this->login = $login;
        $this->password = $password;
        $this->port = $port;
        $this->methods = $methods;

        $this->maxTry = (isset($options['maxTry']) && $options['maxTry'] > 0)
            ? $options['maxTry']
            : 5;

        $this->tryInterval = (isset($options['tryInterval']) && $options['tryInterval'] > 0)
            ? $options['tryInterval']
            : 60;

        $this->connection = $this->connectWithTry();
        $this->stream = ssh2_sftp($this->connection);
    }

    protected function internalConnect()
    {
        $connection = ssh2_connect($this->host, $this->port, $this->methods);

        if (!$connection || !ssh2_auth_password($connection, $this->login, $this->password)) {
            return null;
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
     */
    protected function internalPut($localFile, $assetFile)
    {
        return ssh2_scp_recv($this->connection, $assetFile, $localFile);
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
        if (!ssh2_scp_send($this->connection, $localFile, $assetFile, 0644)) {
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
     * {@inheritdoc}
     *
     * @param string $dir
     * @return mixed False si le répertoire n'existe pas, une liste sinon
     */
    public function getFiles($dir)
    {
        $files = [];
        $handle = opendir("ssh2.sftp://{$this->stream}/$dir");

        while (false !== ($file = readdir($handle))) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            $files[] = $file;
        }
        closedir($handle);

        return $files;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetFile
     * @return boolean
     */
    protected function internalRemove($assetFile)
    {
        return ssh2_sftp_unlink($this->stream, $assetFile);
    }
}
