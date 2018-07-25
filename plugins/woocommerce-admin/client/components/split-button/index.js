/**
 * External dependencies
 *
 * @format
 */
import { Button, IconButton, Dropdown, NavigableMenu } from '@wordpress/components';
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';

const SplitButton = ( {
	isPrimary,
	mainIcon,
	mainLabel,
	onClick,
	menuLabel,
	controls,
	className,
} ) => {
	if ( ! controls || ! controls.length ) {
		return null;
	}

	const MainButtonComponent = ( mainIcon && IconButton ) || Button;
	const classes = classnames( 'woocommerce-split-button', className, {
		'is-primary': isPrimary,
		'has-label': mainLabel,
	} );

	return (
		<div className={ classes }>
			<MainButtonComponent
				icon={ mainIcon }
				className="woocommerce-split-button__main-action"
				onClick={ onClick }
			>
				{ mainLabel }
			</MainButtonComponent>
			<Dropdown
				className="woocommerce-split-button__menu"
				position="bottom left"
				contentClassName="woocommerce-split-button__menu-popover"
				expandOnMobile
				headerTitle={ menuLabel }
				renderToggle={ ( { isOpen, onToggle } ) => {
					return (
						<IconButton
							icon={ isOpen ? 'arrow-up' : 'arrow-down' }
							className={ classnames( 'woocommerce-split-button__menu-toggle', {
								'is-active': isOpen,
							} ) }
							onClick={ onToggle }
							aria-haspopup="true"
							aria-expanded={ isOpen }
							label={ menuLabel }
							tooltip={ menuLabel }
						/>
					);
				} }
				renderContent={ ( { onClose } ) => {
					const renderControl = ( control, index ) => {
						const ButtonComponent = ( control.icon && IconButton ) || Button;
						return (
							<ButtonComponent
								key={ index }
								onClick={ event => {
									event.stopPropagation();
									onClose();
									if ( control.onClick ) {
										control.onClick();
									}
								} }
								className="woocommerce-split-button__menu-item"
								icon={ control.icon || '' }
								role="menuitem"
							>
								{ control.label }
							</ButtonComponent>
						);
					};

					return (
						<NavigableMenu
							className="woocommerce-split-button__menu-wrapper"
							role="menu"
							aria-label={ menuLabel }
						>
							{ controls.map( renderControl ) }
						</NavigableMenu>
					);
				} }
			/>
		</div>
	);
};

SplitButton.propTypes = {
	isPrimary: PropTypes.bool,
	mainIcon: PropTypes.node,
	mainLabel: PropTypes.string,
	onClick: PropTypes.func,
	menuLabel: PropTypes.string,
	controls: PropTypes.arrayOf(
		PropTypes.shape( {
			icon: PropTypes.node,
			label: PropTypes.string,
			onClick: PropTypes.func,
		} )
	).isRequired,
	className: PropTypes.string,
};

SplitButton.defaultProps = {
	isPrimary: false,
	onClick: noop,
};

export default SplitButton;
