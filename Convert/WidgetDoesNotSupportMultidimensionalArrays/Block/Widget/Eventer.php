<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Convert\WidgetDoesNotSupportMultidimensionalArrays\Block\Widget;

use Convert\WidgetDoesNotSupportMultidimensionalArrays\Helper\Data as GAEEHelper;
use Exception;
use InvalidArgumentException;
use Magento\Cookie\Helper\Cookie;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\GoogleAnalytics\Block\Ga;
use Magento\GoogleAnalytics\Helper\Data as GAHelper;
use Magento\GoogleTagManager\Helper\Data as GTMHelper;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Widget\Block\BlockInterface;

/**
 * Widget to dynamically fire GTM events based on pre-defined user interaction
 */
class Eventer extends Ga implements BlockInterface
{
    /**
     * @var bool
     */
    protected $_isShared;

    /**
     * @var GAEEHelper
     */
    protected $_helper;

    /**
     * Proxied instance name
     * @var string
     */
    protected $_instanceName = null;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var SerializerInterface
     */
    protected $serialiser;

    /**
     * @param Context $context
     * @param SerializerInterface $serialiser
     * @param OrderCollectionFactory $salesOrderCollection
     * @param GAHelper $googleAnalyticsData
     * @param EncryptorInterface $encryptor
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     * @param bool $isShared
     * @param array $data
     * @param GAEEHelper|null $_helper
     * @param Cookie|null $cookieHelper
     */
    public function __construct(
        Context $context,
        SerializerInterface $serialiser,
        OrderCollectionFactory $salesOrderCollection,
        GAHelper $googleAnalyticsData,
        EncryptorInterface $encryptor,
        ObjectManagerInterface $objectManager,
        string $instanceName = 'Convert\\WidgetDoesNotSupportMultidimensionalArrays\\Helper\\Data',
        bool $isShared = true,
        array $data = [],
        GAEEHelper $helper = null,
        Cookie $cookieHelper = null
    ) {
        parent::__construct(
            $context,
            $salesOrderCollection,
            $googleAnalyticsData,
            $data,
            $cookieHelper
        );
        $this->serialiser = $serialiser;
        $this->encryptor = $encryptor;
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
        $this->_isShared = $isShared;
        $this->_helper = $helper;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['_helper', '_isShared', '_instanceName'];
    }

    /**
     * Retrieve ObjectManager from global scope
     */
    public function __wakeup()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * Clone proxied instance
     */
    public function __clone()
    {
        $this->_helper = clone $this->_getHelper();
    }

    /**
     * Get proxied instance
     *
     * @return GAEEHelper
     */
    private function _getHelper(): GAEEHelper
    {
        if (!$this->_helper) {
            $this->_helper = true === $this->_isShared
                ? $this->_objectManager->get($this->_instanceName)
                : $this->_objectManager->create($this->_instanceName);
        }

        return $this->_helper;
    }

    /**
     * @return string
     */
    public function layoutHandles(): string
    {
        $handles = (array) $this
            ->getLayout()
            ->getUpdate()
            ->getHandles();

        return implode(",", $handles);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return filter_var($this->getConfig(GAHelper::XML_PATH_ACTIVE), FILTER_VALIDATE_BOOLEAN) && strlen($this->containerId()) && $this->isGtmType();
    }

    public function isGtmType(): bool
    {
        return (class_exists(GTMHelper::class))
            ? GTMHelper::TYPE_TAG_MANAGER === $this->getConfig(GTMHelper::XML_PATH_TYPE)
            : true;
    }

    /**
     * @return string
     */
    public function enabledEvents(): string
    {
        return $this->serializer->serialize(explode(",", (string) $this->getConfig(GAEEHelper::CONFIG_PATH_GAEE_EVENTS_ENABLED)));
    }

    /**
     * @return string
     */
    public function containerId(): string
    {
        return (class_exists(GTMHelper::class))
            ? (string) $this->getConfig(GTMHelper::XML_PATH_CONTAINER_ID)
            : $this->getConfig(GAEEHelper::CONFIG_PATH_GAEE_GTM_CONTAINER_ID);
    }

    /**
     * Get widget GTM parameters
     *
     * @return string
     */
    public function gtmParameters(): string
    {
        $params = "";
        $encryptParams = $this->getParameters();
        if ($encryptParams && $this->isEncrypted($encryptParams)) {
            $paramsArr = $this->serialiser->unserialize(
                $this->encryptor->decrypt(
                    $encryptParams
                )
            );
            if (is_array($paramsArr)) {
                $data = [];
                foreach ($paramsArr as $p) {
                    $data[$p['key']] = $p['value'];
                }

                $action = $this
                    ->_getHelper()
                    ->newAction($this->gtmEvent(), $data);

                $params = str_replace(
                    "\"\"",
                    "\"",
                    str_replace(
                        "''",
                        "'",
                        str_replace(
                            "\"{",
                            "{",
                            str_replace(
                                "}\"",
                                "}",
                                stripcslashes(
                                    $this->_serialise($action)
                                )
                            )
                        )
                    )
                );
            }
        }

        return $params;
    }

    /**
     * Get widget GTM element selector
     *
     * @return string
     */
    public function gtmElementSelector(): string
    {
        return $this->getElement();
    }

    /**
     * Get widget GTM event JS observer
     *
     * @return string
     */
    public function gtmObserver(): string
    {
        return html_entity_decode($this->_escaper->escapeJs((string) $this->getObserver()));
    }

    /**
     * Get widget GTM event
     *
     * @return string
     */
    public function gtmEvent(): string
    {
        return html_entity_decode($this->_escaper->escapeJs((string) $this->getEvent()));
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
            return (strlen($this->encryptor->decrypt($string)))
                ? true
                : false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * JSON encode serialisation (stolen from Magento\Framework\Serialize\SerializerInterface) but I didn't want to use constructors here ;)
     *
     * @see Magento\Framework\Serialize\SerializerInterface
     * @param array $string
     * @return string
     */
    private function _serialise(array $data): string
    {
        try {
            return $this->serialiser->serialize($data);
        } catch (InvalidArgumentException $e) {
            return "{}";
        }
    }

    // /**
    //  * JSON decode serialisation (stolen from Magento\Framework\Serialize\SerializerInterface) but I didn't want to use constructors here ;)
    //  *
    //  * @see Magento\Framework\Serialize\SerializerInterface
    //  * @param string $string
    //  * @return array
    //  */
    // public function unserialise(string $string): array
    // {
    //     $result = json_decode($string, true);
    //     if (json_last_error() !== JSON_ERROR_NONE) {
    //         throw new InvalidArgumentException("Unable to unserialize value. Error: " . json_last_error_msg());
    //     }

    //     return ($result)
    //         ? $result
    //         : [];
    // }

    /**
     * Check if value is serialised
     *
     * @param string $string
     * @return bool
     */
    public function isSerialised(string $string): bool
    {
        try {
            $unserialised = $this->serialiser->unserialize($string);
            return (is_array($unserialised))
                ? true
                : false;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
}
