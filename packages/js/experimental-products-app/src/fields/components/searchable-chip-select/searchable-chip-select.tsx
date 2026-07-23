/**
 * External dependencies
 */
import { Combobox as BaseCombobox } from '@base-ui/react/combobox';
import clsx from 'clsx';
import { forwardRef } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Item, SearchableChipSelectProps } from './types';

const DEFAULT_ITEMS: Item[] = [];

function itemToStringLabel( item: Item ) {
	return item.label;
}

function itemToStringValue( item: Item ) {
	return item.value;
}

function isItemEqualToValue( item: Item, value: Item ) {
	return item.value === value.value;
}

/**
 * A searchable multi-selection component with chips, with support for
 * a footer item to create new items.
 */
export const SearchableChipSelect = forwardRef<
	HTMLDivElement,
	SearchableChipSelectProps
>( function SearchableChipSelect(
	{
		children,
		creatableItem,
		disabled,
		emptyContent = __( 'No results found.', 'woocommerce' ),
		items = DEFAULT_ITEMS,
		chipsContent,
		placeholderChip,
		searchPlaceholder = __( 'Search', 'woocommerce' ),
		showClearButton = true,
		clearButtonLabel = __( 'Clear all', 'woocommerce' ),
		'aria-label': ariaLabel,
		'aria-labelledby': ariaLabelledby,
		'aria-describedby': ariaDescribedby,
		itemToStringLabel: customItemToStringLabel = itemToStringLabel,
		itemToStringValue: customItemToStringValue = itemToStringValue,
		isItemEqualToValue: customIsItemEqualToValue = isItemEqualToValue,
		...restProps
	},
	ref
) {
	const rootItems = creatableItem ? [ ...items, creatableItem ] : items;

	return (
		<BaseCombobox.Root< Item, true >
			items={ rootItems }
			multiple
			disabled={ disabled }
			itemToStringLabel={ customItemToStringLabel }
			itemToStringValue={ customItemToStringValue }
			isItemEqualToValue={ customIsItemEqualToValue }
			{ ...restProps }
		>
			<BaseCombobox.Chips
				ref={ ref }
				render={
					<div
						className={ clsx(
							'woocommerce-searchable-chip-select',
							disabled &&
								'woocommerce-searchable-chip-select--is-disabled'
						) }
					/>
				}
			>
				<BaseCombobox.Value>
					{ ( value: Item[] ) => {
						const hasValue = value.length > 0;
						const showPlaceholderChip =
							! hasValue && Boolean( placeholderChip );

						return hasValue || showPlaceholderChip ? (
							<div className="woocommerce-searchable-chip-select__chips-edit-area">
								<div className="woocommerce-searchable-chip-select__chips-list">
									{ hasValue && chipsContent
										? chipsContent( value )
										: value.map( ( item ) => (
												<BaseCombobox.Chip
													key={ item.value }
													className="woocommerce-searchable-chip-select__chip"
												>
													<span className="woocommerce-searchable-chip-select__chip-content">
														{ item.label }
													</span>
													<BaseCombobox.ChipRemove
														className="woocommerce-searchable-chip-select__chip-remove"
														aria-label={ __(
															'Remove',
															'woocommerce'
														) }
													>
														<span aria-hidden="true">
															×
														</span>
													</BaseCombobox.ChipRemove>
												</BaseCombobox.Chip>
										  ) ) }
									{ showPlaceholderChip && (
										<span className="woocommerce-searchable-chip-select__chip woocommerce-searchable-chip-select__chip--is-placeholder">
											<span className="woocommerce-searchable-chip-select__chip-content">
												{ placeholderChip }
											</span>
										</span>
									) }
								</div>
								{ hasValue && showClearButton && (
									<BaseCombobox.Clear
										className="woocommerce-searchable-chip-select__clear"
										aria-label={ clearButtonLabel }
									>
										<span aria-hidden="true">×</span>
									</BaseCombobox.Clear>
								) }
							</div>
						) : null;
					} }
				</BaseCombobox.Value>

				<BaseCombobox.Input
					className="woocommerce-searchable-chip-select__input"
					placeholder={ searchPlaceholder }
					aria-label={ ariaLabel }
					aria-labelledby={ ariaLabelledby }
					aria-describedby={ ariaDescribedby }
				/>
			</BaseCombobox.Chips>

			<BaseCombobox.Portal>
				<BaseCombobox.Positioner className="woocommerce-searchable-chip-select__positioner">
					<BaseCombobox.Popup className="woocommerce-searchable-chip-select__popup">
						<BaseCombobox.Empty className="woocommerce-searchable-chip-select__empty">
							{ emptyContent }
						</BaseCombobox.Empty>
						<BaseCombobox.List className="woocommerce-searchable-chip-select__list">
							<BaseCombobox.Collection>
								{ ( item: Item, index: number ) => {
									if ( item.value === creatableItem?.value ) {
										return null;
									}
									if ( children ) {
										return children( item, index );
									}
									return (
										<BaseCombobox.Item
											key={ item.value }
											value={ item }
											disabled={ item.disabled }
											className="woocommerce-searchable-chip-select__item"
										>
											{ item.label }
										</BaseCombobox.Item>
									);
								} }
							</BaseCombobox.Collection>
							{ creatableItem && (
								<BaseCombobox.Item
									value={ creatableItem }
									disabled={ creatableItem.disabled }
									className={ clsx(
										'woocommerce-searchable-chip-select__item',
										'woocommerce-searchable-chip-select__item--is-creatable'
									) }
								>
									{ creatableItem.label }
								</BaseCombobox.Item>
							) }
						</BaseCombobox.List>
					</BaseCombobox.Popup>
				</BaseCombobox.Positioner>
			</BaseCombobox.Portal>
		</BaseCombobox.Root>
	);
} );
