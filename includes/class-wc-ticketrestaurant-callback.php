<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_TicketRestaurant_Callback
{

  protected static $instance = null;

  public function __construct()
  {
    $this->id = 'ticket-restaurant-mobile-for-woocommerce';

    add_action('woocommerce_api_wc_ticketrestaurant', array($this, 'callback_handler'));
  }

  function callback_log($message, $error = false)
  {

    if ($error == true) {
      $title = __('Error', 'ticket-restaurant-mobile-for-woocommerce');
      $response = 500;
    } else {
      $title = __('Success', 'ticket-restaurant-mobile-for-woocommerce');
      $response = 200;
    }

    wp_die($message, $title, array('response' => $response));
  }

  public function callback_handler()
  {
    $notify_code = get_option('ticketrestaurant_mobile_auth_code');

    if ($_SERVER['HTTP_AUTHORIZATION'] != 'Basic ' . $notify_code) {
      $this->callback_log(__('Auth failed', 'ticket-restaurant-mobile-for-woocommerce'), true);
    }

    $inputJSON = file_get_contents('php://input');
    if (!isset($inputJSON) || empty($inputJSON)) {
      $this->callback_log(__('Invalid data', 'ticket-restaurant-mobile-for-woocommerce'), true);
    }

    $input_data = json_decode($inputJSON);

    if (!isset($input_data->paymentId) || empty($input_data->paymentId)) {
      $this->callback_log(__('Payment Id error', 'ticket-restaurant-mobile-for-woocommerce'), true);
    }

    $orders = wc_get_orders(array(
      'ticketrestaurant_payment_id' => $input_data->paymentId,
    ));

    if (!isset($orders) || empty($orders)) {
      $this->callback_log(__('No orders found', 'ticket-restaurant-mobile-for-woocommerce'), true);
    }

    if (count($orders) > 1) {
      $this->callback_log(__('More than one order with this payment Id', 'ticket-restaurant-mobile-for-woocommerce'), true);
    }

    foreach ($orders as $order) {
      $api = new WC_TicketRestaurant_API($this);

      $status = $api->get_payment_status($input_data->paymentId);

      if (isset($status->status) && !empty($status->status)) {

        update_option('ticketrestaurant_mobile_callback_received', date('Y-m-d H:i:s'));

        switch ($status->status) {
          case 'Completed':
            if (!$order->has_status(array('on-hold', 'pending'))) {
              $this->callback_log(__('Order is not on-hold or pending status', 'ticket-restaurant-mobile-for-woocommerce'), true);
            }

            $order->add_order_note(__('Payment accepted by customer', 'ticket-restaurant-mobile-for-woocommerce'));
            $order->payment_complete('');
            $this->callback_log(__('Processing order', 'ticket-restaurant-mobile-for-woocommerce'));
            break;

          case 'RejectedByUser':
            if (!$order->has_status(array('on-hold', 'pending'))) {
              $this->callback_log(__('Order is not on-hold or pending status', 'ticket-restaurant-mobile-for-woocommerce'), true);
            }

            $order->add_order_note(__('Payment refused by customer', 'ticket-restaurant-mobile-for-woocommerce'));
            $order->update_status('cancelled');
            $this->callback_log(__('Order cancelled', 'ticket-restaurant-mobile-for-woocommerce'));
            break;

          case 'Expired':
            if (!$order->has_status(array('on-hold', 'pending'))) {
              $this->callback_log(__('Order is not on-hold or pending status', 'ticket-restaurant-mobile-for-woocommerce'), true);
            }

            $order->add_order_note(__('Payment expired', 'ticket-restaurant-mobile-for-woocommerce'));
            $order->update_status('failed');
            $this->callback_log(__('Order failed, payment expired', 'ticket-restaurant-mobile-for-woocommerce'));
            break;

          default:
            break;
        }
      }
    }

    $this->callback_log('Error', true);
  }
}
