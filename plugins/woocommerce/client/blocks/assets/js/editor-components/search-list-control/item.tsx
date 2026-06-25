/**
 * External dependencies
 */
import clsx from 'clsx';
import { CheckboxControl } from '@wordpress/components';
import { useCallback, useEffect } from '@wordpress/element';
import { arrayDifferenceBy, arrayUnionBy } from '@woocommerce/utils';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import type {
	RenderItemArgs,
	SearchListItem as SearchListItemProps,
} from './types';
import { getHighlightedName, getBreadcrumbsForDisplay } from './utils';

const isExpandedOrDescendantIsExpanded = (
	item: SearchListItemProps,
	expandedPanelId: number
): boolean => {
	if ( item.id === expandedPanelId ) {
		return true;
	}
	if ( Array.isArray( item.children ) && item.children.length > 0 ) {
		return item.children.some( ( child ) =>
			isExpandedOrDescendantIsExpanded( child, expandedPanelId )
		);
	}
	return false;
};

const isSomeChildrenSelected = (
	item: SearchListItemProps,
	selected: SearchListItemProps[]
): boolean => {
	return ( item.children as SearchListItemProps[] ).some( ( child ) =>
		selected.find(
			( selectedItem ) =>
				selectedItem.id === child.id ||
				isSomeChildrenSelected( child, selected )
		)
	);
};

const getItemWithDescendants = (
	item: SearchListItemProps
): SearchListItemProps[] => {
	const descendants = item.children?.map( ( child ) =>
		getItemWithDescendants( child )
	);
	if ( ! descendants ) {
		return [ item ];
	}
	return [ item, ...descendants.flat() ];
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
	item,
	isSelected,
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

	useEffect( () => {
		if ( hasChildren && isSelected ) {
			setExpandedPanelId( item.id as number );
		}
	}, [ item, hasChildren, isSelected, setExpandedPanelId ] );

	const name = props.name || `search-list-item-${ controlId }`;
	const id = `${ name }-${ item.id }`;

	const togglePanel = useCallback( () => {
		if ( ! isExpanded ) {
			setExpandedPanelId( Number( item.id ) );
			return;
		}
		if ( item.parent ) {
			setExpandedPanelId( Number( item.parent ) );
			return;
		}
		setExpandedPanelId( -1 );
	}, [ isExpanded, item.id, setExpandedPanelId ] );

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
						onChange={ onSelect( item ) }
						onClick={ ( e ) => e.stopPropagation() }
						checked={ isSelected }
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
						checked={ isSelected }
						indeterminate={
							! isSelected &&
							isSomeChildrenSelected( item, selected )
						}
						label={ getHighlightedName(
							decodeEntities( item.name ),
							search
						) }
						onChange={ () => {
							const itemWithDescendants =
								getItemWithDescendants( item );
							if ( isSelected ) {
								onSelect(
									arrayDifferenceBy(
										selected,
										itemWithDescendants,
										'id'
									)
								)();
							} else {
								onSelect(
									arrayUnionBy(
										selected,
										itemWithDescendants,
										'id'
									)
								)();
							}
						} }
						onClick={ ( e ) => e.stopPropagation() }
					/>

					{ showCount ? (
						<Count label={ countLabel || item.count } />
					) : null }
				</>
			) }
		</div>
	) : (
		<label htmlFor={ id } className={ classes }>
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
						className="woocommerce-search-list__item-input"
					></input>

					<ItemLabel item={ item } search={ search } />
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
					onChange={ onSelect( item ) }
					checked={ isSelected }
				/>
			) }

			{ showCount ? <Count label={ countLabel || item.count } /> : null }
		</label>
	);
};

export default SearchListItem;
