/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import Gridicon from 'gridicons';
import { IconButton } from '@wordpress/components';
import { intersection, noop, partial } from 'lodash';
import PropTypes from 'prop-types';
import { applyFilters } from '@wordpress/hooks';

class WordPressNotices extends Component {
	constructor() {
		super();
		this.state = {
			count: 0,
			notices: null,
			screenLinks: null,
			screenMeta: null,
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
		const screenMeta = document.getElementById( 'screen-meta' );
		const screenLinks = document.getElementById( 'screen-meta-links' );

		const collapsedTargetArea = document.getElementById( 'woocommerce-layout__notice-list' );
		const uncollapsedTargetArea =
			document.getElementById( 'wp__notice-list-uncollapsed' ) ||
			document.getElementById( 'ajax-response' ) ||
			document.getElementById( 'woocommerce-layout__notice-list' );

		let count = 0;

		for ( const notice of Array.from( notices.children ) ) {
			if ( 0 === notice.innerHTML.length ) {
				// Ignore empty elements in this part of the DOM.
				continue;
			} else if ( ! this.shouldCollapseNotice( notice ) ) {
				uncollapsedTargetArea.insertAdjacentElement( 'afterend', notice );
			} else {
				count++;
			}
		}

		this.props.onCountUpdate( count );
		this.setState( { count, notices, noticesOpen, screenMeta, screenLinks } );

		// Move collapsed WordPress notifications into the main wc-admin body
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
		const noticesToShow = applyFilters(
			'woocommerce_admin_notices_to_show',
			// element id, [ classes to include ], [ classes to exclude ]
			[
				[ null, [ 'jetpack-jitm-message' ] ],
				[ 'woocommerce_errors', null ],
				[ null, [ 'hidden' ] ],
				[ 'message', [ 'notice', 'updated' ], [ 'woocommerce-message' ] ],
			]
		);

		for ( let i = 0; i < noticesToShow.length; i++ ) {
			const [ id, includeClasses, excludeClasses ] = noticesToShow[ i ];

			const idMatch = null === id || id === element.id;
			let classMatch = true;

			if ( Array.isArray( includeClasses ) ) {
				classMatch = 0 < intersection( element.classList, includeClasses ).length;
			}

			if ( Array.isArray( excludeClasses ) ) {
				classMatch = classMatch && 0 === intersection( element.classList, excludeClasses ).length;
			}

			if ( idMatch && classMatch ) {
				return applyFilters( 'woocommerce_admin_should_hide_notice', false, element );
			}
		}

		return applyFilters( 'woocommerce_admin_should_hide_notice', true, element );
	}

	updateCount() {
		const updatedCount = this.state.count - 1;
		this.setState( { count: updatedCount } );
		this.props.onCountUpdate( updatedCount );

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
		const { notices, screenLinks, screenMeta } = this.state;
		notices.classList.add( 'woocommerce-layout__notice-list-show' );
		notices.classList.remove( 'woocommerce-layout__notice-list-hide' );
		screenMeta && screenMeta.classList.add( 'is-hidden-by-notices' );
		screenLinks && screenLinks.classList.add( 'is-hidden-by-notices' );

		window.scrollBy( 0, window.scrollY * -1 );
		this.setState( { noticesOpen: true } );
	}

	hideNotices() {
		const { notices, screenLinks, screenMeta } = this.state;
		notices.classList.add( 'woocommerce-layout__notice-list-hide' );
		notices.classList.remove( 'woocommerce-layout__notice-list-show' );
		screenMeta && screenMeta.classList.remove( 'is-hidden-by-notices' );
		screenLinks && screenLinks.classList.remove( 'is-hidden-by-notices' );

		this.setState( { noticesOpen: false } );
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
				role="tab"
				tabIndex={ showNotices ? null : -1 }
			>
				{ __( 'Notices', 'woocommerce-admin' ) }{' '}
				<span className="screen-reader-text">{ __( 'unread activity', 'woocommerce-admin' ) }</span>
			</IconButton>
		);
	}
}

WordPressNotices.propTypes = {
	showNotices: PropTypes.bool,
	togglePanel: PropTypes.func,
	onCountUpdate: PropTypes.func,
};

WordPressNotices.defaultProps = {
	togglePanel: noop,
	onCountUpdate: noop,
};

export default WordPressNotices;
