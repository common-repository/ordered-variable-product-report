<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_link  = $_product ? admin_url( 'post.php?post=' . absint( $_product->id ) . '&action=edit' ) : '';
$thumbnail     = $_product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $_product->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '';
$tax_data      = empty( $legacy_order ) && wc_tax_enabled() ? maybe_unserialize( isset( $item['line_tax_data'] ) ? $item['line_tax_data'] : '' ) : false;
$item_total    = ( isset( $item[ 'line_total' ] ) ) ? esc_attr( wc_format_localized_price( $item['line_total'] ) ) : '';
$item_subtotal = ( isset( $item[ 'line_subtotal' ] ) ) ? esc_attr( wc_format_localized_price( $item['line_subtotal'] ) ) : '';
?>
<div class="wovpe-order-variable-item-wrap">
	
	<!--==================================================
	=            Product image is shown here.            =
	===================================================-->

	<div class="wovpe-order-item-thumbnail wovpe-order-variable-item">
		<?php echo wp_kses_post( $thumbnail ); ?>
	</div>

	<!--====  End of Product image is shown here.  ====-->

	<!--===================================================
	=            Products information section.            =
	====================================================-->
	<div class="wovpe_item_info_wrap wovpe-order-variable-item">
			
		<?php 
		echo $product_link ? '<a href="' . esc_url( $product_link ) . '" class="wovpe-order-item-name">' .  esc_html( $item['name'] ) . '</a>' : '<div class="class="wc-order-item-name"">' . esc_html( $item['name'] ) . '</div>'; 

		/**
		 * Product sku
		 */
		if ( $_product && $_product->get_sku() ) {
			echo '<div class="wovpe-order-item-sku">';
				echo '<span><strong>' . __( 'SKU:', 'woocommerce' ) . '</strong></span> ';
				echo '<span>'. esc_html( $_product->get_sku() ) . '</span>';
			echo '</div>';
		}
		?>
		<div class="wovpe-order-item-prce item_cost" data-sort-value="<?php echo esc_attr( $order->get_item_subtotal( $item, false, true ) );?>">

			<span>
				<strong><?php echo __( 'Price:', 'woocommerce' ); ?></strong>
			</span>
			<span>
				<?php
				if ( isset( $item['line_total'] ) ) {
					echo wc_price( $order->get_item_total( $item, false, true ), array( 'currency' => $order->get_order_currency() ) );

					if ( isset( $item['line_subtotal'] ) && $item['line_subtotal'] != $item['line_total'] ) {
						echo '<span class="wc-order-item-discount">-' . wc_price( wc_format_decimal( $order->get_item_subtotal( $item, false, false ) - $order->get_item_total( $item, false, false ), '' ), array( 'currency' => $order->get_order_currency() ) ) . '</span>';
					}
				}
				?>
			</span>
		</div>
	
		<div class="wovpe-quantity">
			<span>
				<strong><?php _e( "Quantity ", 'ordered-variable-product-report' ); ?></strong>
			</span>
			<span>
				<?php echo ( isset( $item['qty'] ) ? esc_html( $item['qty'] ) : '1' ); ?>
			</span>
		</div>

		<div class="wovpe-order-item-variation-wrap">
			<?php 
			/**
			 * Product Variation id
			 */
			if ( ! empty( $item['variation_id'] ) ) {
				echo '<span><strong>' . __( 'Variation ID:', 'woocommerce' ) . '</strong></span> ';
				echo '<span>';
					if ( ! empty( $item['variation_id'] ) && 'product_variation' === get_post_type( $item['variation_id'] ) ) {
						echo esc_html( $item['variation_id'] );
					} elseif ( ! empty( $item['variation_id'] ) ) {
						echo esc_html( $item['variation_id'] ) . ' (' . __( 'No longer exists', 'woocommerce' ) . ')';
					}
				echo '</span>';
			}?>
		</div>
	</div>
	<!--====  End of Products information section.  ====-->

	<!--==========================================================
	=            Variation Attributes are shown here.            =
	===========================================================-->
	<div class="view wovpe_attributes wovpe-order-variable-item">
		<h3><?php _e( 'Attributes', 'ordered-variable-product-report' ); ?></h3>
		<?php 
		if ( $metadata = $order->has_meta( $item_id ) ) {

			echo '<table cellspacing="0" class="display_meta">';
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
				if ( taxonomy_exists( wc_sanitize_taxonomy_name( $meta['meta_key'] ) ) ) {
					$term               	= get_term_by( 'slug', $meta['meta_value'], wc_sanitize_taxonomy_name( $meta['meta_key'] ) );
					$meta[ 'meta_key' ]   	= wc_attribute_label( wc_sanitize_taxonomy_name( $meta['meta_key'] ) );
					$meta[ 'meta_value' ] 	= isset( $term->name ) ? $term->name : $meta['meta_value'];
				} else {
					$meta['meta_key']   	= wc_attribute_label( $meta['meta_key'], $_product );
				}

				echo '<tr>';
					echo '<th>' . wp_kses_post( rawurldecode( $meta['meta_key'] ) ) . ':</th>';
					echo '<td>' . wp_kses_post( wpautop( make_clickable( rawurldecode( $meta['meta_value'] ) ) ) ) . '</td>';
				echo '</tr>';
			}
			echo '</table>';
		}?>
	</div>
	<!--====  End of Variation Attributes are shown here.  ====-->
</div>