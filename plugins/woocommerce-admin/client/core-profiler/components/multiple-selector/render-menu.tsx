/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import {
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
} from '@woocommerce/components';
import { ChildrenProps } from '@woocommerce/components/build-types/experimental-select-control/types';
import clsx from 'clsx';

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
	}: ChildrenProps< {
		label: string;
		value: string;
	} > ) => {
		useEffect( () => {
			onOpenClose( isOpen );
		}, [ isOpen ] );

		return (
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
								className={ clsx( 'core-profiler__checkbox', {
									'is-selected': isSelected,
								} ) }
								onChange={ () => {} }
								checked={ isSelected }
								label={ item.label }
							/>
						</MenuItem>
					);
				} ) }
			</Menu>
		);
	};
