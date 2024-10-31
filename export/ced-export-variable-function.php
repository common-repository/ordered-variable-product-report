<?php 
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Ordered Variable Product Export functions and definitions
 * export_variable_csv Function.
 * exports the variable order product into csv file
 * @name wevop_export_variable_csv
 */
add_action( 'admin_init', 'wevop_export_variable_order_product_csv' );

/**
 * this function exports ordrered variable product in csv format
 * 
 * @name wevop_export_variable_order_product_csv
 * @author CedCommerce <plugins@cedcommerce.com>
 * @link http://cedcommerce.com/
 */
function wevop_export_variable_order_product_csv() {
	if( isset( $_POST[ 'wevop_export_var_order_product' ] ) ) {

		$args = array(
			'post_type' 	=> 'shop_order',
			'post_status' 	=> array_keys( wc_get_order_statuses() ),
			'posts_per_page' => '-1'
		);
		$query = new WP_Query($args);
		$orders = $query->get_posts();
		$head = array( 'Product', 'Quantity' );
		$final_items 	= array();
		foreach ( $orders as $orderInfo ) {
			if ( empty( $orderInfo ) ) {
				continue;
			}

			$orderId 		= $orderInfo->ID;
			$order 			= new WC_Order( $orderId );
			$line_items 	= $order->get_items( 'line_item' );
			$doneVariations = array();
			foreach ( $line_items as $item_id => $item ) {
				if ( empty( $item ) ) {
					continue;
				}
				
				$_product  = $order->get_product_from_item( $item );
				$product = wc_get_product( $item[ 'product_id' ] );
				
				
				if ( empty( $product ) ) {
					continue;
				}

				/**
				 * If product is not of variable type, move to next pointer without considering it.
				 */
				if( ! $product->is_type( 'variable' ) ) {
					continue;
				}

				$variation_id = '';
				if ( ! empty( $item['variation_id'] ) && 'product_variation' === get_post_type( $item['variation_id'] ) ) {
					$variation_id = esc_html( $item['variation_id'] );
				} elseif ( ! empty( $item['variation_id'] ) ) {
					$variation_id = esc_html( $item['variation_id'] );
				}
				

				if ( array_key_exists( $item[ 'product_id' ], $final_items ) ) {
					if ( array_key_exists( $variation_id, $final_items[ $item[ 'product_id' ] ] ) ) {
						$final_items[ $item[ 'product_id' ] ][ $variation_id ][ 'qty' ] += 1;
					} else {
						$final_items[ $item[ 'product_id' ] ][ $variation_id ][ 'title' ] 	= $product->post->post_title;
						$final_items[ $item[ 'product_id' ] ][ $variation_id ][ 'qty' ] 	= ( int )isset( $item[ 'qty' ] ) ? esc_html( $item[ 'qty' ] ) : 1;
						$doneVariations[] = esc_html( $item['variation_id'] );
					}
				} else {
					$final_items[ $item[ 'product_id' ] ][ $variation_id ][ 'title' ] 			= $product->post->post_title;
					$final_items[ $item[ 'product_id' ] ][ $variation_id ][ 'qty' ] 			= ( int )isset( $item[ 'qty' ] ) ? esc_html( $item[ 'qty' ] ) : 1;
					$doneVariations[] = $variation_id;
				}


				if ( $metadata = $order->has_meta( $item_id ) ) {
					foreach ( $metadata as $meta ) {

						$skippableMetas = array(
							'_qty',
							'_tax_class',
							'_product_id',
							'_variation_id',
							'_line_subtotal',
							'_line_subtotal_tax',
							'_line_total',
							'_line_tax',
							'method_id',
							'cost'
						);

						// Skip hidden core fields
						if ( in_array( $meta['meta_key'], $skippableMetas ) ) {
							continue;
						}

						// Skip serialised meta
						if ( is_serialized( $meta['meta_value'] ) ) {
							continue;
						}

						// Get attribute data
						if ( taxonomy_exists( wc_sanitize_taxonomy_name( $meta[ 'meta_key' ] ) ) ) {
							$term               	= get_term_by( 'slug', $meta[ 'meta_value' ], wc_sanitize_taxonomy_name( $meta['meta_key'] ) );
							$meta[ 'meta_key' ]   	= wc_attribute_label( wc_sanitize_taxonomy_name( $meta['meta_key'] ) );
							$meta[ 'meta_value' ] 	= isset( $term->name ) ? $term->name : $meta['meta_value'];
						} else {
							$meta['meta_key']   	= wc_attribute_label( $meta['meta_key'], $_product );
						}
						$final_items[ $item[ 'product_id' ] ][ $variation_id ][ 'attributes' ][ $meta[ 'meta_key' ] ] = $meta[ 'meta_value' ];
						$head[] = $meta['meta_key'];
					}
				}
			}
		}
		if ( empty( $final_items ) ) {
			return;
		}

		$csv_items 	= array();
		$counter 	= 0; 
		$head = array_unique( $head );
		$temp_head 	= $head;
		unset( $temp_head[0] );
		unset( $temp_head[1] );
		$temp_head = array_values( $temp_head );
		
		foreach ( $final_items as $item_id => $var_items ) {
			if ( empty( $var_items ) or ! is_array( $var_items ) ) {
				continue;
			}

			foreach ( $var_items as $var_id => $var_item ) {
				if ( empty( $var_item ) ) {
					continue;
				}

				if ( empty( $var_id ) ) {
					continue;
				}

				foreach ( $var_item as $key => $value ) {
					if ( $key == 'attributes' ) {
						foreach ( $temp_head as $k => $val ) {
							if ( array_key_exists( $val, $value ) ) {
								$csv_items[ $var_id ][] = $value[ $val ];
							} else {
								$csv_items[ $var_id ][] = '';
							}
						}
					} else {
						$csv_items[ $var_id ][] = $value;
					}
				}
			}
		}
		if ( empty( $csv_items ) ) {
			return;
		}
		
		$current_time = time();
		$csvName 		= "Report-{$current_time}.csv";
		header( 'Content-Encoding: UTF-8');
		header( "Content-type: application/csv; charset=UTF-8");
		header( "Content-Disposition: attachment; filename=\"{$csvName}\"" );
		header( "Pragma: no-cache" );
		header( "Expires: 0" );
		$fh = fopen( 'php://output', 'w' );
		fputcsv( $fh, $head );			
		foreach( $csv_items as $v_id => $product ) {
			fputcsv( $fh, $product );			
		}
		fclose($fh);
		exit();
	}
}