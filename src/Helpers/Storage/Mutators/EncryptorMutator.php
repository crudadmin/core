<?php

namespace Admin\Core\Helpers\Storage\Mutators;

use Illuminate\Support\Facades\Crypt;
use Image;
use ImageCompressor;

class EncryptorMutator extends UploadMutator
{
    /**
     * Prefixes for encrypted files
     */
    const ENCRYPTOR_PREFIX = 'crudadmin.encrypted.base64:';
    const ENCRYPTOR_EXTENSION = '.encrypted';

    public function isActive()
    {
        return $this->isEncrypted();
    }

    private function isEncrypted()
    {
        return $this->model->hasFieldParam($this->fieldKey, 'encrypted');
    }

    public static function getEncryptor()
    {
        return Crypt::getFacadeRoot();
    }

    public static function encrypt($data)
    {
        return self::ENCRYPTOR_PREFIX.self::getEncryptor()->encrypt($data);
    }

    public static function decrypt($data)
    {
        $data = substr($data, strlen(self::ENCRYPTOR_PREFIX));

        return self::getEncryptor()->decrypt($data);
    }

    public function getFilename()
    {
        $e = self::ENCRYPTOR_EXTENSION;

        //If filename already has encrypted extension, does not duplicate
        return str_replace_last($e.$e, $e, $this->filename.$e);
    }

    public function mutate()
    {
        $localStorage = $this->getStorage();
        $originalPath = $this->getPath();

        $encryptedPath = dirname($originalPath).'/'.$this->getFilename();

        $encryptedData = self::encrypt(
            $localStorage->get($originalPath)
        );

        $localStorage->put(
            $encryptedPath,
            $encryptedData
        );

        //Remove previous file
        if ( $encryptedPath !== $originalPath ) {
            $localStorage->delete($originalPath);
        }
    }
}
