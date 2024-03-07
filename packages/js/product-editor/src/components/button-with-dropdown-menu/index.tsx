/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { chevronDown } from '@wordpress/icons';
import { Button, DropdownMenu, Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ButtonWithDropdownMenuProps } from './types';

export * from './types';

export function ButtonWithDropdownMenu( {
	dropdownButtonLabel = __( 'More options', 'woocommerce' ),
	controls,
	defaultOpen = false,
	popoverProps: {
		placement = 'bottom-end',
		position = 'bottom left left',
		offset = 0,
	} = {
		placement: 'bottom-end',
		position: 'bottom left left',
		offset: 0,
	},
	className,
	renderMenu,
	...props
}: ButtonWithDropdownMenuProps ) {
	return (
		<Flex
			className={ `woocommerce-button-with-dropdown-menu${
				className?.length ? ' ' + className : ''
			}` }
			justify="left"
			gap={ 0 }
			expanded={ false }
			role="group"
		>
			<FlexItem role="none">
				<Button
					{ ...props }
					className="woocommerce-button-with-dropdown-menu__main-button"
				/>
			</FlexItem>

			<FlexItem role="none">
				<DropdownMenu
					toggleProps={ {
						className:
							'woocommerce-button-with-dropdown-menu__dropdown-button',
						variant: props.variant,
					} }
					controls={ controls }
					icon={ chevronDown }
					label={ dropdownButtonLabel }
					popoverProps={ {
						placement,
						// @ts-expect-error no exported member.
						position,
						offset,
					} }
					defaultOpen={ defaultOpen }
				>
					{ renderMenu }
				</DropdownMenu>
			</FlexItem>
		</Flex>
	);
}
