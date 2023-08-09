/**
 * External dependencies
 */
import classNames from 'classnames';
import { createElement } from 'react';
import { SelectedItems } from './selected-items';
import { DefaultItem, getItemLabelType, getItemValueType } from './types';

export type ComboboxProps< Item > = {
	isFocused: boolean;
	multiple: boolean;
	selected: Item[];
	isReadOnly: boolean;
	getItemLabel: getItemLabelType< Item >;
	getItemValue: getItemValueType< Item >;
	onRemove: ( item: Item ) => void;
	comboboxProps: Record< string, any >;
};

export function Combobox< Item = DefaultItem >( {
	isFocused,
	multiple,
	selected,
	isReadOnly,
	getItemLabel,
	getItemValue,
	onRemove,
	comboboxProps,
}: ComboboxProps< Item > ) {
	return (
		<div
			className={ classNames(
				'woocommerce-experimental-select-control__combo-box-wrapper',
				{ 'is-focused': isFocused }
			) }
		>
			<div className="woocommerce-experimental-select-control__items-wrapper">
				{ multiple && (
					<SelectedItems
						items={ selected as Item[] }
						isReadOnly={ isReadOnly }
						getItemLabel={ getItemLabel }
						getItemValue={ getItemValue }
						onRemove={ onRemove }
					/>
				) }
				<div className="woocommerce-experimental-select-control__combo-box">
					{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
					{ /* @ts-ignore TS complains about autocomplete despite it being a valid property. */ }
					<input type="text" { ...comboboxProps } />
				</div>
			</div>
		</div>
	);
}
