<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php if ($method == 'ticketrestaurant_mobile') : ?>
  <table cellpadding="10" cellspacing="0" align="center" border="0" style="margin: auto; margin-top: 10px; margin-bottom: 10px; border-collapse: collapse; border: 1px solid #1465AA; border-radius: 4px !important; background-color: #FFFFFF;">
    <tr>
      <td style="border: 1px solid #1465AA; border-top-right-radius: 4px !important; border-top-left-radius: 4px !important; text-align: center; color: #000000; font-weight: bold;" colspan="2">
        <?php _e('Payment instructions', 'ticket-restaurant-mobile-for-woocommerce'); ?>
        <br/>
        <img src="<?php echo plugins_url('images/ticket-restaurant-mobile-icon.png', dirname(dirname(__FILE__))); ?>" alt="<?php echo esc_attr($payment_name); ?>" title="<?php echo esc_attr($payment_name); ?>" style="margin-top: 10px;"/>
      </td>
    </tr>
    <tr>
      <td style="border: 1px solid #1465AA; color: #000000;"><?php _e('Reference', 'ticket-restaurant-mobile-for-woocommerce'); ?>:</td>
      <td style="border: 1px solid #1465AA; color: #000000; white-space: nowrap;"><?php echo $reference; ?></td>
    </tr>
    <tr>
      <td style="border: 1px solid #1465AA; color: #000000;"><?php _e('Phone Number', 'ticket-restaurant-mobile-for-woocommerce'); ?>:</td>
      <td style="border: 1px solid #1465AA; color: #000000; white-space: nowrap;"><?php echo $phone_number; ?></td>
    </tr>
    <tr>
      <td style="border: 1px solid #1465AA; color: #000000;"><?php _e('Value', 'ticket-restaurant-mobile-for-woocommerce'); ?>:</td>
      <td style="border: 1px solid #1465AA; color: #000000; white-space: nowrap;"><?php echo $order_total; ?> &euro;</td>
    </tr>
    <tr>
      <td style="font-size: x-small; border: 1px solid #1465AA; border-bottom-right-radius: 4px !important; border-bottom-left-radius: 4px !important; color: #000000; text-align: center;" colspan="2"><?php _e('Accept this payment at your Ticket Restaurant Mobile mobile app.', 'ticket-restaurant-mobile-for-woocommerce'); ?></td>
    </tr>
  </table>
<?php else :
  echo '<p><strong>' . __('Error getting payment details', 'ticket-restaurant-mobile-for-woocommerce') . '</strong>';
endif; ?>
