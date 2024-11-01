<?php if (!defined('ABSPATH')) exit;?>
<style type="text/css">
  table.woocommerce_ticketrestaurant_table {
    width: auto !important;
    margin: auto;
  }

  table.woocommerce_ticketrestaurant_table td,
  table.woocommerce_ticketrestaurant_table th {
    background-color: #FFFFFF;
    color: #000000;
    padding: 10px;
    vertical-align: middle;
  }

  table.woocommerce_ticketrestaurant_table th {
    text-align: center;
    font-weight: bold;
  }

  table.woocommerce_ticketrestaurant_table th img {
    margin: auto;
    margin-top: 10px;
  }
</style>
<?php if ($method == 'ticketrestaurant_mobile') : ?>
  <table class="woocommerce_ticketrestaurant_table" cellpadding="0" cellspacing="0">
    <tr>
      <th colspan="2">
        <?php _e('Payment instructions', 'ticket-restaurant-mobile-for-woocommerce'); ?>
        <br />
        <img src="<?php echo plugins_url('images/ticket-restaurant-mobile-icon.png', dirname(__FILE__)); ?>" alt="<?php echo esc_attr($payment_name); ?>" title="<?php echo esc_attr($payment_name); ?>" />
      </th>
    </tr>
    <tr>
      <td><?php _e('Reference', 'ticket-restaurant-mobile-for-woocommerce'); ?>:</td>
      <td><?php echo $reference; ?></td>
    </tr>
    <tr>
      <td><?php _e('Phone Number', 'ticket-restaurant-mobile-for-woocommerce'); ?>:</td>
      <td><?php echo $phone_number; ?></td>
    </tr>
    <tr>
      <td><?php _e('Value', 'ticket-restaurant-mobile-for-woocommerce'); ?>:</td>
      <td><?php echo wc_price($order_total); ?></td>
    </tr>
    <tr>
      <td colspan="2" style="font-size: small;"><?php _e('Accept this payment at your Ticket Restaurant Mobile mobile app.', 'ticket-restaurant-mobile-for-woocommerce'); ?></td>
    </tr>
    <tr>
      <td colspan="2" style="font-size: small; text-align: center;"><span id="ticketrestaurant_resend_span"><?php _e('Não recebeu a notificação?', 'ticket-restaurant-mobile-for-woocommerce'); ?> <a href="#" id="ticketrestaurant_resend" data-order-id="<?php echo $order_id; ?>">Clique aqui para repetir</a></span></td>
    </tr>
  </table>

  <script>
    jQuery('#ticketrestaurant_resend').unbind().on('click', function(e) {
      e.preventDefault();

      var form_data = new FormData();

      form_data.append('action', 'resend_payment_notification');
      form_data.append('order_id', this.getAttribute('data-order-id'));
      
      // send post with files to ajax
      jQuery.ajax({
        url: "/wp-admin/admin-ajax.php",
        type: 'POST',
        contentType: false,
        processData: false,
        data: form_data,
        success: function(response) {
          if (response.success == true) {
            jQuery('#ticketrestaurant_resend_span').html('<?php _e('Payment request resent, check your device', 'ticket-restaurant-mobile-for-woocommerce'); ?>');
          } else {
            jQuery('#ticketrestaurant_resend_span').html('<?php _e('An error has ocurred', 'ticket-restaurant-mobile-for-woocommerce'); ?>');
          }
        },
        error: function(response, status, error) {
          jQuery('#ticketrestaurant_resend_span').html('<?php _e('An error has ocurred', 'ticket-restaurant-mobile-for-woocommerce'); ?>');
        }
      })

    });
  </script>
<?php endif; ?>