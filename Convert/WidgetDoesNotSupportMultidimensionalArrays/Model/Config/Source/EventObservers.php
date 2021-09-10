<?php

namespace Convert\WidgetDoesNotSupportMultidimensionalArrays\Model\Config\Source;

use Convert\WidgetDoesNotSupportMultidimensionalArrays\Helper\Data as GAEEHelper;
use Magento\Framework\Data\OptionSourceInterface;

class EventObservers implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => GAEEHelper::GAEE_OBSERVER_CLICK, 'label' => __('On Element Click')],
            ['value' => GAEEHelper::GAEE_OBSERVER_SCROLL, 'label' => __('On Element Scroll To')]
        ];
    }
}
