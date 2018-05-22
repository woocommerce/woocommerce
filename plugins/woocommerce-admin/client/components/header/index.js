/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fill } from 'react-slot-fill';
import { isArray, noop } from 'lodash';
import { IconButton } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import { getAdminLink } from 'lib/nav-utils';

const Header = ( { sections, onToggle, isSidebarOpen } ) => {
	const _sections = isArray( sections ) ? sections : [ sections ];

	return (
		<div className="woo-dash__header">
			<h1>
				<span>
					<a href={ getAdminLink( '/' ) }>WooCommerce</a>
				</span>
				{ _sections.map( ( subSection, i ) => <span key={ i }>{ subSection }</span> ) }
			</h1>
			<div className="woo-dash__header-toggle">
				<IconButton
					className="woo-dash__header-button"
					onClick={ onToggle }
					icon="clock"
					label={ __( 'Show Sidebar', 'woo-dash' ) }
					aria-expanded={ isSidebarOpen }
				/>
			</div>
		</div>
	);
};

Header.propTypes = {
	sections: PropTypes.node.isRequired,
	onToggle: PropTypes.func.isRequired,
	isSidebarOpen: PropTypes.bool,
};

Header.defaultProps = {
	onToggle: noop,
};

export default function( props ) {
	return (
		<Fill name="header">
			<Header { ...props } />
		</Fill>
	);
}
