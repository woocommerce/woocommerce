/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Link as RouterLink } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { getAdminLink } from 'lib/nav-utils';

/**
 * Use `Link` to create a link to another resource. It accepts a type to automatically
 * create wp-admin links, wc-admin links, and external links.
 */
class Link extends Component {
	render() {
		const { children, href, type, ...props } = this.props;
		if ( this.context.router && 'wc-admin' === type ) {
			return (
				<RouterLink to={ href } { ...props }>
					{ children }
				</RouterLink>
			);
		}

		let path;
		if ( 'wp-admin' === type ) {
			path = getAdminLink( href );
		} else if ( 'external' === type ) {
			path = href;
		} else {
			path = getAdminLink( 'admin.php?page=wc-admin#' + href );
		}

		return (
			<a href={ path } { ...props }>
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
