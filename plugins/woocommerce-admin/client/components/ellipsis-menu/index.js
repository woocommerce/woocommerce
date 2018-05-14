/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import classnames from 'classnames';
import { IconButton, Dropdown, NavigableMenu } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

class EllipsisMenu extends Component {
	render() {
		const { children, label } = this.props;
		if ( ! children ) {
			return null;
		}

		const renderToggle = ( { onToggle, isOpen } ) => {
			const toggleClassname = classnames( 'woo-dash__ellipsis-menu-toggle', {
				'is-opened': isOpen,
			} );

			return (
				<IconButton
					className={ toggleClassname }
					onClick={ onToggle }
					icon="ellipsis"
					label={ label }
					aria-expanded={ isOpen }
				/>
			);
		};

		const renderContent = () => (
			<NavigableMenu className="woo-dash__ellipsis-menu-content">{ children }</NavigableMenu>
		);

		return (
			<div className="woo-dash__ellipsis-menu">
				<Dropdown
					contentClassName="woo-dash__ellipsis-menu-popover"
					position="bottom left"
					renderToggle={ renderToggle }
					renderContent={ renderContent }
				/>
			</div>
		);
	}
}

EllipsisMenu.propTypes = {
	label: PropTypes.string.isRequired,
};

export { EllipsisMenu };
export { default as MenuItem } from './menu-item';
export { default as MenuTitle } from './menu-title';
