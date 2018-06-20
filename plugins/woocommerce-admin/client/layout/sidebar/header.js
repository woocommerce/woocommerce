/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { H } from 'layout/section';

class SidebarHeader extends Component {
	render() {
		const { label } = this.props;
		return (
			<div className="woocommerce-layout__sidebar-header">
				<H className="woocommerce-layout__sidebar-header-label">{ label }</H>
				<div className="woocommerce-layout__sidebar-header-divider" />
			</div>
		);
	}
}

SidebarHeader.propTypes = {
	label: PropTypes.string.isRequired,
};

export default SidebarHeader;
