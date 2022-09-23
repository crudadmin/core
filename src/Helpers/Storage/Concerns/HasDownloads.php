<?php

namespace Admin\Core\Helpers\Storage\Concerns;

use Admin\Core\Helpers\Storage\Mutators\EncryptorMutator;

trait HasDownloads
{
    /**
     * Returns file hash for download
     *
     * @return  string
     */
    public function hash()
    {
        return hash('sha256', md5('!$%'.sha1(env('APP_KEY')).$this->disk.$this->path));
    }

    /**
     * Returns absolute signed path for downloading file
     *
     * @param  bool  $displayableInBrowser
     *
     * @return  string
     */
    public function download($displayableInBrowser = false)
    {
        //If is possible open file in browser, then return right path of file and not downloading path
        if ($displayableInBrowser && in_array($this->extension, (array) $displayableInBrowser)) {
            return $this->url;
        }

        $action = action('\Admin\Controllers\DownloadController@signedDownload', $this->hash());

        $query = [
            'model' => $this->table,
            'field' => $this->fieldKey,
            'file' => $this->filename,
        ];

        return $action.'?'.http_build_query($query);
    }

    /**
     * Returns file download response
     *
     * @return  Response
     */
    public function downloadResponse()
    {
        if ( $this->isEncrypted() ){
            $encryptedData = $this->get();
            $filename = str_replace_last(EncryptorMutator::ENCRYPTOR_EXTENSION, '', $this->filename);

            return response($encryptedData, 200, [
                'Content-Disposition' => 'attachment;filename='.$filename,
            ]);
        }

        return $this->getStorage()->download(
            $this->path
        );
    }
}