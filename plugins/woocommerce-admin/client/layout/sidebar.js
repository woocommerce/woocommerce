/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { IconButton } from '@wordpress/components';
import { uniqueId } from 'lodash';

/**
 * Internal dependencies
 */
import Activity from 'dashboard/activity';

class Sidebar extends Component {
	render() {
		const { isOpen, onToggle } = this.props;
		const className = classnames( 'woo-dash__secondary', {
			'is-opened': isOpen,
		} );
		const headerId = uniqueId( 'sidebar-header_' );

		return (
			<aside className={ className } aria-labelledby={ headerId }>
				<header className="woo-dash__sidebar-header">
					<h2 className="woo-dash__sidebar-title" id={ headerId }>
						{ __( 'Store Activity', 'woo-dash' ) }
					</h2>
					<div className="woo-dash__sidebar-toggle">
						<IconButton
							className="woo-dash__sidebar-button"
							onClick={ onToggle }
							icon="no-alt"
							label={ __( 'Close Sidebar', 'woo-dash' ) }
						/>
					</div>
				</header>

				<Activity />
			</aside>
		);
	}
}

export default Sidebar;
