<?php

namespace Convert\WidgetDoesNotSupportMultidimensionalArrays\Block\Adminhtml\Widget\Field;

use Convert\WidgetDoesNotSupportMultidimensionalArrays\Block\Adminhtml\Form\Field\EventParameters as EventParametersForm;
use Exception;
use InvalidArgumentException;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class EventParameters
 */
class EventParameters extends Widget
{
    /**
     * @var EncryptorInterface
     */
    private $_encryptor;

    /**
     * @var SerializerInterface
     */
    private $_serialiser;

    /**
     * @param SerializerInterface $serialiser
     * @param EncryptorInterface $encryptor
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        SerializerInterface $serialiser,
        EncryptorInterface $encryptor,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_serialiser = $serialiser;
        $this->_encryptor = $encryptor;
    }

    /**
     * Prepare chooser element HTML
     *
     * @param AbstractElement $element Form Element
     * @return AbstractElement
     */
    public function prepareElementHtml(AbstractElement $element)
    {
        /** @var EventParametersForm $eventer */
        $eventer = $this
            ->getLayout()
            ->createBlock(EventParametersForm::class);

        if ($element->getValue() && $this->isEncrypted($element->getValue())) {
            $decryptedValue = $this->_encryptor->decrypt($element->getValue());
            if ($this->isSerialised($decryptedValue)) {
                $value = $this->_serialiser->unserialize($decryptedValue);
                $element->setValue($value);
            }
        }

        $eventer
            ->setElement($element)
            ->setConfig($this->getConfig())
            ->setFieldsetId($this->getFieldsetId());

        $element->setData('after_element_html', $eventer->toHtml());
        return $element;
    }

    /**
     * Check value is encrypted
     *
     * @param string $string
     * @return boolean
     */
    protected function isEncrypted(string $string): bool
    {
        try {
            return (strlen($this->_encryptor->decrypt($string)))
                ? true
                : false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * JSON Encode Serialisation (stolen from Magento\Framework\Serialize\SerializerInterface) but I didn't want to use constructors here ;)
     *
     * @see Magento\Framework\Serialize\SerializerInterface
     * @param string $string
     * @return mixed
     */
    // protected function unserialise(string $string)
    // {
    //     $result = json_decode($string, true);
    //     if (json_last_error() !== JSON_ERROR_NONE) {
    //         throw new InvalidArgumentException("Unable to unserialize value. Error: " . json_last_error_msg());
    //     }

    //     return $result;
    // }

    /**
     * Check if value is serialised
     *
     * @param string $string
     * @return bool
     */
    protected function isSerialised(string $string): bool
    {
        try {
            $unserialised = $this->_serialiser->unserialize($string);
            return (is_array($unserialised))
                ? true
                : false;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
}
