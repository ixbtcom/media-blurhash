<?php


namespace Lukaswhite\MediaBlurhash\Utils;

use Ixbtcom\Common\Models\Media;

/**
 * Class ImageChecker
 *
 * Simple utility class for checking whether a media record is an image.
 *
 * @package Lukaswhite\MediaBlurhash\Utils
 */
class ImageChecker
{

    protected $media;


    public function __construct($media)
    {
        $this->media = $media;
    }

    /**
     * @return bool
     */
    public function isImage(): bool
    {
        return in_array($this->media->mime_type, config('media-blurhash.image_mime_types'));
    }
}
