/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

class SidebarHeader extends Component {
	render() {
		const { label } = this.props;
		return (
			<div className="woo-dash__sidebar-header">
				<h3 className="woo-dash__sidebar-header-label">{ label }</h3>
				<div className="woo-dash__sidebar-header-divider" />
			</div>
		);
	}
}

SidebarHeader.propTypes = {
	label: PropTypes.string.isRequired,
};

export default SidebarHeader;
