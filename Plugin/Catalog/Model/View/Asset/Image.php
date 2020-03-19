<?php
/**
 * Image
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    Firebear Studio <fbeardev@gmail.com>
 */

namespace Rapidive\FixImage\Plugin\Catalog\Model\View\Asset;

use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\MediaStorage\Service\ImageResize;

class Image
{
    /**
     * Misc image params depend on size, transparency, quality, watermark etc.
     *
     * @var array
     */
    private $miscParams;
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var \Magento\Catalog\Model\View\Asset\Image
     */
    private $subject;
    /**
     * @var ImageResize
     */
    private $imageResize;

    /**
     * Image constructor.
     * @param EncryptorInterface $encryptor
     * @param ImageResize $imageResize
     */
    public function __construct(
        EncryptorInterface $encryptor,
        ImageResize $imageResize
    ) {
        $this->encryptor = $encryptor;
        $this->imageResize = $imageResize;
    }

    public function beforeGetUrl(
        \Magento\Catalog\Model\View\Asset\Image $subject
    ) {
        $this->subject = $subject;
        $actualFile = $subject->getContext()->getPath() . DIRECTORY_SEPARATOR . $this->getImageInfo();
        if (!file_exists($actualFile)) {
            $this->imageResize->resizeFromImageName($subject->getFilePath());
        }
    }

    /**
     * Generate path from image info
     *
     * @return string
     */
    private function getImageInfo()
    {
        $path = $this->subject->getModule()
            . DIRECTORY_SEPARATOR . $this->getMiscPath()
            . DIRECTORY_SEPARATOR . $this->subject->getFilePath();
        return preg_replace('|\Q'. DIRECTORY_SEPARATOR . '\E+|', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Retrieve part of path based on misc params
     *
     * @return string
     */
    private function getMiscPath()
    {
        return $this->encryptor->hash(
            implode('_', $this->convertToReadableFormat($this->miscParams)),
            Encryptor::HASH_VERSION_MD5
        );
    }

    /**
     * Converting bool into a string representation
     * @param $miscParams
     * @return array
     */
    private function convertToReadableFormat($miscParams)
    {
        $miscParams['image_height'] = 'h:' . ($miscParams['image_height'] ?? 'empty');
        $miscParams['image_width'] = 'w:' . ($miscParams['image_width'] ?? 'empty');
        $miscParams['quality'] = 'q:' . ($miscParams['quality'] ?? 'empty');
        $miscParams['angle'] = 'r:' . ($miscParams['angle'] ?? 'empty');
        $miscParams['keep_aspect_ratio'] = (isset($miscParams['keep_aspect_ratio']) ? '' : 'non') . 'proportional';
        $miscParams['keep_frame'] = (isset($miscParams['keep_frame']) ? '' : 'no') . 'frame';
        $miscParams['keep_transparency'] = (isset($miscParams['keep_transparency']) ? '' : 'no') . 'transparency';
        $miscParams['constrain_only'] = (isset($miscParams['constrain_only']) ? 'do' : 'not') . 'constrainonly';
        $miscParams['background'] = isset($miscParams['background'])
            ? 'rgb' . implode(',', $miscParams['background'])
            : 'nobackground';
        return $miscParams;
    }
}
