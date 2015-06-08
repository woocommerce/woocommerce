/*global jQuery, Backbone, _, woocommerce_admin_api_keys */
(function( $ ) {

	var APIView = Backbone.View.extend({
		el: $( '#key-fields' ),
		events: {
			'click input#update_api_key': 'saveKey'
		},
		initialize: function(){
			_.bindAll( this, 'saveKey' );
		},
		block: function() {
			$( this.el ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
		},
		unblock: function() {
			$( this.el ).unblock();
		},
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
		createQRCode: function( consumer_key, consumer_secret ) {
			$( '#keys-qrcode' ).qrcode({
				text: consumer_key + '|' + consumer_secret,
				width: 120,
				height: 120
			});
		},
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

						$( '#key_id', self.el ).val( data.key_id );
						$( '#key_description', self.el ).val( data.description );
						$( '#key_user', self.el ).val( data.user_id );
						$( '#key_permissions', self.el ).val( data.permissions );

						if ( 0 < data.consumer_key.length && 0 < data.consumer_secret.length ) {
							$( '#update_api_key', self.el ).val( woocommerce_admin_api_keys.i18n_save_changes ).after( data.revoke_url );

							var keysTemplate = _.template( $( '#api-keys-template' ).html(), {
								consumer_key:    data.consumer_key,
								consumer_secret: data.consumer_secret
							});

							$( 'p.submit', self.el ).before( keysTemplate );
							self.createQRCode( data.consumer_key, data.consumer_secret );
							self.initTipTip();
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
