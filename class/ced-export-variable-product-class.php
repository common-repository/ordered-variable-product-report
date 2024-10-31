<?php 
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) 
{
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Ordered Variable Product Export functions and definitions
 * 
 * @name WEVOP
 * @author CedCommerce <plugins@cedcommerce.com>
 * @link http://cedcommerce.com/
 */
if( ! class_exists( 'WEVOP' ) ) {
	class WEVOP	{

		/**
		 * Intialize `$order_filter_date_from` variable to filter the orders.
		 * Will be set if start date is selected from variable products' orders table.
		 * @var string $order_filter_date_from
		 */
		public $order_filter_date_from 	= '';

		/**
		 * Intialize `$order_filter_date_to` variable to filter the orders.
		 * Will be set if end date is selected from variable products' orders table.
		 * @var string $order_filter_date_to
		 */
		public $order_filter_date_to 	= '';

		/**
		 * Intialize `$product_filter_date_from` variable to filter the items.
		 * Will be set if start date is selected from variable products.
		 * @var string $product_filter_date_from
		 */
		public $product_filter_date_from = '';

		/**
		 * Intialize `$product_filter_date_to` variable to filter the items.
		 * Will be set if end date is selected from variable products.
		 * @var string $product_filter_date_to
		 */
		public $product_filter_date_to 	= '';

		/**
		 * Intialize `$product_filter_date_to` variable to filter the items.
		 * Will be set if end date is selected from variable products.
		 * @var string $product_filter_date_to
		 */
		public $product_order_filter 	= array();

		/**
		 * Intialize `$order_filter_by_status` variable to filter the orders.
		 * Will be set if orders status is selected to filter the orders.
		 * @var string $product_filter_date_to
		 */
		public $order_filter_by_status 	= '';

		/**
		 * This function is constructor where all action and hooks are initiated.
		 * @name __construct()
		 * @author CedCommerce <plugins@cedcommerce.com>
	 	 * @link http://cedcommerce.com/
		 */
		public function __construct() {

			/**
			 * Set variable products' orders page title.
			 * @var string
			 */
			$this->title = __( 'Orders of variable products.', 'ordered-variable-product-report' );

			if( ! session_id() ) {
				session_start();
			}

			add_action( 'admin_init', array( $this, 'wovpr_initialize' ) );
			add_action( 'wp_ajax_set_ovpr_user_roles', array( $this,'set_ovpr_user_roles'));
			add_action( 'wp_ajax_nopriv_set_ovpr_user_roles', array( $this,'set_ovpr_user_roles'));
			add_action( 'wp_ajax_set_ovpr_user_roles_uncheck', array( $this,'set_ovpr_user_roles_uncheck'));
			add_action( 'wp_ajax_nopriv_wp_ajax_set_ovpr_user_roles_uncheck', array( $this,'set_ovpr_user_roles_uncheck'));
			add_action( "admin_enqueue_scripts", array( $this, "ced_wovpe_enqueue_script" ) );
			add_action( 'wovpr_empty_variable_orders_list', array( $this, 'wovpr_empty_variable_orders_list' ) );
			add_action( 'wovpr_orders_list_start_table', array( $this, 'wovpr_orders_list_start_table' ) );
			add_action( 'wovpr_orders_list_end_table', array( $this, 'wovpr_orders_list_end_table' ), 10, 4 );
			add_action( 'wovpr_variable_orders_filter_html', array( $this, 'wovpr_variable_orders_filter_html' ) );
			add_action( 'admin_menu', array( $this, 'wevop_register_ordered_product_page' ) );
			add_action( 'admin_footer', array( $this, 'wevop_add_thickbox_html' ) );
			add_action('wp_loaded',array($this,'ced_wovpr_close_email_image'));
			add_action("wp_ajax_ced_wovpr_send_mail",array($this,"ced_wovpr_send_mail"));
		}

		function ced_wovpr_send_mail()
		{
			if(isset($_POST["flag"]) && $_POST["flag"]==true && !empty($_POST["emailid"]))
			{
				$to = "support@cedcommerce.com";
				$subject = "Wordpress Org Know More";
				$message = 'This user of our woocommerce extension "Ordered Variable Product Report" wants to know more about marketplace extensions.<br>';
				$message .= 'Email of user : '.$_POST["emailid"];
				$headers = array('Content-Type: text/html; charset=UTF-8');
				$flag = wp_mail( $to, $subject, $message);	
				if($flag == 1)
				{
					echo json_encode(array('status'=>true,'msg'=>__('Soon you will receive the more details of this extension on the given mail.',"ordered-variable-product-report")));
				}
				else
				{
					echo json_encode(array('status'=>false,'msg'=>__('Sorry,an error occured.Please try again.',"ordered-variable-product-report")));
				}
			}
			else
			{
				echo json_encode(array('status'=>false,'msg'=>__('Sorry,an error occured.Please try again',"ordered-variable-product-report")));
			}
			wp_die();

		}

		function ced_wovpr_close_email_image()
		{
			if(isset($_GET["ced_wovpr_close"]) && $_GET["ced_wovpr_close"]==true)
			{
				unset($_GET["ced_wovpr_close"]);
				if(!session_id())
					session_start();
				$_SESSION["ced_wovpr_hide_email"]=true;
			}
		}
		/**
		 * Intializes required data.
		 * @return [type] [description]
		 */
		function wovpr_initialize() {

			/**
			 * If date filetring is applied.
			 * Set the date variables by value of posted dates.
			 */
			if ( isset( $_POST[ 'wovpr_filter_variable_orders' ] ) ) {
				$this->order_filter_date_from 	= isset( $_POST[ 'wovpr_datepicker_from' ] ) ? $_POST[ 'wovpr_datepicker_from' ] : '';
				$this->order_filter_date_to 	= isset( $_POST[ 'wovpr_datepicker_to' ] ) ? $_POST[ 'wovpr_datepicker_to' ] : '';
				$this->order_filter_by_status 	= isset( $_POST[ 'wovpr_filter_by_status' ] ) ? $_POST[ 'wovpr_filter_by_status' ] : '';
				$this->product_order_filter 	= isset( $_POST[ 'wovpr_filter_by_items' ] ) ? $_POST[ 'wovpr_filter_by_items' ] : '';
			}

			if ( isset( $_POST[ 'show_variation_product' ] ) ) {
				$this->product_filter_date_from = isset( $_POST[ 'wovpr_datepicker_from' ] ) ? $_POST[ 'wovpr_datepicker_from' ] : '';
				$this->product_filter_date_to 	= isset( $_POST[ 'wovpr_datepicker_to' ] ) ? $_POST[ 'wovpr_datepicker_to' ] : '';
			}
		}

		/**
		 * This function to set user roles
		 * @name set_ovpr_user_roles
		 * @author CedCommerce <plugins@cedcommerce.com>
	 	 * @link http://cedcommerce.com/ 
		 */
		function set_ovpr_user_roles() {
			$roles 		= sanitize_text_field($_POST['roles']);
			$rolearr 	= get_option( "ovpr_view_mode_selected", array() );
			$rolearr[] = $roles;
			if( update_option( "ovpr_view_mode_selected", $rolearr ) ) {
				echo "success";
				wp_die();
			} else {
				echo "failed";
				wp_die();
			}
		}

		function set_ovpr_user_roles_uncheck() {
			$roles 		= sanitize_text_field( $_POST[ 'roles' ] );
			$rolearr 	= get_option( "ovpr_view_mode_selected", array() );
			if( isset( $rolearr ) && !empty( $rolearr ) ) {
				foreach( $rolearr as $key => $value ) {
					if( $value == $roles ) {
						unset( $rolearr[$key] );
					}
				}

				if( update_option( "ovpr_view_mode_selected", $rolearr ) ) {
					echo "success";
					wp_die();
				} else {
					echo "failed";
					wp_die();
				}
			} else {
				echo "failed1234";wp_die();
			}
		}

		public function wevop_add_thickbox_html() {?>
		<div id="wovpe_ordered_variable_product_thickbox_wrap" style="display: none;">
			<div id="wovpe_ordered_variable_product_thickbox"></div>
		</div>
		<?php 
	}


		/**
		 * This function enqueue all necessary scripts
		 * @name ced_wovpe_enqueue_script
		 * @author CedCommerce <plugins@cedcommerce.com>\
		 * @link http://cedcommerce.com/ 
		 */
		function ced_wovpe_enqueue_script() {
			global $wp_scripts;
			wp_enqueue_style( 'woocommerce_admin_styles1', WC()->plugin_url() . '/assets/css/admin.css', array() );
			wp_enqueue_style( 'op-custom', plugins_url( '../assets/css/custom-style.css', __FILE__ ) );
			wp_enqueue_style( 'ced-wovpr-custom', plugins_url( '../assets/css/ced_wovpr_custom.css', __FILE__ ) );
			wp_register_script( 'wc-admin-order-meta-boxes', WC()->plugin_url() . '/assets/js/admin/meta-boxes-order.min.js', array( 'wc-admin-meta-boxes', 'wc-backbone-modal' ), WOPE_VERSION, true );
			wp_enqueue_script( 'wc-admin-order-meta-boxes' );

			wp_enqueue_script( 'ced_wovpe_js', plugins_url( '../assets/js/ced_wovpe.js', __FILE__ ), WOPE_VERSION, true );
			wp_enqueue_script( 'ced-wovpr-custom-script', plugins_url( '../assets/js/ced_wovpr_custom.js', __FILE__ ), WOPE_VERSION, true );
			wp_localize_script("ced-wovpr-custom-script","ajax_url",admin_url('admin-ajax.php'));

			$thckbox_ordered_products = add_query_arg( 
				array(
					'TB_inline' => 'true',
					'width'     => 640,
					'height'    => 360,
					'inlineId'  => 'wovpe_ordered_variable_product_thickbox_wrap',
					)
				);

			$translation_array = array(
				'ajaxurl' 			=> 	admin_url( 'admin-ajax.php' ),
				'variable_items' 	=> 	__( 'Variable products of this order.', 'ordered-variable-product-report' ),
				'thckbxOrderProduct'=>	$thckbox_ordered_products
				);
			
			wp_localize_script( 'ced_wovpe_js', 'globals', $translation_array );
			wp_enqueue_style( 'datepicker-style', plugins_url( '../assets/css/datepicker.css', __FILE__ ) );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			add_thickbox();
		}

		function wovpr_variable_orders_filter_html() {?>
		<h3><?php _e( 'Filter By:' ) ?></h3>
		<div class="alignleft actions wovpr-actions">
			<p class="wovpr_label_p">
				<?php _e( 'Date', 'ordered-variable-product-report' );?>
			</p>
			<input type="text" class="wovpr_datepicker" id="wovpr_datepicker_from" name="wovpr_datepicker_from" value="<?php echo $this->order_filter_date_from; ?>" placeholder="<?php _e( 'Select start date', 'ordered-variable-product-report' );?>">
			<input type="text" class="wovpr_datepicker" id="wovpr_datepicker_to" name="wovpr_datepicker_to" value="<?php echo $this->order_filter_date_to; ?>" placeholder="<?php _e( 'Select end date', 'ordered-variable-product-report' );?>">
		</div>
		<div class="alignleft actions wovpr-actions">
			<p class="wovpr_label_p">
				<?php _e( 'Products name', 'ordered-variable-product-report' );?>
			</p>
			<p class="form-field form-field-wide">
				<select name="wovpr_filter_by_items[]" class="wovpr-select2" multiple="multiple" placeholder="<?php _e( 'Select products', 'ordered-variable-product-report' ); ?>">
					<?php 
					$args = array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => '-1' );
					$query 	= new WP_Query(  $args  );
					$items = $query->get_posts( $args );
					if ( empty( $items ) ) {
						return ;
					}

					foreach ( $items as $item ) {
						if ( empty( $item ) ) {
							continue;
						}

						$product = wc_get_product( $item->ID );

							/**
							 * If product is not found, move to next pointer.
							 */
							if ( empty( $product ) ) {
								continue;
							}

							/**
							 * If product is not of variable type, move to next pointer without considering it.
							 */
							if( ! $product->is_type( 'variable' ) ) {
								continue;
							}

							$selected = '';
							if ( !empty( $this->product_order_filter ) and in_array( $item->ID, $this->product_order_filter ) ) {
								$selected = 'selected';
							}

							echo '<option value="' . esc_attr( $item->ID ) . '" '. esc_attr( $selected ) .'>' . esc_html( $item->post_title ) . '</option>';
						}
						?>
					</select>
				</p>
			</div>
			<div class="alignleft actions wovpr-actions">
				<p class="wovpr_label_p">
					<?php _e( 'Status', 'ordered-variable-product-report' ); ?>
				</p>
				<select id="" name="wovpr_filter_by_status" class="wc-enhanced-select">
					<option value=""><?php _e( 'All', 'ordered-variable-product-report' ); ?></option>
					<?php

					$statuses = wc_get_order_statuses();
					foreach ( $statuses as $status => $status_name ) {
						$selected = '';
						if ( $this->order_filter_by_status == $status ) {
							$selected = 'selected';
						}
						echo '<option value="' . esc_attr( $status ) . '" '. esc_attr( $selected ) .'>' . esc_html( $status_name ) . '</option>';
					}
					?>
				</select>
				<input type="submit" value="Filter" class="button" name="wovpr_filter_variable_orders">
			</div>
			<?php 
		}

		function wovpr_empty_variable_orders_list() {?>
		<div class="ced-pro-sidebar">
			<div class="tablenav top">
				<div class="alignleft actions bulkactions">
					<form method="post">
						<?php do_action( 'wovpr_variable_orders_filter_html' ); ?>
					</form>	
				</div>
			</div>
			<table class="wovpe_variable_product_orders wp-list-table widefat striped posts">
				<thead>
					<tr class="wovpe_variable_product_order_head">
						<th class="wope_order_column manage-column column-order_status" scope="col">
							<span class="status_head tips" data-tip="<?php _e( 'Status', 'ordered-variable-product-report' ); ?>">
								<?php _e( 'Status', 'ordered-variable-product-report' ); ?>
							</span>
						</th>
						<th id="wope_order_title order_title" class="wope_order_column manage-column column-primary">
							<?php _e( 'Order', 'ordered-variable-product-report' ); ?>
						</th>
						<th id="wope_order_items order_items" class="wope_order_column manage-column column-primary">
							<?php _e( 'Purchased', 'ordered-variable-product-report' ); ?>
						</th>
						<th id="wope_shipping_address shipping_address" class="wope_order_column manage-column column-primary">
							<?php _e( 'Ship to', 'ordered-variable-product-report' ); ?>
						</th>
						<th class="wope_order_column manage-column column-primary">
							<?php _e( 'Order Total', 'ordered-variable-product-report' ); ?>
						</th>
						<th class="wope_order_column manage-column column-primary">
							<?php _e( 'Date', 'ordered-variable-product-report' ); ?>
						</th>
						<th class="wope_order_column manage-column column-primary">
							<?php _e( 'Actions', 'ordered-variable-product-report' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="7">
							<?php _e( 'No variable products orders found through your query.', 'ordered-variable-product-report' ); ?>
						</td>
					</tr>
				</tbody>

				<tfoot>
					<tr>
						<th class="" colspan="2">
							<span>
								<strong><?php _e( 'Total Orders:', 'ordered-variable-product-report' ); ?></strong>
							</span>
							<span>0</span>
						</th>
						<th class="" colspan="2">
							<span>
								<strong><?php _e( 'Variable Products Total:', 'ordered-variable-product-report' ); ?></strong>
							</span>
							<span>
								<span class="woocommerce-Price-amount amount">
									<span class="woocommerce-Price-currencySymbol">$</span>
									0.0
								</span>							
							</span>
						</th>	
						<th class="" colspan="4">
							<span>
								<strong><?php _e( 'Orders Total:', 'ordered-variable-product-report' );?></strong>
							</span>
							<span>
								<span class="woocommerce-Price-amount amount">
									<span class="woocommerce-Price-currencySymbol">$</span>0.0</span>
								</span>
							</th>
						</tr>
					</tfoot>
				</table>
			</div>

			<?php  
			if(!session_id())
				session_start();
			if(!isset($_SESSION["ced_wcswr_hide_email"])):	
				$actual_link = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$urlvars = parse_url($actual_link);
			$url_params = $urlvars["query"];
			?>
			<div class="ced_wcswr_img_email_image">
				<div class="ced_wcswr_email_main_content">
					<div class="ced_wcswr_cross_image">
						<a class="button-primary ced_wcswr_cross_image_link" href="?<?php echo $url_params?>&ced_wcswr_close=true">x</a>
					</div>
					<div class="ced-recom">
						<h4>Cedcommerce recommendations for you </h4>
					</div>
					<div class="wramvp_main_content__col">
						<p> 
							Looking forward to evolve your eCommerce?
							<a href="http://bit.ly/2LB1lZV" target="_blank">Sell on the TOP Marketplaces</a>
						</p>
						<div class="wramvp_img_banner">
							<a target="_blank" href="http://bit.ly/2LB1lZV"><img alt="market-place" src="<?php echo plugins_url().'/ordered-variable-product-report/assets/images/market-place-2.jpg'?>"></a> 
						</div>
					</div>
					<br>
					<div class="wramvp_main_content__col">
						<p> 
							Leverage auto-syncing centralized order management and more with our
							<a href="http://bit.ly/2LB71TJ" target="_blank">Integration Extensions</a> 
						</p>
						<div class="wramvp_img_banner">
							<a target="_blank" href="http://bit.ly/2LB71TJ"><img alt="market-place" src="<?php echo plugins_url().'/ordered-variable-product-report/assets/images/market-place.jpg'?>"></a> 
						</div>
					</div>
					<div class="wramvp-support">
						<ul>
							<li>
								<span class="wramvp-support__left">Contact Us :-</span>
								<span class="wramvp-support__right"><a href="mailto:support@cedcommerce.com"> support@cedcommerce.com </a></span>
							</li>
							<li>
								<span class="wramvp-support__left">Get expert's advice :-</span>
								<span class="wramvp-support__right"><a href="https://join.skype.com/bovbEZQAR4DC"> Join Us</a></span>
							</li>
						</ul>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<?php 
	}

		/**
		 * This function is to registers the submenu.
		 * 
		 * @name wevop_register_ordered_product_page
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		function wevop_register_ordered_product_page() {

			if( ! current_user_can( 'manage_woocommerce') ) {
				add_menu_page( __( 'Export Variable Ordered Products', 'ordered-variable-product-report' ), __( 'Export Variable Ordered Products', 'ordered-variable-product-report' ), 'read', 'wc-export_variable_products', array( $this, 'wevop_show_orderd_products' ) );
			} else {
				add_menu_page( __( 'Ordered Variable Products Report', 'ordered-variable-product-report' ), __( 'Ordered Variable Products Report', 'ordered-variable-product-report' ), 'manage_woocommerce', 'wc-export_variable_products', array( $this, 'wevop_show_orderd_products' ) );
				add_submenu_page( 'wc-export_variable_products', __( 'Variable Product Orders', 'ordered-variable-product-report' ), __( 'Variable Product Orders', 'ordered-variable-product-report' ), 'read', 'wc-variable_product_orders', array( $this, 'wevop_show_variable_product_orders' ) );
			}
		}

		function wovpr_orders_list_start_table() {?>
		<div class="ced-pro-sidebar">
			<div class="tablenav top">
				<form method="post" class="wovpr-actions-form">
					<?php do_action( 'wovpr_variable_orders_filter_html' ); ?>
				</form>	
			</div>
			<table class="wovpe_variable_product_orders wp-list-table widefat striped posts">
				<thead>
					<tr class="wovpe_variable_product_order_head">
						<th class="wope_order_column manage-column column-order_status" scope="col">
							<span class="status_head tips" data-tip="<?php _e( 'Status', 'ordered-variable-product-report' ); ?>">
								<?php _e( 'Status', 'ordered-variable-product-report' ); ?>
							</span>
						</th>
						<th id="wope_order_title order_title" class="wope_order_column manage-column column-primary">
							<?php _e( 'Order', 'ordered-variable-product-report' ); ?>
						</th>
						<th id="wope_order_items order_items" class="wope_order_column manage-column column-primary">
							<?php _e( 'Purchased', 'ordered-variable-product-report' ); ?>
						</th>
						<th id="wope_shipping_address shipping_address" class="wope_order_column manage-column column-primary">
							<?php _e( 'Ship to', 'ordered-variable-product-report' ); ?>
						</th>
						<th class="wope_order_column manage-column column-primary">
							<?php _e( 'Order Total', 'ordered-variable-product-report' ); ?>
						</th>
						<th class="wope_order_column manage-column column-primary">
							<?php _e( 'Date', 'ordered-variable-product-report' ); ?>
						</th>
						<?php 
						do_action( 'wovpr_add_variable_order_column_head' );
						?>
						<th class="wope_order_column manage-column column-primary">
							<?php _e( 'Actions', 'ordered-variable-product-report' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>

		<?php  
		if(!session_id())
			session_start();
		if(!isset($_SESSION["ced_wcswr_hide_email"])):	
			$actual_link = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$urlvars = parse_url($actual_link);
		$url_params = $urlvars["query"];
		?>
		<div class="ced_wcswr_img_email_image">
			<div class="ced_wcswr_email_main_content">
				<div class="ced_wcswr_cross_image">
					<a class="button-primary ced_wcswr_cross_image_link" href="?<?php echo $url_params?>&ced_wcswr_close=true">x</a>
				</div>
				<div class="ced-recom">
					<h4>Cedcommerce recommendations for you </h4>
				</div>
				<div class="wramvp_main_content__col">
					<p> 
						Looking forward to evolve your eCommerce?
						<a href="http://bit.ly/2LB1lZV" target="_blank">Sell on the TOP Marketplaces</a>
					</p>
					<div class="wramvp_img_banner">
						<a target="_blank" href="http://bit.ly/2LB1lZV"><img alt="market-place" src="<?php echo plugins_url().'/ordered-variable-product-report/assets/images/market-place-2.jpg'?>"></a> 
					</div>
				</div>
				<br>
				<div class="wramvp_main_content__col">
					<p> 
						Leverage auto-syncing centralized order management and more with our
						<a href="http://bit.ly/2LB71TJ" target="_blank">Integration Extensions</a> 
					</p>
					<div class="wramvp_img_banner">
						<a target="_blank" href="http://bit.ly/2LB71TJ"><img alt="market-place" src="<?php echo plugins_url().'/ordered-variable-product-report/assets/images/market-place.jpg'?>"></a> 
					</div>
				</div>
				<div class="wramvp-support">
					<ul>
						<li>
							<span class="wramvp-support__left">Contact Us :-</span>
							<span class="wramvp-support__right"><a href="mailto:support@cedcommerce.com"> support@cedcommerce.com </a></span>
						</li>
						<li>
							<span class="wramvp-support__left">Get expert's advice :-</span>
							<span class="wramvp-support__right"><a href="https://join.skype.com/bovbEZQAR4DC"> Join Us</a></span>
						</li>
					</ul>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php 
}

		/**
		 * Process variable products' orders and list them.
		 *
		 * This function contains almost all the informations about the 
		 * found variable items. 
		 * If somethign is left, there will be a redirection for that to woocommerce.
		 * So that easily can be navigated.
		 */
		function wevop_show_variable_product_orders() {
			/**
			 * If current user is neither admin nor able to manage woocommerce, go back.
			 */
			if( ! current_user_can( 'manage_woocommerce' ) and ! current_user_can( 'manage_options' ) ) {
				return ;
			}
			
			global $woocommerce;
			$rawTotal = 0.0;
			$rawVariableProductsTotal = 0.0;

			/**
			 * Intializes wrapper of variable product orders.
			 */
			echo '<div class="wovpr-wrapper wrap">';

			echo '<h1 class="wp-heading-inline">'. $this->title .'</h1>';

				/**
				 * Invokes function to fetch only variable products' orders.
				 * @var array $variableOrders
				 */
				$variableOrders = $this->wope_get_variable_product_orders();
				if ( empty( $variableOrders ) ) {

					/**
					 * Prints empty orders table html here.
					 *
					 * Action performed in the function named as `wovpr_empty_variable_orders_list`
					 * Function lists empty table.
					 */
					do_action( 'wovpr_empty_variable_orders_list' );
					return;
				}

				/**
				 * Counts total number of orders orders.
				 * @var int $totalVariableOrders
				 */
				$totalVariableOrders = count( $variableOrders );

				/**
				 * Prints orders table starting html here.
				 *
				 * Action performed in the function named as `wovpr_orders_list_start_table`
				 * Function lists table header, till tbody.
				 */
				do_action( 'wovpr_orders_list_start_table' );
				
				/**
				 * Process orders listing and prints orders' rows.
				 * @var array $variableOrders
				 */
				foreach ( $variableOrders as $orderId => $orderInfo ) {
					if ( empty( $orderInfo ) ) {
						continue;
					}

					$order 		= new WC_Order( $orderId );
					$orderTotal = $order->get_formatted_order_total();
					$rawTotal 	+= $order->get_total();

					$var_items 	= $orderInfo[ 'variable_products' ];
					
					$line_items = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', 'line_item' ) );
					?>
					<tr class="wovpe_variable_product_order order-1">	
						<td class="order_status column-order_status">
							<mark class="<?php echo $order->get_status();?> tips" data-tip="<?php echo wc_get_order_status_name( $order->get_status() ); ?>">
								<?php echo wc_get_order_status_name( $order->get_status() ); ?>
							</mark>
						</td>
						<td class="">
							<div class="wovpe_order_info">
								<span>
									<?php 
									if ( $order->user_id ) {
										$user_info = get_userdata( $order->user_id );
									}

									if ( ! empty( $user_info ) ) {

										$username = '<a href="user-edit.php?user_id=' . absint( $user_info->ID ) . '">';

										if ( $user_info->first_name || $user_info->last_name ) {
											$username .= esc_html( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), ucfirst( $user_info->first_name ), ucfirst( $user_info->last_name ) ) );
										} else {
											$username .= esc_html( ucfirst( $user_info->display_name ) );
										}

										$username .= '</a>';

									} else {
										if ( $order->billing_first_name || $order->billing_last_name ) {
											$username = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $order->billing_first_name, $order->billing_last_name ) );
										} else if ( $order->billing_company ) {
											$username = trim( $order->billing_company );
										} else {
											$username = __( 'Guest', 'woocommerce' );
										}
									}

									printf( 
										_x( '%s by %s', 'Order number by X', 'woocommerce' ), 
										'<a href="' . admin_url( "post.php?post={$orderId}&action=edit" ) . '" class="row-title"><strong>#' . esc_attr( $order->get_order_number() ) . '</strong></a>', $username );

									if ( $order->billing_email ) {
										echo '<small class="meta email"><a href="' . esc_url( 'mailto:' . $order->billing_email ) . '">' . esc_html( $order->billing_email ) . '</a></small>';
									}

									echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', 'woocommerce' ) . '</span></button>';
									?>
								</span>
							</div>
							<div class="wovp_order_product_info wovpe_hide">
								<?php 

								/**
								 * Processing variable order's items.
								 * @var array $line_items
								 */
								foreach ( $line_items as $item_id => $item ) {
									if ( ! in_array( $item[ 'product_id' ], $var_items ) ) {
										continue;
									}

									$_product  = $order->get_product_from_item( $item );
									$item_meta = $order->get_item_meta( $item_id );
									if ( isset( $item[ 'line_total' ] ) ) {
										$rawVariableProductsTotal += $order->get_item_total( $item, false, true );
									}

									do_action( 'woocommerce_before_order_item_' . $item['type'] . '_html', $item_id, $item, $order );

									include( WOPE_PLUGIN_DIR . 'includes/wovpe-order-item.php' );

									do_action( 'woocommerce_order_item_' . $item['type'] . '_html', $item_id, $item, $order );
								}?>
							</div>
						</td>
						<td class="order_items column-order_items" data-colname="<?php _e( 'Purchased', 'ordered-variable-product-report' );?>">
							<?php 
							$this->wevop_order_items( $order );
							?>
						</td>
						<td class="shipping_address column-shipping_address" data-colname="<?php _e( 'Ship to', 'ordered-variable-product-report' );?>">
							<?php 
							if ( $address = $order->get_formatted_shipping_address() ) {
								echo '<a target="_blank" href="' . esc_url( $order->get_shipping_address_map_url() ) . '">'. esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) ) .'</a>';
							} else {
								echo '&ndash;';
							}

							if ( $order->get_shipping_method() ) {
								echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $order->get_shipping_method() ) . '</small>';
							}
							?>
						</td>
						<td>
							<?php 
							/**
							 * Listing order total here.
							 */
							echo $orderTotal; 

							if ( $order->payment_method_title ) {
								echo '<small class="meta">' . __( 'Via', 'woocommerce' ) . ' ' . esc_html( $order->payment_method_title ) . '</small>';
							}
							?>
						</td>
						<td>
							<?php 
							/**
							 * Listing order date.
							 */
							echo date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) );
							?>
						</td>
						<?php 
						do_action( 'wovpr_add_variable_order_column_body' );
						?>
						<td class="order_actions">
							<a class="button tips view wovp_show_thckbx" data-tip="<?php _e( 'View', 'ordered-variable-product-report' ); ?>">
								<?php _e( 'View', 'ordered-variable-product-report' ); ?>
							</a>
						</td>
					</tr>
					<?php 
				}

				/**
				 * Prints orders table ending html here.
				 *
				 * Action performed in the function named as `wovpr_orders_list_end_table`
				 * Function lists table fotter, from </tbody>.
				 */
				do_action( 'wovpr_orders_list_end_table', $order, $totalVariableOrders, $rawVariableProductsTotal, $rawTotal );

				echo '</div>';

			}		


		/**
		 * Lists orders' table footer area. Contains 4 required parameters.
		 * 
		 * @param  object 	$order                    Instance of class `WC_Order`.
		 * @param  int 		$totalVariableOrders      Total number of variable product orders.
		 * @param  float 	$rawVariableProductsTotal Raw total price of all variable items exiting in all the orders.
		 * @param  float 	$rawTotal                 Raw total price of all the orders
		 */
		function wovpr_orders_list_end_table( $order, $totalVariableOrders, $rawVariableProductsTotal, $rawTotal ) {?>
	</tbody>
	<tfoot>
		<tr>
			<th class="" colspan="2">
				<span>
					<strong><?php _e( 'Total Orders:', 'ordered-variable-product-report' ); ?></strong>
				</span>
				<span>
					<?php echo $totalVariableOrders; ?>
				</span>
			</th>
			<th class="" colspan="2">
				<span>
					<strong><?php _e( 'Variable Products Total:', 'ordered-variable-product-report' ); ?></strong>
				</span>
				<span>
					<?php echo wc_price( $rawVariableProductsTotal, array( 'currency' => $order->get_order_currency() ) ); ?>
				</span>
			</th>	
			<th class="" colspan="4">
				<span>
					<strong><?php _e( 'Orders Total:', 'ordered-variable-product-report' ); ?></strong>
				</span>
				<span>
					<?php echo wc_price( $rawTotal, array( 'currency' => $order->get_order_currency() ) ); ?>
				</span>
			</th>
		</tr>
	</tfoot>
</table>
<?php 
}

function wope_get_variable_product_orders() {
	$status = array_keys( wc_get_order_statuses() );
	if ( !empty( $this->order_filter_by_status ) ) {
		$status = $this->order_filter_by_status;
	}

	$args = array(
		'post_type' 	=> 'shop_order',
		'post_status' 	=> $status,
		'posts_per_page'=> '-1',
		'date_query'	=> array(
			array(
				'after'     => $this->order_filter_date_from,
				'before'    => $this->order_filter_date_to,
				'inclusive' => true,
				)
			)
		);


	$query 	= new WP_Query(  $args  );
	$orders = $query->get_posts( $args );

	$finalOrders 	= array();
	foreach ( $orders as $order ) {
		if ( empty( $order ) ) {
			continue;
		}

		$orderId 		= $order->ID;
		$orderInfo 		= new WC_Order( $orderId );
		$items 			= $orderInfo->get_items();

		foreach ( $items as $pID => $item ) {
			if ( empty( $item ) ) {
				continue;
			}

			$product = wc_get_product( $item[ 'product_id' ] );

					/**
					 * If product is not found, move to next pointer.
					 */
					if ( empty( $product ) ) {
						continue;
					}

					/**
					 * If product is not of variable type, move to next pointer without considering it.
					 */
					if( ! $product->is_type( 'variable' ) ) {
						continue;
					}

					if ( !empty( $this->product_order_filter ) ) {
						if ( ! in_array( $item[ 'product_id' ], $this->product_order_filter ) ) {
							continue;
						}		
					}

					if ( ! array_key_exists( $orderId, $finalOrders ) ) {
						$finalOrders[ $orderId ] = array(
							'order_total' 	=> $orderInfo->get_formatted_order_total(),
							'order_status' 	=> $orderInfo->get_status()
							);
					}

					$finalOrders[ $orderId ][ 'variable_products' ][] = $item[ 'product_id' ];
				}
			}
			return $finalOrders;
		}

		function wevop_order_items( $order ) {
			echo '<a href="javascript:void(0);" class="wovpe_show_order_items show_order_items">' . apply_filters( 'woocommerce_admin_order_item_count', sprintf( _n( '%d item', '%d items', $order->get_item_count(), 'woocommerce' ), $order->get_item_count() ), $order ) . '</a>';

			if ( sizeof( $order->get_items() ) > 0 ) {

				echo '<table class="wovpe_order_items_table order_items" cellspacing="0">';

				foreach ( $order->get_items() as $item ) {
					if ( empty( $item ) ) {
						continue;
					}

					$product        = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
					$item_meta      = new WC_Order_Item_Meta( $item, $product );
					$item_meta_html = $item_meta->display( true, true );
					?>
					<tr class="<?php echo apply_filters( 'woocommerce_admin_order_item_class', '', $item, $order ); ?>">
						<td class="qty"><?php echo absint( $item['qty'] ); ?></td>
						<td class="name">
							<?php  if ( $product ) : ?>
								<?php echo ( wc_product_sku_enabled() && $product->get_sku() ) ? $product->get_sku() . ' - ' : ''; ?><a href="<?php echo get_edit_post_link( $product->id ); ?>" title="<?php echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false ); ?>"><?php echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false ); ?></a>
							<?php else : ?>
								<?php echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false ); ?>
							<?php endif;

							if ( ! empty( $item_meta_html ) ) {
								echo '<span class="woocommerce-help-tip tips" data-tip="'. esc_attr( esc_html( $item_meta_html ) ) .'"></span>';  
							} else { 
								if ( empty( $product ) ) {
									continue;
								}

								/**
								 * If product is not of variable type, move to next pointer without considering it.
								 */
								if( $product->product_type == 'variable' ) {
									echo '<span class="woocommerce-help-tip tips" data-tip="'. __( 'This item is assigned as variable product, but no variations found.', 'ordered-variable-product-report' ) .'"></span>';
								}
							} ?>
						</td>
					</tr>
					<?php
				}
				echo '</table>';
			}
		}

		/**
		 * This function is to Showing Ordered Products.
		 * @name wevop_show_orderd_products
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		function wevop_show_orderd_products() {
			global $current_user;
			$user_roles = $current_user->roles;
			$user_role 	= array_shift( $user_roles );
			$rolearr 	= get_option( "ovpr_view_mode_selected", array() );
			if( ! in_array( $user_role, $rolearr ) and ! current_user_can( 'manage_options' ) ) {
				?>
				<p class="setting-error">
					<strong>
						<?php _e( 'Sorry! Setting can not be displayed to this user role.', 'ordered-variable-product-report' ); ?>
					</strong>
				</p>
				<?php 
				return;
			}
			$rolearr 		= get_option( "ovpr_view_mode_selected", array() );
			if(!session_id())
				session_start();
			if(!isset($_SESSION["ced_wcswr_hide_email"])):	
				$actual_link = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$urlvars = parse_url($actual_link);
			$url_params = $urlvars["query"];
			?>
			<div class="ced_wcswr_img_email_image">
				<div class="ced_wcswr_email_main_content">
					<div class="ced_wcswr_cross_image">
						<a class="button-primary ced_wcswr_cross_image_link" href="?<?php echo $url_params?>&ced_wcswr_close=true">x</a>
					</div>
					<div class="ced-recom">
						<h4>Cedcommerce recommendations for you </h4>
					</div>
					<div class="wramvp_main_content__col">
						<p> 
							Looking forward to evolve your eCommerce?
							<a href="http://bit.ly/2LB1lZV" target="_blank">Sell on the TOP Marketplaces</a>
						</p>
						<div class="wramvp_img_banner">
							<a target="_blank" href="http://bit.ly/2LB1lZV"><img alt="market-place" src="<?php echo plugins_url().'/ordered-variable-product-report/assets/images/market-place-2.jpg'?>"></a> 
						</div>
					</div>
					<br>
					<div class="wramvp_main_content__col">
						<p> 
							Leverage auto-syncing centralized order management and more with our
							<a href="http://bit.ly/2LB71TJ" target="_blank">Integration Extensions</a> 
						</p>
						<div class="wramvp_img_banner">
							<a target="_blank" href="http://bit.ly/2LB71TJ"><img alt="market-place" src="<?php echo plugins_url().'/ordered-variable-product-report/assets/images/market-place.jpg'?>"></a> 
						</div>
					</div>
					<div class="wramvp-support">
						<ul>
							<li>
								<span class="wramvp-support__left">Contact Us :-</span>
								<span class="wramvp-support__right"><a href="mailto:support@cedcommerce.com"> support@cedcommerce.com </a></span>
							</li>
							<li>
								<span class="wramvp-support__left">Get expert's advice :-</span>
								<span class="wramvp-support__right"><a href="https://join.skype.com/bovbEZQAR4DC"> Join Us</a></span>
							</li>
						</ul>
					</div>
				</div>
			</div>
		<?php endif;  ?>

		<?php
		if( current_user_can( "administrator" ) == true ) {?>
		<div class="ced-promotional-sidebar">
			<div class="wovpr_select">
				<p>
					<strong>
						<i><?php _e( 'Set user roles to which setting should be displayed', 'ordered-variable-product-report' );?></i>
					</strong>
				</p>
				<?php 
				$roles = get_editable_roles();
				foreach( $roles as $key => $value ) {
					if( $key != "administrator" && $key != "customer" && $key != "subscriber" ) {
						$chckd = "";
						if( in_array( $key, $rolearr ) ) {
							$chckd = "checked";
						}
						?>
						<p class="wovpr-filter-section-item">
							<input type="checkbox" <?php echo $chckd; ?>  value="<?php echo $key;?>" class="ovpr_user_selection">
							<span>
								<strong><?php _e( $value[ 'name' ], 'ordered-variable-product-report' );?></strong>
							</span>
						</p>
						<?php 
					}
				} 
				$role = get_option("ovpr_view_mode");
				$chck =""; 
				if( $role == "only_admin" ) { 
					$chck = ""; 
				} elseif( $role =="all" ) { 
					$chck = "checked"; 
				}?>
			</div>
			<?php }?>
			<div class="filter-variation" >
				<span>
					<strong>
						<?php _e( 'Filter Orders on Variable Products by Date:', 'ordered-variable-product-report' );?>
					</strong>
				</span>
				<hr>
				<form method="post">
					<label>
						<b> <?php _e( 'From :', 'ordered-variable-product-report' );?></b>
					</label>
					<input type="text" class="wovpr_datepicker" id="wovpr_datepicker_from" name="wovpr_datepicker_from" value="<?php echo $this->product_filter_date_from;?>" placeholder="<?php _e( 'Select start date', 'ordered-variable-product-report' );?>">
					
					<label>
						<b> <?php _e( 'To :', 'ordered-variable-product-report' );?></b>
					</label>
					<input type="text" class="wovpr_datepicker" id="wovpr_datepicker_to" name="wovpr_datepicker_to" value="<?php echo $this->product_filter_date_to;?>" placeholder="<?php _e( 'Select end date', 'ordered-variable-product-report' );?>">
					
					<input type="submit" value="Filter" class="button-primary" name="show_variation_product">
				</form>				
			</div>
			<?php 
			$variation_exist = false;
			echo '<div class="meta-box-sortables ui-sortable" id="wovp-accordion">';
			$order_products = $this->wevop_get_orders();
			if( empty( $order_products ) ) {
				echo '<div class="wovpe-order-item-section">';
				echo '<h3>';
				echo '<span>'.__( "No ordered variable products found!!", 'ordered-variable-product-report' ).'</span>';
				echo '</h3>';
				echo '</div>';
				return;
			}

			foreach( $order_products as $productKey  => $order_product ) {
				if( ! empty( $order_product[ 'variations' ] ) ) {
					$variation_exist = true;
					?>
					<div class="wovpe-order-item-section">
						<h3>
							<span><?php echo $order_product['product_name'];?></span>
						</h3>
						<div class="wovpe-order-item-section-inside inside">
							<?php 
							if( ! empty( $order_product[ 'attributes' ] ) ): ?>
							<table class="wovpe_variable_products_list" cellspacing="0" cellpadding="0" border="1" width="100%">
								<thead>
									<tr>
										<th>&nbsp;</th>
										<th><?php echo __("Attributes","ordered-variable-product-report");?></th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<th width="10%"><?php echo __("Variations","ordered-variable-product-report");?></th>
										<td width="90%">
											<table cellspacing="0" cellpadding="0" width="100%">
												<thead>
													<tr>
														<?php 
														$per_width = ( 75 / count( $order_product[ 'attributes' ] ) );
														foreach( $order_product['attributes'] as $key => $attribute ):?>
														<th width="<?php echo $per_width;?>%">
															<?php echo ucfirst(str_replace("pa_","",$key));?>
														</th>
													<?php endforeach;?>
													<th width="5%">
														<?php echo __( "Qty", "ordered-variable-product-report" );?>
													</th>
												</tr>
											</thead>
										</table>
									</td>
								</tr>
								<?php 
								$count 		= 1;
								$quantity 	= 0;
								foreach( $order_product['variations'] as $variation_id => $variation) { 
									if( $variation ['qty'] ) { ?>
									<tr>
										<td class="td-normal" width="10%"><?php echo $count; $count++?></td>
										<td class="td-normal" width="90%">
											<table cellspacing="0" cellpadding="0" width="100%">
												<thead>
													<tr>
														<?php 
														foreach( $variation['attributes'] as $key => $attribute ):?>

														<td class="td-normal" width="<?php echo $per_width;?>%">
															<?php echo strtoupper($attribute);?>
														</td>
													<?php endforeach;?>
													<td class="td-normal" width="5%">
														<?php echo $variation['qty'];?>
													</td>
												</tr>
											</thead>
										</table>
									</td>
								</tr>
								<?php 
								$quantity += $variation['qty'];
							}
						}?>
						<tr>
							<th class="td-normal th-head" colspan="3">
								<span>
									<?php _e('Total Quantity:','ordered-variable-product-report')?>
								</span>
								<span>
									<?php echo $quantity;?>
								</span>
							</th>
						</tr>
					</tbody>
				</table>
			<?php endif;?>		
		</div>
	</div>
</div>
<?php 
}
}

if( $variation_exist == false ) {
	echo '<div class="wovpe-order-item-section">';
	echo '<h3>';
	echo '<span>'.__( "It seems either no variable products are ordered or they don't have any variations.", 'ordered-variable-product-report' ).'</span>';
	echo '</h3>';
	echo '</div>';
	echo '</div>';
	return;
}

echo '</div>';
?>
<p>
	<form method="post" action="">
		<input id="wevop_export_var_order_product" class="button-primary" type="submit" name="wevop_export_var_order_product" value="Export Variable Products Orders">
	</form>
</p> 
<?php	
}
		/**
		 * This function is to fetch orders from database.
		 * @name wevop_get_orders
		 * @author CedCommerce <plugins@cedcommerce.com>
		 * @link http://cedcommerce.com/
		 */
		function wevop_get_orders() {
			global $woocommerce, $product;
			$args = array(
				'post_type' 	=> 'shop_order',
				'post_status' 	=> array_keys( wc_get_order_statuses() ),
				'posts_per_page'=> '-1',
				'date_query'	=> array(
					array(
						'after'     => $this->product_filter_date_from,
						'before'    => $this->product_filter_date_to,
						'inclusive' => true,
						)
					)
				);
			

			$my_query = new WP_Query( $args );
			$order_pro_arr = array();
			
			while( $my_query->have_posts() ) {
				$my_query->the_post();
				$order_id 	= $my_query->post->ID; 
				$order 		= new WC_Order( $order_id );
				$items 		= $order->get_items();
				foreach ( $items as $ordered_item_id => $item ) {
					if( $item['variation_id'] != 0 ) {
						$pro_variations = array();
						$pro_attributes = array();
						if ( array_key_exists( $item[ 'product_id' ], $order_pro_arr ) ) {
							if ( array_key_exists( $item[ 'variation_id' ], $order_pro_arr[ $item[ 'product_id' ] ][ 'variations' ] )) {
								$order_pro_arr[$item['product_id']]['variations'][$item['variation_id']]['qty'] = $order_pro_arr[$item['product_id']]['variations'][$item['variation_id']]['qty'] + $item['qty'];
								$order_pro_arr[$item['product_id']]['variations'][$item['variation_id']]['ordered_item_ids'] = $order_pro_arr[$item['product_id']]['variations'][$item['variation_id']]['ordered_item_ids'].','.$ordered_item_id;
							} else {
								$pro_all_variations = $order_pro_arr[$item['product_id']]['all_variations'];
								if( isset( $pro_all_variations ) )
								{
									foreach ( $pro_all_variations as $pro_all_variation )
									{
										if( $item['variation_id'] == $pro_all_variation['variation_id'] )
										{
											$order_pro_arr[$item['product_id']]['variations'][$pro_all_variation['variation_id']]['attributes'] = $pro_all_variation['attributes'];
											$order_pro_arr[$item['product_id']]['variations'][$pro_all_variation['variation_id']]['qty'] = $item['qty'];
											$order_pro_arr[$item['product_id']]['variations'][$pro_all_variation['variation_id']]['ordered_item_ids'] = $ordered_item_id;
											$order_pro_arr[$item['product_id']]['variations'][$pro_all_variation['variation_id']]['default_price'] = (float)($item['line_subtotal']/$item['qty']); //get_post_meta( $pro_all_variation['variation_id'], '_price', true );
											$order_pro_arr[$item['product_id']]['variations'][$pro_all_variation['variation_id']]['order_date'] = $order->order_date;
											break;
										}				
									}
								}
							}
						} else {
							$pro_variation_details = new WC_Product_Variable($item['product_id']);
							$pro_all_variations = $pro_variation_details->get_available_variations();
							$pro_attributes = $pro_variation_details->get_variation_attributes();
							$order_pro_arr_individual['product_id'] = $item['product_id'];
							$order_pro_arr_individual['product_name'] = $item['name'];
							$order_pro_arr_individual['attributes'] = $pro_attributes;
							$order_pro_arr_individual['all_variations'] = $pro_all_variations;
							$pro_variations = array();
							
							if( is_array( $pro_all_variations ) && !empty( $pro_all_variations ) ) {
								$variation_found = false;
								foreach ($pro_all_variations as $pro_all_variation)
								{
									if($item['variation_id'] == $pro_all_variation['variation_id'])
									{
										$pro_variations[$pro_all_variation['variation_id']]['attributes'] = $pro_all_variation['attributes'];
										$pro_variations[$pro_all_variation['variation_id']]['qty'] = $item['qty'];
										$pro_variations[$pro_all_variation['variation_id']]['ordered_item_ids'] = $ordered_item_id;
										$pro_variations[$pro_all_variation['variation_id']]['default_price'] = (float)($item['line_subtotal']/$item['qty']); //get_post_meta( $pro_all_variation['variation_id'], '_price', true );
										$pro_variations[$pro_all_variation['variation_id']]['order_date'] = $order->order_date;
										$variation_found = true;
										break;
									}
								}
								if(!$variation_found)
								{
									$pro_variations[$pro_all_variation['variation_id']]['qty'] = 0;
									$pro_variations[$pro_all_variation['variation_id']]['ordered_item_ids'] = '';
									$pro_variations[$pro_all_variation['variation_id']]['default_price'] = 0;
								}
							}
							$order_pro_arr_individual['variations'] = $pro_variations;
							$order_pro_arr[$item['product_id']] = $order_pro_arr_individual;
						}
					}
					else
					{
						if (array_key_exists($item['product_id'], $order_pro_arr)) 
						{
							$order_pro_arr[$item['product_id']]['qty']++;
						}
						else
						{
							$order_pro_arr_individual = array();
							$order_pro_arr_individual['product_id'] = $item['product_id'];
							$order_pro_arr_individual['product_name'] = $item['name'];
							$order_pro_arr_individual['attributes'] = array();
							$order_pro_arr_individual['variations'] = array();
							$order_pro_arr_individual['default_price'] = (float)($item['line_subtotal']/$item['qty']);
							$order_pro_arr_individual['qty'] = $item['qty'];
							$order_pro_arr_individual['order_date'] = $order->order_date;
							$order_pro_arr[$item['product_id']] = $order_pro_arr_individual;
						}
					}
				}
			}
			return $order_pro_arr;
		}	
	}
	new WEVOP();
}
?>