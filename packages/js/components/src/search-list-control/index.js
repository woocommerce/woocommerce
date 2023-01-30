/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	Button,
	Spinner,
	TextControl,
	withSpokenMessages,
} from '@wordpress/components';
import {
	createElement,
	Fragment,
	useState,
	useEffect,
} from '@wordpress/element';
import { compose, withInstanceId } from '@wordpress/compose';
import { escapeRegExp, findIndex } from 'lodash';
import NoticeOutlineIcon from 'gridicons/dist/notice-outline';
import PropTypes from 'prop-types';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { buildTermsTree } from './hierarchy';
import SearchListItem from './item';
import Tag from '../tag';

const defaultMessages = {
	clear: __( 'Clear all selected items', 'woocommerce' ),
	noItems: __( 'No items found.', 'woocommerce' ),
	noResults: __( 'No results for %s', 'woocommerce' ),
	search: __( 'Search for items', 'woocommerce' ),
	selected: ( n ) =>
		sprintf(
			/* translators: Number of items selected from list. */
			_n( '%d item selected', '%d items selected', n, 'woocommerce' ),
			n
		),
	updated: __( 'Search results updated.', 'woocommerce' ),
};

/**
 * Component to display a searchable, selectable list of items.
 *
 * @param {Object} props
 */
export const SearchListControl = ( props ) => {
	const [ searchValue, setSearchValue ] = useState( props.search || '' );
	const {
		isSingle,
		isLoading,
		onChange,
		selected,
		instanceId,
		messages: propsMessages,
		isCompact,
		debouncedSpeak,
		onSearch,
		className = '',
	} = props;

	const messages = { ...defaultMessages, ...propsMessages };

	useEffect( () => {
		if ( typeof onSearch === 'function' ) {
			onSearch( searchValue );
		}
	}, [ onSearch, searchValue ] );

	const onRemove = ( id ) => {
		return () => {
			if ( isSingle ) {
				onChange( [] );
			}
			const i = findIndex( selected, { id } );
			onChange( [
				...selected.slice( 0, i ),
				...selected.slice( i + 1 ),
			] );
		};
	};

	const onSelect = ( item ) => {
		return () => {
			if ( isSelected( item ) ) {
				onRemove( item.id )();
				return;
			}
			if ( isSingle ) {
				onChange( [ item ] );
			} else {
				onChange( [ ...selected, item ] );
			}
		};
	};

	const isSelected = ( item ) =>
		findIndex( selected, { id: item.id } ) !== -1;

	const getFilteredList = ( list, search ) => {
		const { isHierarchical } = props;
		if ( ! search ) {
			return isHierarchical ? buildTermsTree( list ) : list;
		}
		const re = new RegExp( escapeRegExp( search ), 'i' );
		debouncedSpeak( messages.updated );
		const filteredList = list
			.map( ( item ) => ( re.test( item.name ) ? item : false ) )
			.filter( Boolean );
		return isHierarchical
			? buildTermsTree( filteredList, list )
			: filteredList;
	};

	const defaultRenderItem = ( args ) => {
		return <SearchListItem { ...args } />;
	};

	const renderList = ( list, depth = 0 ) => {
		const renderItem = props.renderItem || defaultRenderItem;
		if ( ! list ) {
			return null;
		}

		return list.map( ( item ) => (
			<Fragment key={ item.id }>
				<li>
					{ renderItem( {
						item,
						isSelected: isSelected( item ),
						onSelect,
						isSingle,
						search: searchValue,
						depth,
						controlId: instanceId,
					} ) }
				</li>
				{ renderList( item.children, depth + 1 ) }
			</Fragment>
		) );
	};

	const renderListSection = () => {
		if ( isLoading ) {
			return (
				<div className="woocommerce-search-list__list is-loading">
					<Spinner />
				</div>
			);
		}
		const list = getFilteredList( props.list, searchValue );

		if ( ! list.length ) {
			return (
				<div className="woocommerce-search-list__list is-not-found">
					<span className="woocommerce-search-list__not-found-icon">
						<NoticeOutlineIcon
							role="img"
							aria-hidden="true"
							focusable="false"
						/>
					</span>
					<span className="woocommerce-search-list__not-found-text">
						{ searchValue
							? // eslint-disable-next-line @wordpress/valid-sprintf
							  sprintf( messages.noResults, searchValue )
							: messages.noItems }
					</span>
				</div>
			);
		}

		return (
			<ul className="woocommerce-search-list__list">
				{ renderList( list ) }
			</ul>
		);
	};

	const renderSelectedSection = () => {
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
							onClick={ onChange( [] ) }
							aria-label={ messages.clear }
						>
							{ __( 'Clear all', 'woocommerce' ) }
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

	return (
		<div
			className={ classnames( 'woocommerce-search-list', className, {
				'is-compact': isCompact,
			} ) }
		>
			{ renderSelectedSection() }

			<div className="woocommerce-search-list__search">
				<TextControl
					label={ messages.search }
					type="search"
					value={ searchValue }
					onChange={ ( value ) => setSearchValue( value ) }
				/>
			</div>

			{ renderListSection() }
		</div>
	);
};

SearchListControl.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * Whether it should be displayed in a compact way, so it occupies less space.
	 */
	isCompact: PropTypes.bool,
	/**
	 * Whether the list of items is hierarchical or not. If true, each list item is expected to
	 * have a parent property.
	 */
	isHierarchical: PropTypes.bool,
	/**
	 * Whether the list of items is still loading.
	 */
	isLoading: PropTypes.bool,
	/**
	 * Restrict selections to one item.
	 */
	isSingle: PropTypes.bool,
	/**
	 * A complete list of item objects, each with id, name properties. This is displayed as a
	 * clickable/keyboard-able list, and possibly filtered by the search term (searches name).
	 */
	list: PropTypes.arrayOf(
		PropTypes.shape( {
			id: PropTypes.number,
			name: PropTypes.string,
		} )
	),
	/**
	 * Messages displayed or read to the user. Configure these to reflect your object type.
	 * See `defaultMessages` above for examples.
	 */
	messages: PropTypes.shape( {
		/**
		 * A more detailed label for the "Clear all" button, read to screen reader users.
		 */
		clear: PropTypes.string,
		/**
		 * Message to display when the list is empty (implies nothing loaded from the server
		 * or parent component).
		 */
		noItems: PropTypes.string,
		/**
		 * Message to display when no matching results are found. %s is the search term.
		 */
		noResults: PropTypes.string,
		/**
		 * Label for the search input
		 */
		search: PropTypes.string,
		/**
		 * Label for the selected items. This is actually a function, so that we can pass
		 * through the count of currently selected items.
		 */
		selected: PropTypes.func,
		/**
		 * Label indicating that search results have changed, read to screen reader users.
		 */
		updated: PropTypes.string,
	} ),
	/**
	 * Callback fired when selected items change, whether added, cleared, or removed.
	 * Passed an array of item objects (as passed in via props.list).
	 */
	onChange: PropTypes.func.isRequired,
	/**
	 * Callback fired when the search field is used.
	 */
	onSearch: PropTypes.func,
	/**
	 * Callback to render each item in the selection list, allows any custom object-type rendering.
	 */
	renderItem: PropTypes.func,
	/**
	 * The list of currently selected items.
	 */
	selected: PropTypes.array.isRequired,
	// from withSpokenMessages
	debouncedSpeak: PropTypes.func,
	// from withInstanceId
	instanceId: PropTypes.number,
};

export default compose( [ withSpokenMessages, withInstanceId ] )(
	SearchListControl
);
