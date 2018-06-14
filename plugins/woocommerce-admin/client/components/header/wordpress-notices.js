/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { IconButton } from '@wordpress/components';
import { sprintf, _n } from '@wordpress/i18n';

class WordPressNotices extends Component {
	constructor() {
		super();
		this.state = {
			count: 0,
			notices: null,
			noticesOpen: false,
			hasEventListeners: false,
		};

		this.onToggle = this.onToggle.bind( this );
		this.updateCount = this.updateCount.bind( this );
		this.showNotices = this.showNotices.bind( this );
		this.hideNotices = this.hideNotices.bind( this );
	}

	componentDidMount() {
		const notices = document.getElementById( 'wpadmin-notice-list' );
		let count = notices.children.length - 1; // Subtract 1 for the wp-header-end div, used to show notices
		const noticesOpen = notices.classList.contains( 'woocommerce__admin-notice-list-show' );
		const primary = document.getElementById( 'woocommerce-layout__primary' );

		// Move JITM notices out of the wp toggle
		const jitmPlaceholder = notices.getElementsByClassName( 'jetpack-jitm-message' );
		if ( jitmPlaceholder.length > 0 ) {
			count = count - 1; // Container div
			primary.insertAdjacentElement( 'afterbegin', jitmPlaceholder[ 0 ] );
		}

		// See https://reactjs.org/docs/react-component.html#componentdidmount
		this.setState( { count, notices, noticesOpen } ); // eslint-disable-line react/no-did-mount-set-state

		// Move WordPress notifications into the main WooDash body
		primary.insertAdjacentElement( 'afterbegin', notices );
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

	updateCount() {
		this.setState( { count: this.state.count - 1 } );
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
		this.state.notices.className = 'woocommerce__admin-notice-list-show';
	}

	hideNotices() {
		this.state.notices.className = 'woocommerce__admin-notice-list-hide';
	}

	onToggle() {
		const { noticesOpen } = this.state;

		if ( noticesOpen ) {
			this.hideNotices();
		} else {
			this.showNotices();
			this.maybeAddDismissEvents();
		}

		this.setState( { noticesOpen: ! noticesOpen } );
	}

	render() {
		const { count, noticesOpen } = this.state;

		if ( count < 1 ) {
			return null;
		}

		return (
			<IconButton
				onClick={ this.onToggle }
				icon="wordpress-alt"
				label={ sprintf(
					_n( 'View %d WordPress Notice', 'View %d WordPress Notices', count, 'woo-dash' ),
					count
				) }
				aria-expanded={ noticesOpen }
			/>
		);
	}
}

export default WordPressNotices;
