/**
 * External dependencies
 */
import interpolate from '@automattic/interpolate-components';
import { Button, Popover } from '@wordpress/components';
import { Icon, plus, search } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import React, { createElement, forwardRef } from 'react';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { Tree } from '../experimental-tree';
import { ComboBox } from '../experimental-select-control/combo-box';
import { SelectedItems } from '../experimental-select-control/selected-items';
import { SuffixIcon } from '../experimental-select-control/suffix-icon';
import { useAutocomplete } from './hooks/use-autocomplete';
import { AutocompleteProps } from './types';
import './autocomplete.scss';

export const Autocomplete = forwardRef( function ForwardedAutocomplete(
	{
		id,
		items,
		selected,
		multiple,
		allowCreate: allowCreate,
		onSelect,
		onRemove,
		onInputChange,
		onCreateClick,
		...props
	}: AutocompleteProps,
	ref: React.ForwardedRef< HTMLInputElement >
) {
	const {
		autocompleteProps,
		isMenuOpen,
		inputProps,
		comboBoxProps,
		menuContainerProps,
		menuContainerWidth,
		menuProps,
		allowCreateProps,
	} = useAutocomplete( {
		id,
		ref,
		items,
		selected,
		multiple,
		allowCreate,
		onSelect,
		onRemove,
		onInputChange,
		onCreateClick,
		...props,
	} );

	return (
		<div
			{ ...props }
			{ ...autocompleteProps }
			className="experimental-woocommerce-autocomplete"
		>
			<ComboBox
				comboBoxProps={ comboBoxProps }
				inputProps={ inputProps }
				suffix={ <SuffixIcon icon={ search } /> }
			>
				<div
					{ ...menuContainerProps }
					className="experimental-woocommerce-autocomplete__popover-menu-container"
				>
					{ isMenuOpen && (
						<Popover
							animate={ false }
							focusOnMount={ false }
							position="bottom left"
							className="experimental-woocommerce-autocomplete__popover-menu"
						>
							<div style={ { width: menuContainerWidth } }>
								<Tree
									{ ...menuProps }
									getItemLabel={ ( item ) => (
										<span>
											{ inputProps.value
												? interpolate( {
														mixedString:
															item.label.replace(
																new RegExp(
																	inputProps.value,
																	'ig'
																),
																( group ) =>
																	`{{bold}}${ group }{{/bold}}`
															),
														components: {
															bold: <b />,
														},
												  } )
												: item.label }
										</span>
									) }
								/>
							</div>

							{ allowCreate && (
								<Button
									{ ...allowCreateProps }
									type="button"
									className={ classNames(
										'experimental-woocommerce-autocomplete__create-action',
										allowCreateProps.className
									) }
								>
									<Icon icon={ plus } />
									<span>
										{ __( 'Create', 'woocommerce' ) }{ ' ' }
										{ allowCreateProps.children
											? `"${ allowCreateProps.children }"`
											: __( 'new', 'woocommerce' ) }
									</span>
								</Button>
							) }
						</Popover>
					) }
				</div>

				{ multiple && Array.isArray( selected ) && (
					<SelectedItems
						items={ selected }
						getItemLabel={ ( item ) => item?.label ?? '' }
						getItemValue={ ( item ) => item?.value ?? '' }
						getSelectedItemProps={ () => ( {} ) }
						onRemove={ onRemove }
					/>
				) }
			</ComboBox>
		</div>
	);
} );
