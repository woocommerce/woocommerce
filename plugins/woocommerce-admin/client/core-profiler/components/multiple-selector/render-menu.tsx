/**
 * External dependencies
 */
import { TouchEvent, MouseEvent } from 'react';
import { CheckboxControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import {
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
} from '@woocommerce/components';
import { ChildrenProps } from '@woocommerce/components/build-types/experimental-select-control/types';

type Props = {
	selectedOptions: Array< { label: string; value: string } >;
	onOpenClose: ( isOpen: boolean ) => void;
};

export const renderMenu =
	( { selectedOptions, onOpenClose }: Props ) =>
	( {
		items,
		highlightedIndex,
		isOpen,
		getItemProps,
		getMenuProps,
		closeMenu,
	}: ChildrenProps< {
		label: string;
		value: string;
	} > ) => {
		useEffect( () => {
			onOpenClose( isOpen );
		}, [ isOpen ] );

		const possiblyCloseMenu = ( event: TouchEvent | MouseEvent ) => {
			if ( isOpen ) {
				// eslint-disable-next-line max-len
				// We need to use blur() and closeMenu() because closeMenu doesn't work as expected.
				// We should fix it in upstream and remove blur() then.
				document
					.querySelector< HTMLInputElement >(
						'.woocommerce-experimental-select-control__input'
					)
					?.blur();
				closeMenu();
				// Stop propagation to prevent the input from being focused.
				event.preventDefault();
				event.stopPropagation();
			}
		};

		return (
			<>
				{ /*
				This is a workaround to toggle the platform dropdown when clicking itself
				because the select control doesn't support it.
			*/ }
				<div
					aria-hidden="true"
					className="woocommerce-experimental-select-control__input-overlay"
					onTouchEnd={ possiblyCloseMenu }
					onMouseDown={ possiblyCloseMenu }
				/>

				<Menu
					isOpen={ isOpen }
					getMenuProps={ getMenuProps }
					scrollIntoViewOnOpen={ true }
				>
					{ items.map( ( item, menuIndex ) => {
						const isSelected = selectedOptions.includes( item );
						return (
							<MenuItem
								key={ `${ item.value }` }
								index={ menuIndex }
								item={ item }
								getItemProps={ getItemProps }
								isActive={ highlightedIndex === menuIndex }
								activeStyle={ {
									backgroundColor: '#f6f7f7',
								} }
							>
								<CheckboxControl
									onChange={ () => {} }
									checked={ isSelected }
									label={ item.label }
								/>
							</MenuItem>
						);
					} ) }
				</Menu>
			</>
		);
	};
