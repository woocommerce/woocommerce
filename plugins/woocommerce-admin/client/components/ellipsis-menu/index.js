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
	label: PropTypes.string.isRequired,
};

export { EllipsisMenu };
export { default as MenuItem } from './menu-item';
export { default as MenuTitle } from './menu-title';
