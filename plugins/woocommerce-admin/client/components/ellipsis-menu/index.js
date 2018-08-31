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

/**
 * This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.
 */
class EllipsisMenu extends Component {
	render() {
		const { children, label } = this.props;
		if ( ! children ) {
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
					label={ label }
					aria-expanded={ isOpen }
				/>
			);
		};

		const renderContent = () => (
			<NavigableMenu className="woocommerce-ellipsis-menu__content">{ children }</NavigableMenu>
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
};

export default EllipsisMenu;
