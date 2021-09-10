<?php

namespace Convert\WidgetDoesNotSupportMultidimensionalArrays\Model\Config\Source;

use Convert\WidgetDoesNotSupportMultidimensionalArrays\Helper\Data as GAEEHelper;
use Magento\Framework\Data\OptionSourceInterface;

class EventsOptions implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => GAEEHelper::TEST_VIEW, 'label' => __('Page View')],
            ['value' => GAEEHelper::TEST_VIEW_LIST, 'label' => __('View Category List')],
            ['value' => GAEEHelper::TEST_VIEW_ITEM, 'label' => __('View Product Item')],
            ['value' => GAEEHelper::TEST_CART_ADD, 'label' => __('Add to Cart/Basket')],
            ['value' => GAEEHelper::TEST_CART_VIEW, 'label' => __('View Cart/Basket')],
            ['value' => GAEEHelper::TEST_CART_REMOVE, 'label' => __('Remove from Cart/Basket')],
            ['value' => GAEEHelper::TEST_CHECKOUT_START, 'label' => __('Checkout Start')],
            ['value' => GAEEHelper::TEST_CHECKOUT_SHIPPING, 'label' => __('Checkout Shipping Info')],
            ['value' => GAEEHelper::TEST_CHECKOUT_PAYMENT, 'label' => __('Checkout Payment Info')],
            ['value' => GAEEHelper::TEST_PROMO_VIEW, 'label' => __('Promotion View')],
            ['value' => GAEEHelper::TEST_PROMO_CLICK, 'label' => __('Promotion Clicks')],
            ['value' => GAEEHelper::TEST_SELECT_CONTENT, 'label' => __('Select Content')],
            ['value' => GAEEHelper::TEST_ORDER_REFUND, 'label' => __('Order Refund')],
            ['value' => GAEEHelper::TEST_ORDER_REFUND_VIEW, 'label' => __('Order Refund View')],
            ['value' => GAEEHelper::TEST_ORDER_PURCHASE, 'label' => __('Order Purchases')],
            ['value' => GAEEHelper::TEST_LOGIN, 'label' => __('Customer Login')],
            ['value' => GAEEHelper::TEST_LOGOUT, 'label' => __('Customer Logout')],
            ['value' => GAEEHelper::TEST_SIGN_UP, 'label' => __('Customer Registration')],
            ['value' => GAEEHelper::TEST_NEWSLETTER_SUBSCRIBER_ADD, 'label' => __('Newsletter Subscriber Add')],
            ['value' => GAEEHelper::TEST_NEWSLETTER_SUBSCRIBER_REMOVE, 'label' => __('Newsletter Subscriber Remove')],
            ['value' => GAEEHelper::TEST_CURRENCY, 'label' => __('Display Currency')]
        ];
    }
}
