/**
 * External dependencies
 */
import React, { useRef, useState } from 'react';
import { check, chevronDown, chevronUp, Icon } from '@wordpress/icons';
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useSelect, UseSelectState } from 'downshift';

/**
 * Internal dependencies
 */
import './style.scss';

export interface ListItem {
	key: string;
	name: string;
	group?: string;
	context?: string;
	className?: string;
	items?: string[];
}

export interface GroupedSelectControlProps< ItemType > {
	label: string;
	options: ItemType[];
	value?: ItemType | null;
	placeholder?: string;
	searchable?: boolean;
	name?: string;
	className?: string;
	onChange?: ( changes: Partial< UseSelectState< ItemType > > ) => void;
}

const GroupedSelectControl = < ItemType extends ListItem >( {
	name,
	className,
	label,
	options: listItems,
	onChange: onSelectedItemChange,
	value,
	placeholder,
	searchable,
}: GroupedSelectControlProps< ItemType > ): JSX.Element => {
	const searchRef = useRef< HTMLInputElement >( null );
	const previousStateRef = useRef< {
		visibleItems: Set< string >;
	} >();
	const groupKeys = listItems
		.filter( ( item ) => item.items?.length )
		.map( ( group ) => group.key );

	const [ openedGroups, setOpenedGroups ] = useState(
		new Set( [ groupKeys[ 0 ] ] )
	);

	const [ visibleItems, setVisibleItems ] = useState(
		new Set( [ ...groupKeys, ...( listItems[ 0 ]?.items || [] ) ] )
	);

	const [ searchText, setSearchText ] = useState( '' );

	const itemsToRender = listItems.filter( ( item ) =>
		visibleItems.has( item.key )
	);

	const {
		isOpen,
		selectedItem,
		getToggleButtonProps,
		getMenuProps,
		getLabelProps,
		highlightedIndex,
		getItemProps,
	} = useSelect( {
		items: itemsToRender,
		itemToString: ( item ) => item?.name || '',
		selectedItem: value || ( {} as ItemType ),
		onSelectedItemChange,
		stateReducer: ( state, { changes, type } ) => {
			if (
				searchable &&
				type === useSelect.stateChangeTypes.ToggleButtonKeyDownArrowDown
			) {
				return state;
			}

			if ( changes.selectedItem && changes.selectedItem.items ) {
				if ( searchText ) return state;
				const key = changes.selectedItem.key;
				if ( openedGroups.has( key ) ) {
					openedGroups.delete( key );
					changes.selectedItem.items.forEach( ( itemKey ) =>
						visibleItems.delete( itemKey )
					);
				} else {
					openedGroups.add( key );
					changes.selectedItem.items.forEach( ( itemKey ) =>
						visibleItems.add( itemKey )
					);
				}
				setOpenedGroups( openedGroups );
				setVisibleItems( visibleItems );
				return state;
			}

			return changes;
		},
	} );

	const handleSearch = ( {
		target,
	}: React.ChangeEvent< HTMLInputElement > ) => {
		if ( ! previousStateRef.current ) {
			previousStateRef.current = {
				visibleItems,
			};
		}

		if ( target.value === '' ) {
			setVisibleItems( previousStateRef.current.visibleItems );
			previousStateRef.current = undefined;
		} else {
			const filteredItems = listItems.filter(
				( item ) =>
					item?.group &&
					`${ item.name } ${ item.context || '' }`
						.toLowerCase()
						.includes( target.value.toLowerCase() )
			);
			const filteredGroups = filteredItems.map(
				( item ): string => item?.group || ''
			);
			const filteredVisibleItems = new Set( [
				...filteredItems.map( ( i ) => i.key ),
				...filteredGroups,
			] );
			setVisibleItems( filteredVisibleItems );
		}

		setSearchText( target.value );
	};

	const menuProps = getMenuProps( {
		className: 'components-grouped-select-control__list',
		'aria-hidden': ! isOpen,
		onFocus: () => searchRef.current?.focus(),
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		onBlur: ( event: any ) => {
			if ( event.relatedTarget === searchRef.current ) {
				event.nativeEvent.preventDownshiftDefault = true;
			}
		},
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		onKeyDown: ( event: any ) => {
			if ( event.code === 'Space' ) {
				event.nativeEvent.preventDownshiftDefault = true;
			}
		},
	} );

	return (
		<div
			className={ clsx(
				'woopayments components-grouped-select-control',
				className
			) }
		>
			{ /* eslint-disable-next-line jsx-a11y/label-has-associated-control */ }
			<label
				{ ...getLabelProps( {
					className: 'components-grouped-select-control__label',
				} ) }
			>
				{ label }
			</label>
			<button
				{ ...getToggleButtonProps( {
					type: 'button',
					className: clsx(
						'components-text-control__input components-grouped-select-control__button',
						{ placeholder: ! selectedItem?.name }
					),
					name,
				} ) }
			>
				<span className="components-grouped-select-control__button-value">
					{ selectedItem?.name || placeholder }
				</span>
				<Icon
					icon={ chevronDown }
					className="components-grouped-select-control__button-icon"
				/>
			</button>
			<div { ...menuProps }>
				{ isOpen && (
					<>
						{ searchable && (
							<input
								className="components-grouped-select-control__search"
								ref={ searchRef }
								type="text"
								value={ searchText }
								onChange={ handleSearch }
								tabIndex={ -1 }
								placeholder={ __( 'Search…', 'woocommerce' ) }
							/>
						) }
						<ul className="components-grouped-select-control__list-container">
							{ itemsToRender.map( ( item, index ) => {
								const isGroup = !! item.items;

								return (
									// eslint-disable-next-line react/jsx-key
									<li
										key={ item.key }
										{ ...getItemProps( {
											item,
											index,
											className: clsx(
												'components-grouped-select-control__item',
												item.className,
												{
													'is-highlighted':
														index ===
														highlightedIndex,
												},
												{
													'is-group': isGroup,
												}
											),
										} ) }
									>
										<div className="components-grouped-select-control__item-content">
											{ item.name }
										</div>
										{ item.key === selectedItem?.key && (
											<Icon icon={ check } />
										) }
										{ ! searchText && isGroup && (
											<Icon
												icon={
													openedGroups.has( item.key )
														? chevronUp
														: chevronDown
												}
											/>
										) }
									</li>
								);
							} ) }
						</ul>
					</>
				) }
			</div>
		</div>
	);
};

export default GroupedSelectControl;
