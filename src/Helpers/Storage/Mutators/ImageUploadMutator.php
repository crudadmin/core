<?php

namespace Admin\Core\Helpers\Storage\Mutators;

use Image;
use ImageCompressor;

class ImageUploadMutator extends UploadMutator
{
    public function mutate()
    {
        //Skip non image files
        if ( in_array($this->getExtension(), ['jpg', 'jpeg', 'png']) === false ){
            return true;
        }

        $filedata = $this->getStorage()->get(
            $this->getPath()
        );

        $image = Image::make($filedata);

        $image = $this->rotateImage($image);

        $image = $this->resizeMaxResolution($image);

        //Save image with compression
        ImageCompressor::saveImageWithCompression(
            $this->getStorage(),
            $this->model,
            $image,
            $this->getPath(),
            $this->getExtension()
        );
    }

    /**
     * If image is uploaded from mobile device
     * it may be saved in wrong rotation direction. We need fix this.
     *
     * @param  Image  $image
     *
     * @return  Image
     */
    private function rotateImage($image)
    {
        //Skip non rotatable image types
        if ( in_array($this->getExtension(), ['jpg', 'jpeg', 'png']) === false ){
            return true;
        }

        return $image->orientate();
    }

    /*
     * Check if uploaded image is bigger than max size
     */
    private function resizeMaxResolution($image)
    {
        $imageMaximumProportions = $this->model->getProperty('imageMaximumProportions');

        //Check if images can be automatically resized
        if (! ($can_resize = config('admin.image_auto_resize', true))) {
            return $image;
        }

        //Max dimensions
        $maxWidth = config('admin.image_max_width', 1920);
        $maxHeight = config('admin.image_max_height', 1200);

        $aspectRatio = function ($constraint) {
            $constraint->aspectRatio();
        };

        //Check if images can be resized to max filesize
        if ($maxWidth !== false) {
            if ($image->getWidth() > $maxWidth) {
                $image->resize($maxWidth, null, $aspectRatio);
            }
        }

        //Check if images can be resized to max filesize
        if ($maxHeight !== false) {
            if ($image->getHeight() > $maxHeight) {
                $image->resize(null, $maxHeight, $aspectRatio);
            }
        }

        return $image;
    }
}
