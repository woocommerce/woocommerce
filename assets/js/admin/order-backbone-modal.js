/*global jQuery, Backbone, _ */
( function ( $, Backbone, _ ) {
	'use strict';

	window.WCBackbone = {
		Modal: {
			__instance: undefined
		}
	};

	window.WCBackbone.Modal.View = Backbone.View.extend({
		tagName: 'div',
		id: 'wc-backbone-modal-dialog',
		_target: undefined,
		events: {
			'click #btn-cancel': 'closeButton',
			'click #btn-ok':     'addButton',
		},
		initialize: function ( data ) {
			this._target = data.target;
			_.bindAll( this, 'render' );
			this.render();
		},
		render: function () {
			this.$el.attr( 'tabindex' , '0' ).append( $( this._target ).html() );

			$( 'body' ).css({
				'overflow': 'hidden'
			}).append( this.$el );
			$( 'body' ).trigger( 'wc_backbone_modal_loaded', this._target );
		},
		closeButton: function ( e ) {
			e.preventDefault();
			this.undelegateEvents();
			$( document ).off( 'focusin' );
			$( 'body' ).css({
				'overflow': 'auto'
			});
			this.remove();
			$( 'body' ).trigger( 'wc_backbone_modal_removed', this._target );
			window.WCBackbone.Modal.__instance = undefined;
		},
		addButton: function ( e ) {
			$( 'body' ).trigger( 'wc_backbone_modal_response', this._target, this.getFormData() );
			this.closeButton( e );
		},
		getFormData: function () {
			var data = {};

			$.each( $( 'form', this.$el ).serializeArray(), function( index, item ) {
				if ( data.hasOwnProperty( item.name ) ) {
					data[ item.name ] = $.makeArray( data[ item.name ] );
					data[ item.name ].push( item.value );
				}
				else {
					data[ item.name ] = item.value;
				}
			});

			return data;
		}
	});
}( jQuery, Backbone, _ ));
