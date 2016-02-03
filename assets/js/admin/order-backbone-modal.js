/*global jQuery, Backbone, _ */
( function( $, Backbone, _ ) {
	'use strict';

	/**
	 * WooCommerce Backbone Modal plugin
	 *
	 * @param {object} options
	 */
	$.fn.WCBackboneModal = function( options ) {
		return this.each( function() {
			( new $.WCBackboneModal( $( this ), options ) );
		});
	};

	/**
	 * Initialize the Backbone Modal
	 *
	 * @param {object} element [description]
	 * @param {object} options [description]
	 */
	$.WCBackboneModal = function( element, options ) {
		// Set settings
		var settings = $.extend( {}, $.WCBackboneModal.defaultOptions, options );

		if ( settings.template ) {
			new $.WCBackboneModal.View({
				target: settings.template,
				string: settings.variable
			});
		}
	};

	/**
	 * Set default options
	 *
	 * @type {object}
	 */
	$.WCBackboneModal.defaultOptions = {
		template: '',
		variable: {}
	};

	/**
	 * Create the Backbone Modal
	 *
	 * @return {null}
	 */
	$.WCBackboneModal.View = Backbone.View.extend({
		tagName: 'div',
		id: 'wc-backbone-modal-dialog',
		_target: undefined,
		_string: undefined,
		events: {
			'click .modal-close': 'closeButton',
			'click #btn-ok':      'addButton',
			'touchstart #btn-ok': 'addButton',
			'keydown':            'keyboardActions'
		},
		initialize: function( data ) {
			this._target = data.target;
			this._string = data.string;
			_.bindAll( this, 'render' );
			this.render();
		},
		render: function() {
			var template = wp.template( this._target );

			this.$el.attr( 'tabindex' , '0' ).append(
				template( this._string )
			);

			$( document.body ).css({
				'overflow': 'hidden'
			}).append( this.$el );

			var $content  = $( '.wc-backbone-modal-content' ).find( 'article' );
			var content_h = ( $content.height() < 90 ) ? 90 : $content.height();
			var max_h     = $( window ).height() - 200;

			if ( max_h > 400 ) {
				max_h = 400;
			}

			if ( content_h > max_h ) {
				$content.css({
					'overflow': 'auto',
					height: max_h + 'px'
				});
			} else {
				$content.css({
					'overflow': 'visible',
					height: ( content_h > 90 ) ? 'auto' : content_h + 'px'
				});
			}

			$( '.wc-backbone-modal-content' ).css({
				'margin-top': '-' + ( $( '.wc-backbone-modal-content' ).height() / 2 ) + 'px',
				'margin-left': '-' + ( $( '.wc-backbone-modal-content' ).width() / 2 ) + 'px'
			});

			$( document.body ).trigger( 'wc_backbone_modal_loaded', this._target );
		},
		closeButton: function( e ) {
			e.preventDefault();
			$( document.body ).trigger( 'wc_backbone_modal_before_remove', this._target );
			this.undelegateEvents();
			$( document ).off( 'focusin' );
			$( document.body ).css({
				'overflow': 'auto'
			});
			this.remove();
			$( document.body ).trigger( 'wc_backbone_modal_removed', this._target );
		},
		addButton: function( e ) {
			$( document.body ).trigger( 'wc_backbone_modal_response', [ this._target, this.getFormData() ] );
			this.closeButton( e );
		},
		getFormData: function() {
			var data = {};

			$( document.body ).trigger( 'wc_backbone_modal_before_update', this._target );

			$.each( $( 'form', this.$el ).serializeArray(), function( index, item ) {
				if ( data.hasOwnProperty( item.name ) ) {
					data[ item.name ] = $.makeArray( data[ item.name ] );
					data[ item.name ].push( item.value );
				} else {
					data[ item.name ] = item.value;
				}
			});

			return data;
		},
		keyboardActions: function( e ) {
			var button = e.keyCode || e.which;

			// Enter key
			if ( 13 === button && ! ( e.target.tagName && ( e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'textarea' ) ) ) {
				this.addButton( e );
			}

			// ESC key
			if ( 27 === button ) {
				this.closeButton( e );
			}
		}
	});

}( jQuery, Backbone, _ ));
