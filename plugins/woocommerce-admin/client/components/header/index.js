/** @format */
/**
 * External dependencies
 */
import { isArray } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import { getAdminLink } from '../../lib/nav-utils';

// TODO Implement timeline icon

const Header = ( { sections, showTimeline } ) => {
	const renderBreadcrumbs = () => {
		const _sections = isArray( sections ) ? sections : [ sections ];
		const crumbs = _sections.map( ( subSection, i ) => <span key={ i }>{ subSection }</span> );
		return (
			<h1>
				<span>
					<a href={ getAdminLink( 'admin.php?page=woodash' ) }>WooCommerce</a>
				</span>
				{ crumbs }
			</h1>
		);
	};

	return (
		<div className="woo-dash__header">
			{ renderBreadcrumbs() }
			{ showTimeline && <div /> }
		</div>
	);
};

Header.propTypes = {
	sections: PropTypes.node.isRequired,
	showTimeline: PropTypes.bool,
};

Header.defaultProps = {
	showTimeline: true,
};

export default Header;
