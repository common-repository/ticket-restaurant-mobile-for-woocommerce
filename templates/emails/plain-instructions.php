<?php if (!defined('ABSPATH')) exit; ?>

<?php
if ($method == 'ticketrestaurant_mobile') :
  _e('Payment instructions', 'ticket-restaurant-mobile-for-woocommerce');
  echo "\n";
  _e('Entity', 'ticket-restaurant-mobile-for-woocommerce');
  echo ': ';
  echo $entidade;
  echo "\n";
  _e('Reference', 'ticket-restaurant-mobile-for-woocommerce');
  echo ': ';
  echo $reference;
  echo "\n";
  _e('Phone Number', 'ticket-restaurant-mobile-for-woocommerce');
  echo ': ';
  echo $phone_number;
  echo "\n";
  _e('Value', 'ticket-restaurant-mobile-for-woocommerce');
  echo ': ';
  echo $order_total;
  echo '&euro;';
  echo "\n";
  _e('Accept this payment at your Ticket Restaurant Mobile mobile app.', 'ticket-restaurant-mobile-for-woocommerce');
else :
  _e('Error getting payment details', 'ticket-restaurant-mobile-for-woocommerce');
endif;
?>