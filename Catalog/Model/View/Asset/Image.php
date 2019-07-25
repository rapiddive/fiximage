<?php
/**
 * Image
 *
 * @copyright Copyright © 2019 Firebear Studio. All rights reserved.
 * @author    fbeardev@gmail.com
 */

namespace Rapiddive\FixImage\Catalog\Model\View\Asset;

use Magento\Catalog\Model\Product\Media\ConfigInterface;
use Magento\Catalog\Model\View\Asset\Image as MagentoImage;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\View\Asset\ContextInterface;

class Image extends MagentoImage
{
    /**
     * Misc image params depend on size, transparency, quality, watermark etc.
     *
     * @var array
     */
    private $miscParams;

    /**
     * @var ContextInterface
     */
    private $context;
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Image constructor.
     * @param ConfigInterface $mediaConfig
     * @param ContextInterface $context
     * @param EncryptorInterface $encryptor
     * @param $filePath
     * @param array $miscParams
     */
    public function __construct(
        ConfigInterface $mediaConfig,
        ContextInterface $context,
        EncryptorInterface $encryptor,
        $filePath,
        array $miscParams
    ) {
        parent::__construct($mediaConfig, $context, $encryptor, $filePath, $miscParams);
        $this->context = $context;
        $this->encryptor = $encryptor;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        $url = $this->context->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getImageInfo();
        $imageFile = $this->context->getPath() . DIRECTORY_SEPARATOR . $this->getImageInfo();
        if (!file_exists($imageFile)) {
            $url = $this->context->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getImageInfoWithoutCache();
        }
        return $url;
    }

    private function getImageInfoWithoutCache()
    {
        $path = $this->getFilePath();
        return preg_replace('|\Q' . DIRECTORY_SEPARATOR . '\E+|', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Generate path from image info
     *
     * @return string
     */
    private function getImageInfo()
    {
        $path = $this->getModule()
            . DIRECTORY_SEPARATOR . $this->getMiscPath()
            . DIRECTORY_SEPARATOR . $this->getFilePath();
        return preg_replace('|\Q' . DIRECTORY_SEPARATOR . '\E+|', DIRECTORY_SEPARATOR, $path);
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
