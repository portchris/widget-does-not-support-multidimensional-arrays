<?php

/**
 * This addresses the core Magento issue whereby Widget's values cannot handle multi-dimensional arrays
 *
 * @see
 */

namespace Convert\WidgetDoesNotSupportMultidimensionalArrays\Plugin\Widget;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Widget\Model\Widget as Original;

/**
 * @package Convert\WidgetDoesNotSupportMultidimensionalArrays\Plugin\Widget
 */
class WidgetPlugin
{
    /**
     * @var Serializer
     */
    private $_serializer;

    /**
     * @var EncryptorInterface
     */
    private $_encryptor;

    /**
     * @param EncryptorInterface $escaper
     * @param Serializer $serializer
     */
    public function __construct(
        EncryptorInterface $escaper,
        SerializerInterface  $serializer
    ) {
        $this->_encryptor = $escaper;
        $this->_serializer = $serializer;
    }

    /**
     * Return widget presentation code in WYSIWYG editor
     *
     * @param Original $subject
     * @param string $type Widget Type
     * @param array $params Pre-configured Widget Params
     * @param bool $asIs Return result as widget directive(true) or as placeholder image(false)
     * @return string Widget directive ready to parse
     */
    public function beforeGetWidgetDeclaration(Original $subject, ...$args): array
    {
        /**
         * @var array $params Pre-configured Widget Params
         */
        $params = $args[1];
        if (is_array($params) && count($params) && $this->_isMultidimensionalArray($params)) {
            foreach ($params as &$value) {
                if ($this->_isMultidimensionalArray($value)) {
                    foreach ($value as $rowId => &$row) {
                        if (is_string($row)) {
                            unset($value[$rowId]);
                        }
                    }
                    $value = $this->_encryptor->encrypt(
                        $this->_serializer->serialize(
                            $value
                        )
                    );
                }
            }
            $args[1] = $params;
        }

        return $args;
    }

    /**
     * Check if value is a multi-dimensional array
     *
     * @param mixed $a
     * @return boolean
     */
    private function _isMultidimensionalArray($a): bool
    {
        $rv = (is_array($a))
            ? array_filter($a, 'is_array')
            : [];

        return (count($rv) > 0)
            ? true
            : false;
    }
}
