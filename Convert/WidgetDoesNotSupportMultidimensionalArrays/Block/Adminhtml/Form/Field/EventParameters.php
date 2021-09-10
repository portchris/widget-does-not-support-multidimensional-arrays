<?php

namespace Convert\WidgetDoesNotSupportMultidimensionalArrays\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class EventParameters extends AbstractFieldArray
{
    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('key', ['label' => __('Key'), 'class' => 'required-entry no-whitespace validate-data']);
        $this->addColumn('value', ['label' => __('Value'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Parameter');
    }
}
