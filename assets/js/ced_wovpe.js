 jQuery( document ).ready( function( $ ) {
 	// $( "#op-accordion" ).accordion({
 	// 	collapsible: true
 	// });

 	$( document ).on( 'click', '.wovpe-order-item-section h3', function( event ) {
 		var $this = $( this );
 		$this.next( '.wovpe-order-item-section-inside' ).slideToggle( 400, 'linear' );
 		// $this.next( '.wovpe-order-item-section-inside' ).slideToggle( 400, 'linear' );
 	});

 	$( ".wovpe-order-item-section-inside" ).hide();
 	$( ".wovpe-order-item-section-inside" ).first().show();

 	/**
	 * Applying datepicker to date box's
	 */
	var dates = jQuery( '.wovpr_datepicker' ).datepicker({
		defaultDate: '',
		dateFormat: 'yy/mm/dd',
		numberOfMonths: 1,
		showButtonPanel: true,
		onSelect: function( selectedDate ) {
			var option   = jQuery( this ).is( '#wovpr_datepicker_from' ) ? 'minDate' : 'maxDate';
			var instance = jQuery( this ).data( 'datepicker' );
			var date     = jQuery.datepicker.parseDate( instance.settings.dateFormat || jQuery.datepicker._defaults.dateFormat, selectedDate, instance.settings );
			dates.not( this ).datepicker( 'option', option, date );
		}
	});

	/**
	 * Apply select 2
	 */
	$( '.wovpr-select2' ).select2();

 	$( '.tips' ).tipTip({
		'attribute': 'data-tip',
		'fadeIn': 50,
		'fadeOut': 50,
		'delay': 200
	});
 	
 	$( ".ovpr_user_selection" ).on( "change", function() {
 		var $this 	= $( this ),
 		value 		= $this.val();
 		var data 	= {
 			'action': 'set_ovpr_user_roles',
 			'roles'	: value
 		};

 		if( $this.prop( "checked" ) == true ) {
 			var data = {
 				'action': 'set_ovpr_user_roles',
 				'roles'	: value
 			};
 		} else if( $this.prop( "checked" ) == false ) {
 			var data = {
 				'action': 'set_ovpr_user_roles_uncheck',
 				'roles'	: value
 			};
 		}

 		$.post(
 			ajaxurl, 
 			data, 
 			function( response ) {
			}
		);
 	});

 	$( document ).on('click', '.wovp_show_thckbx', function( event ) {
 		var $this 	= $( this ),
 		caption 	= ( typeof globals.variable_items == "undefined" || globals.variable_items == 'undefined' ) ? '' : globals.variable_items;
 		thckbx_html = $this.parents( 'tr.wovpe_variable_product_order' ).find( '.wovp_order_product_info' ).clone().removeClass( 'wovpe_hide' );
 		$( document ).find( '#wovpe_ordered_variable_product_thickbox' ).html( thckbx_html );
 		tb_show( caption, globals.thckbxOrderProduct );
 		return false;
 	});

 	$( document ).on( 'click', '.wovpe_show_order_items', function( event ) {
 		$( this ).next( '.wovpe_order_items_table' ).toggle();
 	});
 });
