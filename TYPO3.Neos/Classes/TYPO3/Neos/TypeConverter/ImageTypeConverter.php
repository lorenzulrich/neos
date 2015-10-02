<?php
namespace TYPO3\Neos\TypeConverter;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * An type converter for Image objects; which are uploaded using plupload
 *
 * @Flow\Scope("singleton")
 */
class ImageTypeConverter extends \TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = array('array');

    /**
     * @var string
     */
    protected $targetType = 'TYPO3\Media\Domain\Model\Asset';

    /**
     * @var integer
     */
    protected $priority = 2;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Resource\ResourceManager
     */
    protected $resourceManager;

    /**
     * We only convert stuff being uploaded using plupload.
     *
     * @param mixed $source the source data
     * @param string $targetType the type to convert to.
     * @return boolean TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
     * @api
     */
    public function canConvertFrom($source, $targetType)
    {
        return (isset($source['type']) && $source['type'] === 'plupload');
    }

    /**
     * Converts the given string or array to a ResourcePointer object.
     *
     * If the input format is an array, this method assumes the resource to be a
     * fresh file upload and imports the temporary upload file through the
     * resource manager.
     *
     * @param array $source The upload info (expected keys: error, name, tmp_name)
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
     * @return \TYPO3\Media\Domain\Model\Image An object or an instance of TYPO3\Flow\Error\Error if the input format is not supported or could not be converted for other reasons
     * @throws \TYPO3\Flow\Property\Exception\TypeConverterException
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        /** @var \TYPO3\Flow\Resource\Resource $resource */
        $resource = $this->resourceManager->importUploadedResource($_FILES['file']);
        if ($resource === false) {
            throw new \TYPO3\Flow\Property\Exception\TypeConverterException('Resource could not be converted.', 1316428994);
        }

        if (substr($resource->getMediaType(), 0, 5) === 'image') {
            $image = new \TYPO3\Media\Domain\Model\Image($resource);
            // TODO: this should maybe be settable
            $image->setTitle('');
            return $image;
        }

        $asset = new \TYPO3\Media\Domain\Model\Asset($resource);
        // TODO: this should maybe be settable
        $asset->setTitle('');
        return $asset;
    }
}
