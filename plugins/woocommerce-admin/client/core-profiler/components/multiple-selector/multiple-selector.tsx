/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	__experimentalSelectControl as SelectControl,
	selectControlStateChangeTypes,
} from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { renderMenu } from './render-menu';
import './multiple-selector.scss';

type Props = {
	options: Array< { label: string; value: string } >;
	onSelect: (
		selectedOptions: Array< { label: string; value: string } >
	) => void;
	initialValues?: Array< { label: string; value: string } >;
	placeholder?: string;
	onOpenClose?: ( isOpen: boolean ) => void;
};

export const MultipleSelector = ( {
	options,
	onSelect,
	initialValues = [],
	placeholder = __( 'Select platforms', 'woocommerce' ),
	onOpenClose = () => {},
}: Props ) => {
	const [ selectedOptions, setSelectedOptions ] = useState( initialValues );

	return (
		<SelectControl
			label=""
			multiple
			__experimentalOpenMenuOnFocus
			items={ options }
			getFilteredItems={ ( allItems ) => allItems }
			selected={ selectedOptions }
			inputProps={ {
				'aria-readonly': true,
				'aria-label': __(
					'Use up and down arrow keys to navigate',
					'woocommerce'
				),
			} }
			onKeyDown={ ( e ) => {
				if ( e.key.length <= 1 ) {
					e.preventDefault();
					return false;
				}
			} }
			placeholder={ selectedOptions.length ? '' : placeholder }
			stateReducer={ ( state, actionAndChanges ) => {
				const { changes, type } = actionAndChanges;
				switch ( type ) {
					case selectControlStateChangeTypes.ControlledPropUpdatedSelectedItem:
						return {
							...changes,
							inputValue: state.inputValue,
						};
					case selectControlStateChangeTypes.ItemClick:
						return {
							...changes,
							isOpen: true,
							inputValue: state.inputValue,
							highlightedIndex: state.highlightedIndex,
						};
					default:
						return changes;
				}
			} }
			onSelect={ ( item ) => {
				if ( ! item ) {
					return;
				}
				const exist = selectedOptions.find(
					( existingItem ) => existingItem.value === item.value
				);
				const updatedPlatforms = exist
					? selectedOptions.filter(
							( existingItem ) =>
								existingItem.value !== item.value
					  )
					: [ ...selectedOptions, item ];
				setSelectedOptions( updatedPlatforms );
				if ( onSelect ) {
					onSelect( updatedPlatforms );
				}
			} }
			onRemove={ ( item ) =>
				setSelectedOptions(
					selectedOptions.filter( ( i ) => i !== item )
				)
			}
		>
			{ renderMenu( { selectedOptions, onOpenClose } ) }
		</SelectControl>
	);
};
