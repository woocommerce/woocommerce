/*global jQuery, Backbone, _ */
( function ( $, Backbone, _ ) {
	'use strict';

	/**
	 * WooCommerce Backbone Modal plugin
	 *
	 * @param {object} options
	 */
	$.fn.WCBackboneModal = function( options ) {
		return this.each( function () {
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
				target: settings.template
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

			var $content  = $( '.wc-backbone-modal-content' ).find( 'article' );
			var content_h = $content.height();
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
					height: content_h
				});
			}

			$( '.wc-backbone-modal-content' ).css({
				'margin-top': '-' + ( $( '.wc-backbone-modal-content' ).height() / 2 ) + 'px'
			});

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
