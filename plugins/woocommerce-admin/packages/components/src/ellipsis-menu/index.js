/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import classnames from 'classnames';
import { IconButton, Dropdown, NavigableMenu } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.
 */
class EllipsisMenu extends Component {
	render() {
		const { children, label, renderChildren } = this.props;
		if ( ! children && ! renderChildren ) {
			return null;
		}

		const renderToggle = ( { onToggle, isOpen } ) => {
			const toggleClassname = classnames( 'woocommerce-ellipsis-menu__toggle', {
				'is-opened': isOpen,
			} );

			return (
				<IconButton
					className={ toggleClassname }
					onClick={ onToggle }
					icon="ellipsis"
					title={ label }
					aria-expanded={ isOpen }
				/>
			);
		};

		// @todo Make all children rendered by render props so Dropdown args can be passed?
		const renderContent = renderContentArgs => (
			<NavigableMenu className="woocommerce-ellipsis-menu__content">
				{ children && children }
				{ renderChildren && renderChildren( renderContentArgs ) }
			</NavigableMenu>
		);

		return (
			<div className="woocommerce-ellipsis-menu">
				<Dropdown
					contentClassName="woocommerce-ellipsis-menu__popover"
					position="bottom left"
					renderToggle={ renderToggle }
					renderContent={ renderContent }
				/>
			</div>
		);
	}
}

EllipsisMenu.propTypes = {
	/**
	 * The label shown when hovering/focusing on the icon button.
	 */
	label: PropTypes.string.isRequired,
	/**
	 * A list of `MenuTitle`/`MenuItem` components
	 */
	children: PropTypes.node,
	/**
	 * A list of `MenuTitle`/`MenuItem` components as a render prop. Arguments from Dropdown passed as function arguments.
	 */
	renderChildren: PropTypes.func,
};

export default EllipsisMenu;
