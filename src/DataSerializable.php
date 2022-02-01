<?php
/**
 * @link https://github.com/illuminatech
 * @copyright Copyright (c) 2019 Illuminatech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace Illuminatech\Balance;

/**
 * DataSerializable provides ability to serialize extra attributes into the single field.
 *
 * It may be useful using data storage with static data schema, like relational database.
 * This trait supposed to be used inside descendant of {@see \Illuminatech\Balance\Balance}.
 *
 * @mixin \Illuminatech\Balance\Balance
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
trait DataSerializable
{
    /**
     * @var string name of the transaction entity attribute, which should be used to store serialized data.
     */
    public $dataAttribute = 'data';

    /**
     * Processes attributes to be saved in persistent storage, serializing the ones not allowed for direct storage.
     *
     * @param array $attributes raw attributes to be processed.
     * @param array $allowedAttributes list of attribute names, which are allowed to be saved in persistent stage.
     * @return array actual attributes.
     */
    protected function serializeAttributes(array $attributes, array $allowedAttributes)
    {
        if ($this->dataAttribute === null) {
            return $attributes;
        }

        $safeAttributes = [];
        $dataAttributes = [];
        foreach ($attributes as $name => $value) {
            if (in_array($name, $allowedAttributes, true)) {
                $safeAttributes[$name] = $value;
            } else {
                $dataAttributes[$name] = $value;
            }
        }

        if (!empty($dataAttributes)) {
            $safeAttributes[$this->dataAttribute] = $this->serialize($dataAttributes);
        }

        return $safeAttributes;
    }

    /**
     * Processes the raw entity attributes from the persistent storage, converting serialized data into
     * actual attributes list.
     *
     * @param array $attributes raw attribute values from persistent storage.
     * @return array actual attribute values
     */
    protected function unserializeAttributes(array $attributes): array
    {
        if ($this->dataAttribute === null) {
            return $attributes;
        }

        if (empty($attributes[$this->dataAttribute])) {
            unset($attributes[$this->dataAttribute]);
            return $attributes;
        }

        $dataAttributes = $this->unserialize($attributes[$this->dataAttribute]);
        unset($attributes[$this->dataAttribute]);

        return array_merge($attributes, $dataAttributes);
    }

    /**
     * Serializes array value into a string.
     *
     * @param array $value
     * @return string
     */
    protected function serialize(array $value): string
    {
        return json_encode($value);
    }

    /**
     * Unserializes value from string.
     *
     * @param string $value
     * @return array
     */
    protected function unserialize(string $value): array
    {
        return json_decode($value, true);
    }
}
