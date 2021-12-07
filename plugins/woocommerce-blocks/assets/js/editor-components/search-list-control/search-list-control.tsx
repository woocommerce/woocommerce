/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	Spinner,
	TextControl,
	withSpokenMessages,
} from '@wordpress/components';
import {
	useState,
	useMemo,
	useEffect,
	useCallback,
	Fragment,
} from '@wordpress/element';
import { Icon, info } from '@wordpress/icons';
import classnames from 'classnames';
import { useInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { getFilteredList, defaultMessages } from './utils';
import SearchListItem from './item';
import Tag from '../tag';
import type {
	SearchListItemsType,
	SearchListItemType,
	SearchListControlProps,
	SearchListMessages,
	renderItemArgs,
} from './types';

const defaultRenderListItem = ( args: renderItemArgs ): JSX.Element => {
	return <SearchListItem { ...args } />;
};

const ListItems = ( props: {
	list: SearchListItemsType;
	selected: SearchListItemsType;
	renderItem: ( args: renderItemArgs ) => JSX.Element;
	depth?: number;
	onSelect: ( item: SearchListItemType ) => () => void;
	instanceId: string | number;
	isSingle: boolean;
	search: string;
} ): JSX.Element | null => {
	const {
		list,
		selected,
		renderItem,
		depth = 0,
		onSelect,
		instanceId,
		isSingle,
		search,
	} = props;
	if ( ! list ) {
		return null;
	}
	return (
		<>
			{ list.map( ( item ) => {
				const isSelected =
					selected.findIndex( ( { id } ) => id === item.id ) !== -1;
				return (
					<Fragment key={ item.id }>
						<li>
							{ renderItem( {
								item,
								isSelected,
								onSelect,
								isSingle,
								search,
								depth,
								controlId: instanceId,
							} ) }
						</li>
						<ListItems
							{ ...props }
							list={ item.children }
							depth={ depth + 1 }
						/>
					</Fragment>
				);
			} ) }
		</>
	);
};

const SelectedListItems = ( {
	isLoading,
	isSingle,
	selected,
	messages,
	onChange,
	onRemove,
}: SearchListControlProps & {
	messages: SearchListMessages;
	onRemove: ( itemId: string | number ) => () => void;
} ) => {
	if ( isLoading || isSingle || ! selected ) {
		return null;
	}
	const selectedCount = selected.length;
	return (
		<div className="woocommerce-search-list__selected">
			<div className="woocommerce-search-list__selected-header">
				<strong>{ messages.selected( selectedCount ) }</strong>
				{ selectedCount > 0 ? (
					<Button
						isLink
						isDestructive
						onClick={ () => onChange( [] ) }
						aria-label={ messages.clear }
					>
						{ __( 'Clear all', 'woo-gutenberg-products-block' ) }
					</Button>
				) : null }
			</div>
			{ selectedCount > 0 ? (
				<ul>
					{ selected.map( ( item, i ) => (
						<li key={ i }>
							<Tag
								label={ item.name }
								id={ item.id }
								remove={ onRemove }
							/>
						</li>
					) ) }
				</ul>
			) : null }
		</div>
	);
};

const ListItemsContainer = ( {
	filteredList,
	search,
	onSelect,
	instanceId,
	...props
}: SearchListControlProps & {
	messages: SearchListMessages;
	filteredList: SearchListItemsType;
	search: string;
	instanceId: string | number;
	onSelect: ( item: SearchListItemType ) => () => void;
} ) => {
	const { messages, renderItem, selected, isSingle } = props;
	const renderItemCallback = renderItem || defaultRenderListItem;

	if ( filteredList.length === 0 ) {
		return (
			<div className="woocommerce-search-list__list is-not-found">
				<span className="woocommerce-search-list__not-found-icon">
					<Icon icon={ info } />
				</span>
				<span className="woocommerce-search-list__not-found-text">
					{ search
						? // eslint-disable-next-line @wordpress/valid-sprintf
						  sprintf( messages.noResults, search )
						: messages.noItems }
				</span>
			</div>
		);
	}

	return (
		<ul className="woocommerce-search-list__list">
			<ListItems
				list={ filteredList }
				selected={ selected }
				renderItem={ renderItemCallback }
				onSelect={ onSelect }
				instanceId={ instanceId }
				isSingle={ isSingle }
				search={ search }
			/>
		</ul>
	);
};

/**
 * Component to display a searchable, selectable list of items.
 */
export const SearchListControl = (
	props: SearchListControlProps
): JSX.Element => {
	const {
		className = '',
		isCompact,
		isHierarchical,
		isLoading,
		isSingle,
		list,
		messages: customMessages = defaultMessages,
		onChange,
		onSearch,
		selected,
		debouncedSpeak,
	} = props;
	const [ search, setSearch ] = useState( '' );
	const instanceId = useInstanceId( SearchListControl );
	const messages = useMemo(
		() => ( { ...defaultMessages, ...customMessages } ),
		[ customMessages ]
	);
	const filteredList = useMemo( () => {
		return getFilteredList( list, search, isHierarchical );
	}, [ list, search, isHierarchical ] );

	useEffect( () => {
		if ( debouncedSpeak ) {
			debouncedSpeak( messages.updated );
		}
	}, [ debouncedSpeak, messages ] );

	useEffect( () => {
		if ( typeof onSearch === 'function' ) {
			onSearch( search );
		}
	}, [ search, onSearch ] );

	const onRemove = useCallback(
		( itemId: string | number ) => () => {
			if ( isSingle ) {
				onChange( [] );
			}
			const i = selected.findIndex(
				( { id: selectedId } ) => selectedId === itemId
			);
			onChange( [
				...selected.slice( 0, i ),
				...selected.slice( i + 1 ),
			] );
		},
		[ isSingle, selected, onChange ]
	);

	const onSelect = useCallback(
		( item: SearchListItemType ) => () => {
			if ( selected.findIndex( ( { id } ) => id === item.id ) !== -1 ) {
				onRemove( item.id )();
				return;
			}
			if ( isSingle ) {
				onChange( [ item ] );
			} else {
				onChange( [ ...selected, item ] );
			}
		},
		[ isSingle, onRemove, onChange, selected ]
	);

	return (
		<div
			className={ classnames( 'woocommerce-search-list', className, {
				'is-compact': isCompact,
			} ) }
		>
			<SelectedListItems
				{ ...props }
				onRemove={ onRemove }
				messages={ messages }
			/>
			<div className="woocommerce-search-list__search">
				<TextControl
					label={ messages.search }
					type="search"
					value={ search }
					onChange={ ( value ) => setSearch( value ) }
				/>
			</div>
			{ isLoading ? (
				<div className="woocommerce-search-list__list is-loading">
					<Spinner />
				</div>
			) : (
				<ListItemsContainer
					{ ...props }
					search={ search }
					filteredList={ filteredList }
					messages={ messages }
					onSelect={ onSelect }
					instanceId={ instanceId }
				/>
			) }
		</div>
	);
};

export default withSpokenMessages( SearchListControl );
