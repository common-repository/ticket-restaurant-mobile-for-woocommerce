<?php
$order = new WC_Order($post->ID);
echo '<p>';

$payment_method = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_payment_method() : $order->payment_method;
$payment_method_title = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_payment_method_title() : $order->payment_method_title;
$order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;

switch ($payment_method) {
  case 'ticketrestaurant_mobile':
    echo '<img src="' . plugins_url('images/ticket-restaurant-mobile-icon.png', dirname(dirname(__FILE__))) . '" alt="' . esc_attr($payment_method_title) . '" title="' . esc_attr($payment_method_title) . '" /><br />';
    echo '<strong>' . __('Reference', 'ticket-restaurant-mobile-for-woocommerce') . '</strong>: ' . trim(get_post_meta($post->ID, '_ticketrestaurant_reference', true)) . '<br/>';
    echo '<strong>' . __('Phone Number', 'ticket-restaurant-mobile-for-woocommerce') . '</strong>: ' . trim(get_post_meta($post->ID, '_ticketrestaurant_phone_number', true)) . '<br/>';
    echo '<strong>' . __('Value', 'ticket-restaurant-mobile-for-woocommerce') . '</strong>: ' . wc_price($order_total);
    break;

  default:
    echo __('No details available', 'ticket-restaurant-mobile-for-woocommerce');
    break;
}
echo '</p>';
