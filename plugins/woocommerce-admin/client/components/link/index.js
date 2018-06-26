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
		const { children, to, wpAdmin, ...props } = this.props;
		if ( this.context.router && ! wpAdmin ) {
			return (
				<RouterLink to={ to } { ...props }>
					{ children }
				</RouterLink>
			);
		}

		const path = wpAdmin ? getAdminLink( to ) : getAdminLink( 'admin.php?page=woodash#' + to );
		return (
			<a href={ path } { ...props }>
				{ children }
			</a>
		);
	}
}

Link.propTypes = {
	to: PropTypes.string,
	wpAdmin: PropTypes.bool,
};

Link.defaultProps = {
	wpAdmin: false,
};

Link.contextTypes = {
	router: PropTypes.object,
};

export default Link;
