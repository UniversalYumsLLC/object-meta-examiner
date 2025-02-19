<?php
/**
 * Renders the meta data for orders, subscriptions and other post types.
 *
 * @package MetaViewer
 */

namespace MetaViewer;

use Automattic\WooCommerce\Utilities\OrderUtil;

class View extends Metabox {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Define the post types to display meta for.
		$types = array(
			'page',
			'post',
			'shop_order',
			'shop_subscription',
			'shop_coupon',
			'aw_workflow',
			'product',
			'product_variation',
			'woocommerce_page_wc-orders',
			'woocommerce_page_wc-orders--shop_subscription',
		);

		// Removes empty value from get_post_type() if not on that type.
		$types = array_filter( $types );

		$this->init(
			'meta_viewer',
			'Meta Viewer',
			$types,
			'normal',
			'low'
		);
	}

	/**
	 * Render our metabox.
	 *
	 * @param \WP_Post\WC_Object\WC_Subscription $object
	 * @return void
	 */
	public function render( $object ) {
		?>
		<style>
			#meta_viewer table {
				text-align: left;
				width: 100%;
				border: 1px solid #c3c4c7;
				border-spacing: 0;
			}
			#meta_viewer table th,
			#meta_viewer table td {
				display: inline-block;
				vertical-align: top;
				padding: .3em .75em;
			}
			#meta_viewer table th {
				width: calc(100% - 1.5em);
			}
			#meta_viewer table .key-column {
				width: calc(25% - 1.5em);
			}
			#meta_viewer table .value-column {
				width: calc(75% - 1.5em);
			}
			#meta_viewer code {
				word-wrap: break-word;
				word-break: break-all;
				padding: 2px 4px;
				border-radius: 3px;
			}
		</style>
		<?php

		if ( $object instanceof \WC_Subscription || $object instanceof \WC_Order ) {
			$meta = $this->get_order_meta();
		} else {
			$meta = $this->get_post_metadata();
		}

		$table = '<table class="striped">';

		foreach ( $meta as $section => $data ) {
			$table .= '<tr><th colspan="2">' . esc_html( $section ) . '</th></tr>';

			ksort( $data );

			foreach ( $data as $key => $value ) {
				$table .= $this->render_row( $key, $value );
			}
		}

		$table .= '</table>';

		echo $table;
	}

	/**
	 * Build the HTML for a single row in the table, or multiple rows if the value is an array or object.
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @return string
	 */
	public function render_row( $key, $value ) {
		// If value is empty, we still want to know that info, so just render the row regardless of type.
		// Also if it's not an array or object, we can just render it.
		if ( ! $value || ( ! is_array( $value ) && ! is_object( $value ) ) ) {
			return $this->get_row_html( $key, $value );
		}

		$row = '';

		foreach ( $value as $sub_value ) {
			$row .= $this->get_row_html( $key, $sub_value );
		}

		return $row;
	}

	/**
	 * Get the HTML for a single row in the table.
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @return string
	 */
	public function get_row_html( $key, $value ) {
		$value = var_export( $value, true );

		$row  = '<tr>';
		$row .= '<td class="key-column">' . esc_html( $key ) . '</td>';
		$row .= '<td class="value-column"><code>' . esc_html( $value ) . '</code></td>';
		$row .= '</tr>';

		return $row;
	}

	/**
	 * Get the metadata for the current post, for WP_Post type.
	 *
	 * @return array
	 */
	public function get_post_metadata() {
		global $post;

		$meta = array(
			'Record Data' => array(),
			'Other Data'  => array(),
		);

		if ( ! $post ) {
			return $meta;
		}

		// Set record data, based on the post object (the row in the posts table).
		$meta['Record Data'] = array(
			'ID'                => $post->ID,
			'post_author'       => $post->post_author,
			'post_date'         => $post->post_date,
			'post_date_gmt'     => $post->post_date_gmt,
			'post_content'      => $post->post_content,
			'post_title'        => $post->post_title,
			'post_status'       => $post->post_status,
			'post_type'         => $post->post_type,
			'post_modified'     => $post->post_modified,
			'post_modified_gmt' => $post->post_modified_gmt,
			'post_parent'       => $post->post_parent,
		);

		// Set metadata, based on entries in the postmeta table.
		$meta['Other Data'] = get_post_meta( $post->ID );

		return $meta;
	}

	/**
	 * Get the metadata for the current order or subscription based on how HPOS tables are organized.
	 *
	 * @return array
	 */
	public function get_order_meta() {
		/**
		 * @var \WC_Order $theorder
		 */
		global $theorder;

		$meta = array(
			'Record Data'      => array(),
			'Operational Data' => array(),
			'Address Data'     => array(),
			'Other Data'       => array(),
		);

		// If order is not set for some reason, just return the empty meta.
		if ( ! $theorder ) {
			return $meta;
		}

		$status = $theorder->get_status();
		if ( ! str_contains( $status, 'wc-' ) ) {
			$status = 'wc-' . $status;
		}

		// Get all the HPOS meta records, we might want to reference for legacy purposes.
		$object_meta = $this->get_all_hpos_meta( $theorder );

		// Set record data, based on the post object (the row in the posts table).
		$meta['Record Data'] = array(
			'ID'              => $theorder->get_id(),
			'status'          => $status,
			'type'            => $theorder->get_type(),
			'customer_id'     => $theorder->get_customer_id(),
			'date_created'    => $theorder->get_date_created() ? $theorder->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
			'date_updated'    => $theorder->get_date_modified() ? $theorder->get_date_modified()->date( 'Y-m-d H:i:s' ) : '',
			'parent_order_id' => $theorder->get_parent_id(),
			'customer_note'   => $theorder->get_customer_note(),
			'currency'        => $theorder->get_currency(),
			'ip_address'      => $theorder->get_customer_ip_address(),
			'user_agent'      => $theorder->get_customer_user_agent(),
		);

		// Some older records only use deprecated paid and completed date meta.
		$date_paid      = $theorder->get_date_paid() ? $theorder->get_date_paid()->date( 'Y-m-d H:i:s' ) : '';
		$date_completed = $theorder->get_date_completed() ? $theorder->get_date_completed()->date( 'Y-m-d H:i:s' ) : '';

		if ( ! $date_paid && isset( $object_meta['_paid_date'] ) ) {
			$date_paid = $object_meta['_paid_date'] . ' (legacy)';
		}
		if ( ! $date_completed && isset( $object_meta['_completed_date'] ) ) {
			$date_completed = $object_meta['_completed_date'] . ' (legacy)';
		}

		// Operational data based on the HPOS table structures.
		$meta['Operational Data'] = array(
			'billing_email'         => $theorder->get_billing_email(),
			'created_via'           => $theorder->get_created_via(),
			'woocommerce_version'   => $theorder->get_version(),
			'tax_amount'            => $theorder->get_total_tax(),
			'total_amount'          => $theorder->get_total(),
			'shipping_tax_amount'   => $theorder->get_shipping_tax(),
			'shipping_total_amount' => $theorder->get_shipping_total(),
			'discount_tax_amount'   => $theorder->get_discount_tax(),
			'discount_total_amount' => $theorder->get_discount_total(),
			'date_paid'             => $date_paid,
			'date_completed'        => $date_completed,
			'payment_method'        => $theorder->get_payment_method(),
			'payment_method_title'  => $theorder->get_payment_method_title(),
			'transaction_id'        => $theorder->get_transaction_id(),
			'cart_hash'             => $theorder->get_cart_hash(),
			'new_order_email_sent'  => $theorder->get_new_order_email_sent(),
			'order_key'             => $theorder->get_order_key(),
			'order_stock_reduced'   => $theorder->get_order_stock_reduced(),
			'recorded_sales'        => $theorder->get_recorded_sales(),
			'coupon_usage_counts'   => $theorder->get_recorded_coupon_usage_counts(),
		);

		// Address data based on the HPOS table structures.
		$meta['Address Data'] = array(
			'billing_first_name'  => $theorder->get_billing_first_name(),
			'billing_last_name'   => $theorder->get_billing_last_name(),
			'billing_company'     => $theorder->get_billing_company(),
			'billing_address_1'   => $theorder->get_billing_address_1(),
			'billing_address_2'   => $theorder->get_billing_address_2(),
			'billing_city'        => $theorder->get_billing_city(),
			'billing_state'       => $theorder->get_billing_state(),
			'billing_postcode'    => $theorder->get_billing_postcode(),
			'billing_country'     => $theorder->get_billing_country(),
			'billing_phone'       => $theorder->get_billing_phone(),
			'shipping_first_name' => $theorder->get_shipping_first_name(),
			'shipping_last_name'  => $theorder->get_shipping_last_name(),
			'shipping_company'    => $theorder->get_shipping_company(),
			'shipping_address_1'  => $theorder->get_shipping_address_1(),
			'shipping_address_2'  => $theorder->get_shipping_address_2(),
			'shipping_city'       => $theorder->get_shipping_city(),
			'shipping_state'      => $theorder->get_shipping_state(),
			'shipping_postcode'   => $theorder->get_shipping_postcode(),
			'shipping_country'    => $theorder->get_shipping_country(),
			'shipping_phone'      => $theorder->get_shipping_phone(),
		);

		$meta['Other Data'] = $object_meta;

		return $meta;
	}

	/**
	 * Let's get all the meta data from the HPOS meta table, because Woo sometimes abstracts its own
	 * properties and hides some meta data, but it would be useful to have this.
	 *
	 * @param \WC_Order|\WC_Subscription $object
	 * @return array
	 */
	public function get_all_hpos_meta( $object ) {
		/** @var \wpdb $wpdb */ // phpcs:ignore
		global $wpdb;

		$meta_table_name = OrderUtil::get_table_for_order_meta();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$meta_table_name} WHERE order_id = %d",
				$object->get_id()
			),
			ARRAY_A
		);

		$meta_data = array();

		foreach ( $results as $result ) {
			$meta_data[ $result['meta_key'] ] = $result['meta_value'];
		}

		return $meta_data;
	}
}
