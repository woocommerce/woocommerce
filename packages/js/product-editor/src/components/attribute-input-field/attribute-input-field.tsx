/**
 * External dependencies
 */
import { Spinner } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { MenuAttributeList } from './menu-attribute-list';
import {
	AttributeInputFieldProps,
	getItemPropsType,
	getMenuPropsType,
	AttributeInputFieldItemProps,
	UseComboboxGetItemPropsOptions,
	UseComboboxGetMenuPropsOptions,
} from './types';

export const AttributeInputField: React.FC< AttributeInputFieldProps > = ( {
	value = null,
	items = [],
	isLoading,
	onChange,
	placeholder,
	label,
	disabled,
	disabledAttributeMessage,
	createNewAttributesAsGlobal = false,
} ) => {
	const getFilteredItems = (
		allItems: AttributeInputFieldItemProps[],
		inputValue: string
	) => {
		const filteredItems = allItems.filter( ( item ) =>
			( item.name || '' )
				.toLowerCase()
				.startsWith( inputValue.toLowerCase() )
		);

		if (
			inputValue.length > 0 &&
			( createNewAttributesAsGlobal ||
				! allItems.find(
					( item ) =>
						item.name.toLowerCase() === inputValue.toLowerCase()
				) )
		) {
			return [
				...filteredItems,
				{
					id: -99,
					name: inputValue,
				},
			];
		}

		return filteredItems;
	};

	return (
		<SelectControl< AttributeInputFieldItemProps >
			className="woocommerce-attribute-input-field"
			items={ items }
			label={ label || '' }
			disabled={ disabled }
			getFilteredItems={ getFilteredItems }
			placeholder={ placeholder }
			getItemLabel={ ( item ) => item?.name || '' }
			getItemValue={ ( item ) => item?.id || '' }
			selected={ value }
			onSelect={ onChange }
			__experimentalOpenMenuOnFocus
		>
			{ ( {
				items: renderItems,
				highlightedIndex,
				getItemProps,
				getMenuProps,
				isOpen,
			}: {
				items: AttributeInputFieldItemProps[];
				highlightedIndex: number;
				getItemProps: (
					options: UseComboboxGetItemPropsOptions< AttributeInputFieldItemProps >
					// eslint-disable-next-line @typescript-eslint/no-explicit-any
				) => any;
				getMenuProps: getMenuPropsType;
				isOpen: boolean;
			} ) => {
				return (
					<Menu getMenuProps={ getMenuProps } isOpen={ isOpen }>
						{ isLoading ? (
							<Spinner />
						) : (
							<MenuAttributeList
								renderItems={ renderItems }
								highlightedIndex={ highlightedIndex }
								disabledAttributeMessage={
									disabledAttributeMessage
								}
								getItemProps={
									getItemProps as (
										options: UseComboboxGetMenuPropsOptions
									) => getItemPropsType< AttributeInputFieldItemProps >
								}
							/>
						) }
					</Menu>
				);
			} }
		</SelectControl>
	);
};
