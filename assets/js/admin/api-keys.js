/*global jQuery, Backbone, _, woocommerce_admin_api_keys */
(function( $ ) {

	var APIView = Backbone.View.extend({
		/**
		 * Element
		 *
		 * @param {Object} '#key-fields'
		 */
		el: $( '#key-fields' ),

		/**
		 * Events
		 *
		 * @type {Object}
		 */
		events: {
			'click input#update_api_key': 'saveKey'
		},

		/**
		 * Initialize actions
		 */
		initialize: function(){
			_.bindAll( this, 'saveKey' );
		},

		/**
		 * Init jQuery.BlockUI
		 */
		block: function() {
			$( this.el ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
		},

		/**
		 * Remove jQuery.BlockUI
		 */
		unblock: function() {
			$( this.el ).unblock();
		},

		/**
		 * Init TipTip
		 */
		initTipTip: function() {
			$( '.copy-key', this.el ).tipTip({
				'attribute':  'data-tip',
				'activation': 'click',
				'fadeIn':     50,
				'fadeOut':    50,
				'delay':      0
			});

			$( document.body ).on( 'copy', '.copy-key', function( e ) {
				e.clipboardData.clearData();
				e.clipboardData.setData( 'text/plain', $.trim( $( this ).prev( 'code' ).html() ) );
				e.preventDefault();
			});
		},

		/**
		 * Create qrcode
		 *
		 * @param {string} consumer_key
		 * @param {string} consumer_secret
		 */
		createQRCode: function( consumer_key, consumer_secret ) {
			$( '#keys-qrcode' ).qrcode({
				text: consumer_key + '|' + consumer_secret,
				width: 120,
				height: 120
			});
		},

		/**
		 * Save API Key using ajax
		 *
		 * @param {Object} e
		 */
		saveKey: function( e ) {
			e.preventDefault();

			var self = this;

			self.block();

			Backbone.ajax({
				method:   'POST',
				dataType: 'json',
				url:      woocommerce_admin_api_keys.ajax_url,
				data:     {
					action:      'woocommerce_update_api_key',
					security:    woocommerce_admin_api_keys.update_api_nonce,
					key_id:      $( '#key_id', self.el ).val(),
					description: $( '#key_description', self.el ).val(),
					user:        $( '#key_user', self.el ).val(),
					permissions: $( '#key_permissions', self.el ).val()
				},
				success: function( response ) {
					$( '.wc-api-message', self.el ).remove();

					if ( response.success ) {
						var data = response.data;

						$( 'h3', self.el ).first().append( '<div class="wc-api-message updated"><p>' + data.message + '</p></div>' );

						if ( 0 < data.consumer_key.length && 0 < data.consumer_secret.length ) {
							$( '#api-keys-options', self.el ).remove();
							$( 'p.submit', self.el ).empty().append( data.revoke_url );

							var template = wp.template( 'api-keys-template' );

							$( 'p.submit', self.el ).before( template({
								consumer_key:    data.consumer_key,
								consumer_secret: data.consumer_secret
							}) );
							self.createQRCode( data.consumer_key, data.consumer_secret );
							self.initTipTip();
						} else {
							$( '#key_description', self.el ).val( data.description );
							$( '#key_user', self.el ).val( data.user_id );
							$( '#key_permissions', self.el ).val( data.permissions );
						}
					} else {
						$( 'h3', self.el ).first().append( '<div class="wc-api-message error"><p>' + response.data.message + '</p></div>' );
					}

					self.unblock();
				}
			});
		}
	});

	new APIView();

})( jQuery );
