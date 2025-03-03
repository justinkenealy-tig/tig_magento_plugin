<?php

namespace TIG\OpenFreight\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class PackageType extends AbstractSource
{
    /**
     * Retrieve options array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];

        foreach (self::getOptionArray() as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }

        return $result;
    }

    /**
     * Retrieve option array
     *
     * @return string[]
     */
    public static function getOptionArray()
    {
        return [
            'Carton' => __('Carton'),
            'Pallet' => __('Pallet'),
            'Satchel' => __('Satchel'),
            'Skid' => __('Skid'),
            'Cage' => __('Cage'),
            'Document' => __('Document'),
            'Other' => __('Other'),
        ];
    }

    /**
     * Retrieve option array with empty value
     *
     * @return string[]
     */
    public function getAllOptions()
    {
        $result = [];

        foreach (self::getOptionArray() as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }

        return $result;
    }

    /**
     * Retrieve option text by option value
     *
     * @param string $optionId
     * @return string
     */
    public function getOptionText($optionId)
    {
        $options = self::getOptionArray();

        return isset($options[$optionId]) ? $options[$optionId] : null;
    }
}
