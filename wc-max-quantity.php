<?php
/**
 * Plugin Name: Max Quantity for Woocommerce
 * Plugin URI: https://example.com
 * Description: This plugin helps you to prevent any product form <b>add to cart</b> when its already added or the number exceeded
 * Version: 1.0.0
 * Author: Jakaria Istauk
 * Author URI: https://profiles.wordpress.org/jakariaistauk/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace JakariaIstaukPlugins;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Min_Max_Quantity_for_Woocommerce {
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'quantity_metabox' ] );
		add_action( 'save_post', [$this, 'save_qty_metabox_data'] );
		add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'prevent_add_to_cart' ], 10, 2 );
	}

	public function quantity_metabox() {
		add_meta_box(
			'pct_max_quantity',
			'Max Quantity',
			[ $this, 'quantity_input' ],
			'product',
			'side',
			'high'
		);
	}

	public function quantity_input( $post ) {
		// Get the saved value of the custom metabox
		$mmqw_max_quantity = get_post_meta( $post->ID, '_mmqw_max_quantity', true );
		$mmqw_error_notice = get_post_meta( $post->ID, '_mmqw_error_notice', true );

		// Output the HTML for the custom metabox
		?>
        <p>
            <label for="custom_value"><?php _e( 'Max Quantity', 'woocommerce' ); ?></label>
            <input type="number" id="mmqw_max_quantity" name="mmqw_max_quantity"
                   value="<?php echo esc_attr( $mmqw_max_quantity ); ?>">
        </p>
        <p>
            <label for="custom_value"><?php _e( 'Prevent Notice', 'woocommerce' ); ?></label>
            <textarea name="mmqw_error_notice"><?php echo esc_attr( $mmqw_error_notice ); ?></textarea>
        </p>
		<?php
	}

	function save_qty_metabox_data( $post_id ) {
		// Check if this is a product post
		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		// Save the custom value
		if ( isset( $_POST['mmqw_max_quantity'] ) ) {
			update_post_meta( $post_id, '_mmqw_max_quantity', sanitize_text_field( $_POST['mmqw_max_quantity'] ) );
		}

		if ( isset( $_POST['mmqw_error_notice'] ) ) {
			update_post_meta( $post_id, '_mmqw_error_notice', sanitize_textarea_field( $_POST['mmqw_error_notice'] ) );
		}
	}


	function prevent_add_to_cart( $valid, $added_product_id ) {

		$cart = WC()->cart;
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$existing_product      = $cart_item['data'];
			$quantity              = $cart_item['quantity'];
			$product_id            = $cart_item['product_id'];
			$max_quantity          = get_post_meta( $product_id, '_mmqw_max_quantity', true );
			$mmqw_error_notice     = get_post_meta( $product_id, '_mmqw_error_notice', true );
			$existing_product_name = $existing_product->get_name();

			$notice = $mmqw_error_notice ? $mmqw_error_notice : sprintf( __( 'You can not add more than %s %s(s). You already added %s %s(s)', 'woocommerce' ), $max_quantity, $existing_product_name, $max_quantity, $existing_product_name );

			if ( $added_product_id === $product_id && $max_quantity && $quantity >= $max_quantity ) {
				$valid = false;
				wc_add_notice( wp_kses_post( $notice ), 'error' );
			}
		}

		return $valid;

	}
}

// Initialize the plugin
add_action( 'plugins_loaded', function () {
	new Min_Max_Quantity_for_Woocommerce();
} );
