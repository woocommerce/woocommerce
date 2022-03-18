/**
 * External dependencies
 */
import { createElement, Component } from '@wordpress/element';
import classnames from 'classnames';
import { Button, Dropdown, NavigableMenu } from '@wordpress/components';
import { Icon } from '@wordpress/icons';
import Ellipsis from 'gridicons/dist/ellipsis';
import PropTypes from 'prop-types';

/**
 * This is a dropdown menu hidden behind a vertical ellipsis icon. When clicked, the inner MenuItems are displayed.
 */
class EllipsisMenu extends Component {
	render() {
		const { label, renderContent, className } = this.props;
		if ( ! renderContent ) {
			return null;
		}

		const renderEllipsis = ( { onToggle, isOpen } ) => {
			const toggleClassname = classnames(
				'woocommerce-ellipsis-menu__toggle',
				{
					'is-opened': isOpen,
				}
			);

			return (
				<Button
					className={ toggleClassname }
					onClick={ ( e ) => {
						if ( this.props.onToggle ) {
							this.props.onToggle( e );
						}
						onToggle( e );
					} }
					title={ label }
					aria-expanded={ isOpen }
				>
					<Icon icon={ <Ellipsis /> } />
				</Button>
			);
		};

		const renderMenu = ( renderContentArgs ) => (
			<NavigableMenu className="woocommerce-ellipsis-menu__content">
				{ renderContent( renderContentArgs ) }
			</NavigableMenu>
		);

		return (
			<div
				className={ classnames(
					className,
					'woocommerce-ellipsis-menu'
				) }
			>
				<Dropdown
					contentClassName="woocommerce-ellipsis-menu__popover"
					position="bottom left"
					renderToggle={ renderEllipsis }
					renderContent={ renderMenu }
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
	 * A function returning `MenuTitle`/`MenuItem` components as a render prop. Arguments from Dropdown passed as function arguments.
	 */
	renderContent: PropTypes.func,
	/**
	 * Classname to add to ellipsis menu.
	 */
	className: PropTypes.string,
	/**
	 * Callback function when dropdown button is clicked, it provides the click event.
	 */
	onToggle: PropTypes.func,
};

export default EllipsisMenu;
