<?php

/**
 * WC TicketRestaurant API Class.
 */
class WC_TicketRestaurant_API
{
  /**
   * Constructor.
   *
   * @param WC_TicketRestaurant_API
   */
  public function __construct()
  {
  }

  public function get_url()
  {
    return 'https://tr05.paycritical.com/api/';
  }

  public function get_api_key()
  {
    $settings = get_option('woocommerce_ticketrestaurant_mobile_settings');
    $apikey = $settings['apikey'];

    return $apikey;
  }

  /**
   * Money format.
   *
   * @param  int/float $value Value to fix.
   *
   * @return float            Fixed value.
   */
  protected function money_format($value)
  {
    return (float) number_format($value, 2, '.', '');
  }

  public function requestPayment($order_id, $value, $phone_number)
  {

    $data = array(
      'amount' => $this->money_format($value),
      'phoneNumber' => $phone_number,
      'orderRef' => (string) $order_id,
      'transactionType' => 'Capture'
    );

    $response = wp_remote_post(
      $this->get_url() . 'payment',
      [
        'method' => 'POST',
        'headers' => array(
          'Content-Type' => 'application/json',
          'Authorization' => 'Basic ' . $this->get_api_key(),
        ),
        'body' => json_encode($data)
      ]
    );

    $response_body = wp_remote_retrieve_body($response);

    return json_decode($response_body);
  }

  public function resendPayment($order_id)
  {
    if (!isset($order_id) || empty($order_id)) return;

    $order = wc_get_order($order_id);
    if (!$order->has_status('on-hold')) return false;

    $payment_id = get_post_meta($order_id, '_ticketrestaurant_payment_id', true);

    $data = array(
      'paymentId' => $payment_id,
    );

    if (!isset($payment_id) || empty($payment_id)) return false;

    $response = wp_remote_post(
      $this->get_url() . 'payment/resend',
      [
        'method' => 'POST',
        'headers' => array(
          'Content-Type' => 'application/json',
          'Authorization' => 'Basic ' . $this->get_api_key(),
        ),
        'body' => json_encode($data)
      ]
    );

    return $response['response']['code'];
  }

  public function get_payment_status($paymentId)
  {
    $response = wp_remote_get(
      $this->get_url() . 'Payment/' . $paymentId,
      [
        'method' => 'GET',
        'headers' => array(
          'Content-Type' => 'application/json',
          'Authorization' => 'Basic ' . $this->get_api_key(),
        ),
      ]
    );

    $response_body = wp_remote_retrieve_body($response);

    return json_decode($response_body);
  }
}
