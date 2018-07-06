/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { IconButton } from '@wordpress/components';
import { Component } from '@wordpress/element';
import Gridicon from 'gridicons';
import { partial } from 'lodash';
import classnames from 'classnames';

class WordPressNotices extends Component {
	constructor() {
		super();
		this.state = {
			count: 0,
			notices: null,
			noticesOpen: false,
			hasEventListeners: false,
		};

		this.updateCount = this.updateCount.bind( this );
		this.showNotices = this.showNotices.bind( this );
		this.hideNotices = this.hideNotices.bind( this );
		this.initialize = this.initialize.bind( this );
	}

	componentDidMount() {
		this.handleWooCommerceEmbedPage();
		if ( 'complete' === document.readyState ) {
			this.initialize();
		} else {
			window.addEventListener( 'DOMContentLoaded', this.initialize );
		}
	}

	componentDidUpdate( prevProps ) {
		if ( ! prevProps.showNotices && this.props.showNotices ) {
			this.showNotices();
			this.maybeAddDismissEvents();
		}

		if ( prevProps.showNotices && ! this.props.showNotices ) {
			this.hideNotices();
		}
	}

	handleWooCommerceEmbedPage() {
		if ( ! document.body.classList.contains( 'woocommerce-embed-page' ) ) {
			return;
		}

		// Existing WooCommerce pages already have a designted area for notices, using wp-header-end
		// See https://github.com/WordPress/WordPress/blob/f6a37e7d39e2534d05b9e542045174498edfe536/wp-admin/js/common.js#L737
		// We want to move most notices, but keep displaying others (like success notice) where they already are
		// this renames the element in-line, so we can target it later.
		const headerEnds = document.getElementsByClassName( 'wp-header-end' );
		for ( let i = 0; i < headerEnds.length; i++ ) {
			const headerEnd = headerEnds.item( i );
			if ( 'woocommerce-layout__notice-catcher' !== headerEnd.id ) {
				headerEnd.className = '';
				headerEnd.id = 'wp__notice-list-uncollapsed';
			}
		}
	}

	initialize() {
		const notices = document.getElementById( 'wp__notice-list' );
		const noticesOpen = notices.classList.contains( 'woocommerce-layout__notice-list-show' );

		// On existing classic WooCommerce pages, screen links like "help" and "screen options" display, and need to be displayed
		// along side the notifications expansion.
		const screenLinks = document.getElementById( 'screen-meta-links' );
		if ( screenLinks ) {
			notices.classList.add( 'has-screen-meta-links' );
			document
				.getElementById( 'woocommerce-layout__notice-list' )
				.insertAdjacentElement( 'beforebegin', document.getElementById( 'screen-meta' ) );
			document
				.getElementById( 'woocommerce-layout__notice-list' )
				.insertAdjacentElement( 'beforebegin', screenLinks );
		}

		const collapsedTargetArea = document.getElementById( 'woocommerce-layout__notice-list' );
		const uncollapsedTargetArea =
			document.getElementById( 'wp__notice-list-uncollapsed' ) ||
			document.getElementById( 'ajax-response' ) ||
			document.getElementById( 'woocommerce-layout__notice-list' );

		let count = 0;
		for ( let i = 0; i <= notices.children.length; i++ ) {
			const notice = notices.children[ i ];
			if ( ! notice ) {
				continue;
			} else if ( ! this.shouldCollapseNotice( notice ) ) {
				uncollapsedTargetArea.insertAdjacentElement( 'afterend', notice );
			} else {
				count++;
			}
		}

		count = count - 1; // Remove 1 for `wp-header-end` which is a child of wp__notice-list

		this.setState( { count, notices, noticesOpen } );

		// Move collapsed WordPress notifications into the main WooDash body
		collapsedTargetArea.insertAdjacentElement( 'beforeend', notices );
	}

	componentWillUnmount() {
		document
			.getElementById( 'wpbody-content' )
			.insertAdjacentElement( 'afterbegin', this.state.notices );

		const dismissNotices = document.getElementsByClassName( 'notice-dismiss' );
		Object.keys( dismissNotices ).forEach( function( key ) {
			dismissNotices[ key ].removeEventListener( 'click', this.updateCount );
		}, this );

		this.setState( { noticesOpen: false, hasEventListeners: false } );
		this.hideNotices();
	}

	// Some messages should not be displayed in the toggle, like Jetpack JITM messages or update/success messages
	shouldCollapseNotice( element ) {
		if ( element.classList.contains( 'jetpack-jitm-message' ) ) {
			return false;
		}

		if ( 'woocommerce_errors' === element.id ) {
			return false;
		}

		if ( element.classList.contains( 'hidden' ) ) {
			return false;
		}

		if ( 'message' === element.id && element.classList.contains( 'notice' ) ) {
			return false;
		}

		if ( 'message' === element.id && 'updated' === element.className ) {
			return false;
		}
		return true;
	}

	updateCount() {
		const updatedCount = this.state.count - 1;
		this.setState( { count: updatedCount } );

		if ( updatedCount < 1 ) {
			this.props.togglePanel( 'wpnotices' ); // Close the panel since all of the notices have been closed.
		}
	}

	maybeAddDismissEvents() {
		if ( this.state.hasEventListeners ) {
			return;
		}

		const dismiss = document.getElementsByClassName( 'notice-dismiss' );
		Object.keys( dismiss ).forEach( function( key ) {
			dismiss[ key ].addEventListener( 'click', this.updateCount );
		}, this );

		this.setState( { hasEventListeners: true } );
	}

	showNotices() {
		this.state.notices.classList.add( 'woocommerce-layout__notice-list-show' );
		this.state.notices.classList.remove( 'woocommerce-layout__notice-list-hide' );
	}

	hideNotices() {
		this.state.notices.classList.add( 'woocommerce-layout__notice-list-hide' );
		this.state.notices.classList.remove( 'woocommerce-layout__notice-list-show' );
	}

	render() {
		const { count } = this.state;
		const { showNotices, togglePanel } = this.props;

		if ( count < 1 ) {
			return null;
		}

		const className = classnames( 'woocommerce-layout__activity-panel-tab', {
			'woocommerce-layout__activity-panel-tab-wordpress-notices': true,
			'is-active': showNotices,
		} );

		return (
			<IconButton
				key="wpnotices"
				className={ className }
				onClick={ partial( togglePanel, 'wpnotices' ) }
				icon={ <Gridicon icon="my-sites" /> }
			>
				{ __( 'Notices', 'woo-dash' ) }
			</IconButton>
		);
	}
}

export default WordPressNotices;
