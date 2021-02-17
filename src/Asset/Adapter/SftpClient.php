<?php

namespace Recommerce\Asset\Adapter;

use Recommerce\Asset\AssetClient;
use Recommerce\Asset\AssetClientInterface;
use Recommerce\Asset\Exception\AssetMoveException;
use Recommerce\Asset\Exception\AssetPutException;
use Recommerce\Asset\Exception\ConnectionException;

class SftpClient extends AssetClient implements AssetClientInterface
{
    // Connexion (result of ssh2_connect)
    public $connexion = null;

    // SFTP subsystem (result of ssh2_sftp)
    public $sftp = null;

    // Remote Working Directory
    public $remoteWorkingDirectory = '/';

    use ConnectedClientTrait;

    /**
     * @param string $host
     * @param string $login
     * @param string $password
     * @param int $port
     * @param string $fingerprint
     * @param array $options
     *  -> array params [
     *   -> publicKeyFile / privateKeyFile : set of key used to authenticate the connexion (no password authenticate)
     *   -> passPhrase : passPhrase used by keys ?
     *   -> password : password used to authenticate the connexion (no keys authenticate)
     *   -> remoteWorkingDirectory : modify the remote working directory (default : "/")
     *  ]
     *  -> string $method
     *
     * @throws ConnectionException
     */
    public function __construct($host, $login, $password, $port = 22, $fingerprint, array $options = [])
    {
        parent::__construct($options);

        $this->host = $host;
        $this->login = $login;
        $this->password = $password;
        $this->port = $port;
        $this->fingerprint = $fingerprint;
        $this->params = (isset($options['param'])) ? $options['param'] : null;
        $this->method = (isset($options['method'])) ? $options['method'] : null;

        $this->maxTry = (isset($options['maxTry']) && $options['maxTry'] > 0)
            ? $options['maxTry']
            : 5;

        $this->tryInterval = (isset($options['tryInterval']) && $options['tryInterval'] > 0)
            ? $options['tryInterval']
            : 60;

        $this->connection = $this->connectWithTry();
    }

    /**
     * @return bool
     */
    public function disconnect()
    {
        if ($this->connexion) {
            ssh2_exec($this->connexion, 'exit');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $oldAssetFile
     * @param string $newAssetFile
     * @return bool
     * @throws AssetMoveException
     */
    protected function internalMove($oldAssetFile, $newAssetFile)
    {
        $this->reconnectIfNeeded();

        if (!ssh2_sftp_rename($this->connection, $oldAssetFile, $newAssetFile)) {
            throw new AssetMoveException(sprintf(
                "Unable to move '%s' to '%s'.",
                $oldAssetFile,
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
        return $this->uploadFile($localFile, $assetFile);
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
        return $this->downloadFile($assetFile, $localFile, true);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $assetAssetDir
     * @return mixed False si le répertoire n'existe pas, une liste sinon
     */
    protected function internalGetFiles($dir)
    {
        $this->reconnectIfNeeded($dir);

        return $this->scanDir($dir);
    }

    private function scanDir($dirName, $pattern = '')
    {
        $dir = intval($this->sftp) . $this->remoteWorkingDirectory . $dirName;
        $tempArray = [];
        $handle = opendir("ssh2.sftp://" . $dir);
        while (($file = readdir($handle))) {
            if (substr("$file", 0, 1) != ".") {
                if (!is_dir($file)) {
                    $tempArray[] = $file;
                }
            }
        }
        closedir($handle);
        return $tempArray;
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

        return ssh2_sftp_unlink($this->connection, $assetFile);
    }

    /**
     *
     * This function is used to connect an SSH server and open a SFTP subsystem on this server
     *
     * @throws Exception
     *   -> Cannot connect to server
     *   -> No fingerprint defined !
     *   -> No username defined !
     *   -> HOSTKEY MISMATCH! Attaque Man-In-The-Middle possible ?
     *   -> Authentification failed by public/private Key File
     *   -> Authentification failed by password
     *   -> No authentification
     *
     * @return null|resource
     */
    protected function internalConnect()
    {
        // Connect to server
        if ($this->method === null) {
            $this->method = [
                'kex' => 'diffie-hellman-group1-sha1',
                'client_to_server' => [
                    'crypt' => 'aes256-cbc',
                    'comp' => 'none',
                    'mac' => 'hmac-sha1'
                ],
                'server_to_client' => [
                    'crypt' => 'aes256-cbc',
                    'comp' => 'none',
                    'mac' => 'hmac-sha1'
                ]
            ];
        } else {
            if ($this->method = 'default') {
                $this->method = [];
            } else {
                throw new ConnectionException("Wrong method");
            }
        }
        if (!($this->connexion = ssh2_connect($this->host, $this->port, $this->method))) {
            throw new ConnectionException("Cannot connect to server: $this->host:$this->port");
        }

        // Check fingerprint
        if (!$this->fingerprint) {
            throw new ConnectionException('No fingerprint defined !');
        } elseif ($this->fingerprint != $this->getFingerprint()) {
            throw new ConnectionException(
                "HOSTKEY MISMATCH! Attaque Man-In-The-Middle possible ? (got:" . $this->getFingerprint(
                ) . ")"
            );
        }

        // Authenticate the connexion (by keys or password)
        $isAuthentificate = false;
        if (!$this->host) {
            throw new ConnectionException('No login defined !');
        }
        if (isset($this->params['publicKeyFile']) && isset($this->params['privateKeyFile'])) {
            $publicKeyFile = $this->params['publicKeyFile'];
            $privateKeyFile = $this->params['privateKeyFile'];
            if (isset($this->params['passPhrase'])) {
                $passPhrase = $this->params['passPhrase'];
            } else {
                $passPhrase = null;
            }
            if (!(ssh2_auth_pubkey_file(
                $this->connexion,
                $this->login,
                $publicKeyFile,
                $privateKeyFile,
                $passPhrase
            ))
            ) {
                throw new ConnectionException(
                    "Authentification failed by public/private Key File"
                );
            } else {
                $isAuthentificate = true;
            }
        } elseif (!(ssh2_auth_password($this->connexion, $this->login, $this->password))) {
            throw new ConnectionException("Authentification failed by password");
        } else {
            $isAuthentificate = true;
        }
        if (!$isAuthentificate) {
            throw new ConnectionException("No authentification");
        }
        
        // Create the SFTP subsystem
        $this->sftp = ssh2_sftp($this->connexion);
        
        // Modify the remoteWorkingDirectory ?
        if (isset($this->params['remoteWorkingDirectory'])) {
            $this->remoteWorkingDirectory = $this->params['remoteWorkingDirectory'];
        }
        return $this->sftp;
    }

    /**
     * This function is used to get the fingerprint of a server
     *
     * @return string, fingerprint of a server
     */
    public function getFingerprint()
    {
        return ssh2_fingerprint($this->connexion);
    }

    /**
     * @return bool
     */
    private function isConnected($dir)
    {
        return is_array($this->scanDir($dir));
    }

    /**
     * This function is used to download a file from the server. This function get contents from the remote file (with getFile),
     * and save the data in a local file !
     *
     * @param string $remoteFile , relative path of the remote file
     * @param string $localFile , relative path of the local file
     * @param string $replaceFileIfAlreadyExist , replace the current file if it's already exist ?
     * @return boolean true if file is successfully downloaded, false otherwise
     * @throws Exception
     *   -> Could not open remote file: $remoteFile
     *   -> Could not create local file: $localFile
     *   -> Could not save data from file: $remoteFile
     *   -> File already exists: $localFile
     */
    public function downloadFile($remoteFile, $localFile, $replaceFileIfAlreadyExist = false)
    {
        $dataToSave = $this->getFile($remoteFile);

        if ($dataToSave === false) {
            throw new Exception("Could not open remote file: $remoteFile");
        }

        if ($replaceFileIfAlreadyExist || !file_exists($localFile)) {
            $stream = @fopen($localFile, 'wb');
            if (!$stream) {
                throw new Exception("Could not create local file: $localFile");
            }
            if (@fwrite($stream, $dataToSave) === false) {
                throw new Exception(
                    "Could not save data from file: $remoteFile"
                );
            }
            @fclose($stream);
            return true;
        } else {
            throw new Exception("File already exists: $localFile");
        }
        return false;
    }

    /**
     * This function is used to upload a local file to the server. This function get contents from the local file, and
     * call the setFile to put this content to the remoteFile !
     *
     * @param string $localFile , relative path of the local file
     * @param string $remoteFile , relative path of the remote file
     * @return boolean true if file is successfully uploaded, false otherwise
     * @throws Exception
     *   -> Could not open local file: $localFile
     */
    public function uploadFile($localFile, $remoteFile)
    {
        $dataToSend = @file_get_contents($localFile);

        if ($dataToSend === false) {
            throw new Exception("Could not open local file: $localFile");
        }

        return $this->setFile($remoteFile, $dataToSend);
    }

    /**
     * This function is used to get content of a remote file
     *
     * @param string $remoteFile , relative path of the remote file
     * @return string Content of the remote file, or false otherwise !
     * @throws Exception
     *   -> No SFTP connexion !
     *   -> Could not open file: $remoteFile
     *   -> File "$remoteFile" doesn't exists !
     */
    public function getFile($remoteFile)
    {
        $sftp = intval($this->sftp) . $this->remoteWorkingDirectory;
        if (!$sftp) {
            throw new Exception("No SFTP connexion !");
        }
        if (file_exists("ssh2.sftp://" . $sftp . $remoteFile)) {
            $stream = @fopen("ssh2.sftp://" . $sftp . $remoteFile, 'r');
            if (!$stream) {
                throw new Exception("Could not open file: $remoteFile");
            }
            $out = '';
            // Read the file by packet of 8192 bytes
            $buffer = fread($stream, 8192);
            $out .= $buffer;
            while (!feof($stream) && !empty($buffer)) {
                $buffer = fread($stream, 8192);
                $out .= $buffer;
            }
            fclose($stream);
            return $out;
        } else {
            // File doesn't exists
            throw new Exception('File "' . $remoteFile . '" doesn\'t exists !');
        }
        return false;
    }
}
