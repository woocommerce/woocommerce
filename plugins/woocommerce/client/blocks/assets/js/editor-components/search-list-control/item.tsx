/**
 * External dependencies
 */
import clsx from 'clsx';
import { CheckboxControl } from '@wordpress/components';
import { useCallback } from '@wordpress/element';
import { arrayDifferenceBy, arrayUnionBy } from '@woocommerce/utils';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import type {
	RenderItemArgs,
	SearchListItem as SearchListItemProps,
} from './types';
import {
	getHighlightedName,
	getBreadcrumbsForDisplay,
	isExpandedOrDescendantIsExpanded,
} from './utils';

const getItemDescendants = (
	item: SearchListItemProps
): SearchListItemProps[] => {
	const descendants = item.children?.map( ( child ) => [
		child,
		...getItemDescendants( child ),
	] );
	if ( ! descendants ) {
		return [];
	}
	return descendants.flat();
};

const areSomeDescendantsSelected = (
	item: SearchListItemProps,
	selected: SearchListItemProps[]
): boolean => {
	const descendants = getItemDescendants( item );
	return descendants.some( ( descendant ) =>
		selected.find( ( selectedItem ) => selectedItem.id === descendant.id )
	);
};

const areAllDescendantsSelected = (
	item: SearchListItemProps,
	selected: SearchListItemProps[]
): boolean => {
	const descendants = getItemDescendants( item );
	return descendants.every( ( descendant ) =>
		selected.find( ( selectedItem ) => selectedItem.id === descendant.id )
	);
};

const Count = ( { label }: { label: string | React.ReactNode | number } ) => {
	return (
		<span className="woocommerce-search-list__item-count">{ label }</span>
	);
};

const ItemLabel = ( props: { item: SearchListItemProps; search: string } ) => {
	const { item, search } = props;
	const hasBreadcrumbs = item.breadcrumbs && item.breadcrumbs.length;

	return (
		<span className="woocommerce-search-list__item-label">
			{ hasBreadcrumbs ? (
				<span className="woocommerce-search-list__item-prefix">
					{ getBreadcrumbsForDisplay( item.breadcrumbs ) }
				</span>
			) : null }
			<span className="woocommerce-search-list__item-name">
				{ getHighlightedName( decodeEntities( item.name ), search ) }
			</span>
		</span>
	);
};

export const SearchListItem = < T extends object = object >( {
	countLabel,
	className,
	depth = 0,
	controlId = '',
	disabled = false,
	item,
	isSelected,
	isSelectable = true,
	isSingle,
	onSelect,
	search = '',
	selected,
	useExpandedPanelId,
	...props
}: RenderItemArgs< T > ): JSX.Element => {
	const [ expandedPanelId, setExpandedPanelId ] = useExpandedPanelId;
	const showCount =
		countLabel !== undefined &&
		countLabel !== null &&
		item.count !== undefined &&
		item.count !== null;
	const hasBreadcrumbs = !! item.breadcrumbs?.length;
	const hasChildren = !! item.children?.length;
	const isExpanded = isExpandedOrDescendantIsExpanded(
		item,
		expandedPanelId
	);
	const classes = clsx(
		[ 'woocommerce-search-list__item', `depth-${ depth }`, className ],
		{
			'has-breadcrumbs': hasBreadcrumbs,
			'has-children': hasChildren,
			'has-count': showCount,
			'is-expanded': isExpanded,
			'is-radio-button': isSingle,
		}
	);

	const name = props.name || `search-list-item-${ controlId }`;
	const id = `${ name }-${ item.id }`;

	const togglePanel = useCallback( () => {
		if (
			! isExpanded &&
			( typeof item.id === 'number' || typeof item.id === 'string' )
		) {
			setExpandedPanelId( item.id );
			return;
		}
		if (
			item.parent &&
			( typeof item.parent === 'number' ||
				typeof item.parent === 'string' )
		) {
			setExpandedPanelId( item.parent );
			return;
		}
		setExpandedPanelId( null );
	}, [ isExpanded, item.id, item.parent, setExpandedPanelId ] );

	// Non-selectable items (like Product Attributes) should look selected when
	// all their descendants are selected, but look indeterminate when only some
	// are selected.
	const looksSelected =
		( isSelected || ! isSelectable ) &&
		areAllDescendantsSelected( item, selected );

	return hasChildren ? (
		<div
			className={ classes }
			onClick={ togglePanel }
			onKeyDown={ ( e ) =>
				e.key === 'Enter' || e.key === ' ' ? togglePanel() : null
			}
			role="treeitem"
			tabIndex={ 0 }
		>
			{ isSingle ? (
				<>
					<input
						type="radio"
						id={ id }
						name={ name }
						value={ item.value }
						onChange={ () => {
							onSelect( item )();
							if ( ! isExpanded ) {
								setExpandedPanelId( item.id );
							}
						} }
						onClick={ ( e ) => e.stopPropagation() }
						checked={ isSelected }
						disabled={ disabled }
						className="woocommerce-search-list__item-input"
						{ ...props }
					/>

					<ItemLabel item={ item } search={ search } />

					{ showCount ? (
						<Count label={ countLabel || item.count } />
					) : null }
				</>
			) : (
				<>
					<CheckboxControl
						className="woocommerce-search-list__item-input"
						checked={ looksSelected }
						disabled={ disabled }
						indeterminate={
							! looksSelected &&
							( isSelected ||
								areSomeDescendantsSelected( item, selected ) )
						}
						label={ getHighlightedName(
							decodeEntities( item.name ),
							search
						) }
						onClick={ ( e ) => e.stopPropagation() }
						onKeyDown={ ( e ) => e.stopPropagation() }
						onChange={ () => {
							const descendants = getItemDescendants( item );
							const itemsToToggle = isSelectable
								? [ item, ...descendants ]
								: [ ...descendants ];
							const allDescendantsAreSelected =
								areAllDescendantsSelected( item, selected );
							if (
								( isSelectable && isSelected ) ||
								( ! isSelectable && allDescendantsAreSelected )
							) {
								onSelect(
									arrayDifferenceBy(
										selected,
										itemsToToggle,
										'id'
									)
								)();
							} else {
								onSelect(
									arrayUnionBy(
										selected,
										itemsToToggle,
										'id'
									)
								)();
								if ( ! isExpanded ) {
									setExpandedPanelId( item.id );
								}
							}
						} }
						__nextHasNoMarginBottom={ true }
					/>

					{ showCount ? (
						<Count label={ countLabel || item.count } />
					) : null }
				</>
			) }
		</div>
	) : (
		// Items can be enabled via the radios and checkboxes. But we make the
		// whole row clickable for convenience.
		// eslint-disable-next-line jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events
		<div
			className={ classes }
			onClick={ disabled ? undefined : onSelect( item ) }
		>
			{ isSingle ? (
				<>
					<input
						{ ...props }
						type="radio"
						id={ id }
						name={ name }
						value={ item.value }
						onChange={ onSelect( item ) }
						checked={ isSelected }
						disabled={ disabled }
						className="woocommerce-search-list__item-input"
						onClick={ ( e ) => e.stopPropagation() }
					/>

					<label htmlFor={ id }>
						<ItemLabel item={ item } search={ search } />
					</label>
				</>
			) : (
				<CheckboxControl
					{ ...props }
					id={ id }
					name={ name }
					className="woocommerce-search-list__item-input"
					value={ decodeEntities( item.value ) }
					label={ getHighlightedName(
						decodeEntities( item.name ),
						search
					) }
					onChange={ () => {
						onSelect( item )();
						if ( ! isExpanded ) {
							setExpandedPanelId( item.id );
						}
					} }
					checked={ isSelected }
					disabled={ disabled }
					__nextHasNoMarginBottom={ true }
					onClick={ ( e ) => e.stopPropagation() }
				/>
			) }

			{ showCount ? <Count label={ countLabel || item.count } /> : null }
		</div>
	);
};

export default SearchListItem;
