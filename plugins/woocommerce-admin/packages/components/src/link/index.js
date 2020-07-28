/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import { partial } from 'lodash';
import { getHistory } from '@woocommerce/navigation';

/**
 * Use `Link` to create a link to another resource. It accepts a type to automatically
 * create wp-admin links, wc-admin links, and external links.
 */
class Link extends Component {
	// @todo Investigate further if we can use <Link /> directly.
	// With React Router 5+, <RouterLink /> cannot be used outside of the main <Router /> elements,
	// which seems to include components imported from @woocommerce/components. For now, we can use the history object directly.
	wcAdminLinkHandler( onClick, event ) {
		event.preventDefault();

		// If there is an onclick event, execute it.
		const onClickResult = onClick ? onClick( event ) : true;

		// Mimic browser behavior and only continue if onClickResult is not explicitly false.
		if ( onClickResult === false ) {
			return;
		}

		getHistory().push( event.target.closest( 'a' ).getAttribute( 'href' ) );
	}

	render() {
		const { children, href, type, ...props } = this.props;

		const passProps = {
			...props,
			'data-link-type': type,
		};

		if ( type === 'wc-admin' ) {
			passProps.onClick = partial(
				this.wcAdminLinkHandler,
				passProps.onClick
			);
		}

		return (
			<a href={ href } { ...passProps }>
				{ children }
			</a>
		);
	}
}

Link.propTypes = {
	/**
	 * The resource to link to.
	 */
	href: PropTypes.string.isRequired,
	/**
	 * Type of link. For wp-admin and wc-admin, the correct prefix is appended.
	 */
	type: PropTypes.oneOf( [ 'wp-admin', 'wc-admin', 'external' ] ).isRequired,
};

Link.defaultProps = {
	type: 'wc-admin',
};

Link.contextTypes = {
	router: PropTypes.object,
};

export default Link;
