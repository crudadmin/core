<?php

namespace Admin\Core\Eloquent\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

trait HasEncryption
{
    /**
     * Decrypt the given encrypted string.
     *
     * @param  string  $value
     * @return mixed
     */
    public function fromEncryptedString($value)
    {
        try {
            return (static::$encrypter ?? Crypt::getFacadeRoot())->decrypt($value, false);
        } catch (DecryptException $e) {
            return $value;
        }
    }
}
