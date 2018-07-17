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
	href: PropTypes.string.isRequired,
	type: PropTypes.oneOf( [ 'wp-admin', 'wc-admin', 'external' ] ).isRequired,
};

Link.defaultProps = {
	type: 'wc-admin',
};

Link.contextTypes = {
	router: PropTypes.object,
};

export default Link;
