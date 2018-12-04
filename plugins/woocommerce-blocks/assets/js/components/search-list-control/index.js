/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	Button,
	MenuItem,
	MenuGroup,
	Spinner,
	TextControl,
	withSpokenMessages,
} from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose, withInstanceId, withState } from '@wordpress/compose';
import { escapeRegExp, findIndex } from 'lodash';
import Gridicon from 'gridicons';
import PropTypes from 'prop-types';
import { Tag } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';
import { buildTermsTree } from './hierarchy';
import { CheckedIcon, UncheckedIcon } from './icons';

const defaultMessages = {
	clear: __( 'Clear all selected items', 'woo-gutenberg-products-block' ),
	list: __( 'Results', 'woo-gutenberg-products-block' ),
	noItems: __( 'No items found.', 'woo-gutenberg-products-block' ),
	noResults: __( 'No results for %s', 'woo-gutenberg-products-block' ),
	search: __( 'Search for items', 'woo-gutenberg-products-block' ),
	selected: ( n ) =>
		sprintf( _n( '%d item selected', '%d items selected', n, 'woo-gutenberg-products-block' ), n ),
	updated: __( 'Search results updated.', 'woo-gutenberg-products-block' ),
};

/**
 * Component to display a searchable, selectable list of items.
 */
export class SearchListControl extends Component {
	constructor() {
		super( ...arguments );

		this.onSelect = this.onSelect.bind( this );
		this.onRemove = this.onRemove.bind( this );
		this.onClear = this.onClear.bind( this );
		this.isSelected = this.isSelected.bind( this );
		this.defaultRenderItem = this.defaultRenderItem.bind( this );
		this.renderList = this.renderList.bind( this );
	}

	onRemove( id ) {
		const { selected, onChange } = this.props;
		return () => {
			const i = findIndex( selected, { id } );
			onChange( [ ...selected.slice( 0, i ), ...selected.slice( i + 1 ) ] );
		};
	}

	onSelect( item ) {
		const { selected, onChange } = this.props;
		return () => {
			if ( this.isSelected( item ) ) {
				this.onRemove( item.id )();
				return;
			}
			onChange( [ ...selected, item ] );
		};
	}

	onClear() {
		this.props.onChange( [] );
	}

	isSelected( item ) {
		return -1 !== findIndex( this.props.selected, { id: item.id } );
	}

	getFilteredList( list, search ) {
		const { isHierarchical } = this.props;
		if ( ! search ) {
			return isHierarchical ? buildTermsTree( list ) : list;
		}
		const messages = { ...defaultMessages, ...this.props.messages };
		const re = new RegExp( escapeRegExp( search ), 'i' );
		this.props.debouncedSpeak( messages.updated );
		const filteredList = list
			.map( ( item ) => ( re.test( item.name ) ? item : false ) )
			.filter( Boolean );
		return isHierarchical ? buildTermsTree( filteredList, list ) : filteredList;
	}

	getHighlightedName( name, search ) {
		if ( ! search ) {
			return name;
		}
		const re = new RegExp( escapeRegExp( search ), 'ig' );
		return name.replace( re, '<strong>$&</strong>' );
	}

	defaultRenderItem( {
		depth = 0,
		getHighlightedName,
		item,
		isSelected,
		onSelect,
		search = '',
	} ) {
		const classes = [ 'woocommerce-search-list__item' ];
		if ( this.props.isHierarchical ) {
			classes.push( `depth-${ depth }` );
		}

		return (
			<MenuItem
				key={ item.id }
				role="menuitemcheckbox"
				className={ classes.join( ' ' ) }
				onClick={ onSelect( item ) }
				isSelected={ isSelected }
			>
				<span className="woocommerce-search-list__item-state">
					{ isSelected ? <CheckedIcon /> : <UncheckedIcon /> }
				</span>
				<span
					className="woocommerce-search-list__item-name"
					dangerouslySetInnerHTML={ {
						__html: getHighlightedName( item.name, search ),
					} }
				/>
			</MenuItem>
		);
	}

	renderList( list, depth = 0 ) {
		const { search } = this.props;
		const renderItem = this.props.renderItem || this.defaultRenderItem;
		if ( ! list ) {
			return null;
		}
		return list.map( ( item ) => (
			<Fragment key={ item.id }>
				{ renderItem( {
					getHighlightedName: this.getHighlightedName,
					item,
					isSelected: this.isSelected( item ),
					onSelect: this.onSelect,
					search,
					depth,
				} ) }
				{ this.renderList( item.children, depth + 1 ) }
			</Fragment>
		) );
	}

	renderListSection() {
		const { isLoading, search } = this.props;
		const list = this.getFilteredList( this.props.list, search );
		const messages = { ...defaultMessages, ...this.props.messages };

		if ( isLoading ) {
			return (
				<div className="woocommerce-search-list__list is-loading">
					<Spinner />
				</div>
			);
		}

		if ( ! list.length ) {
			return (
				<div className="woocommerce-search-list__list is-not-found">
					<span className="woocommerce-search-list__not-found-icon">
						<Gridicon
							icon="notice-outline"
							role="img"
							aria-hidden="true"
							focusable="false"
						/>
					</span>
					<span className="woocommerce-search-list__not-found-text">
						{ search ? sprintf( messages.noResults, search ) : messages.noItems }
					</span>
				</div>
			);
		}

		return (
			<MenuGroup
				label={ messages.list }
				className="woocommerce-search-list__list"
			>
				{ this.renderList( list ) }
			</MenuGroup>
		);
	}

	render() {
		const { className = '', search, selected, setState } = this.props;
		const messages = { ...defaultMessages, ...this.props.messages };
		const selectedCount = selected.length;

		return (
			<div className={ `woocommerce-search-list ${ className }` }>
				<div className="woocommerce-search-list__selected">
					<div className="woocommerce-search-list__selected-header">
						<strong>{ messages.selected( selectedCount ) }</strong>
						{ selectedCount > 0 ? (
							<Button
								isLink
								isDestructive
								onClick={ this.onClear }
								aria-label={ messages.clear }
							>
								{ __( 'Clear all', 'woo-gutenberg-products-block' ) }
							</Button>
						) : null }
					</div>
					{ selected.map( ( item, i ) => (
						<Tag
							key={ i }
							label={ item.name }
							id={ item.id }
							remove={ this.onRemove }
						/>
					) ) }
				</div>

				<div className="woocommerce-search-list__search">
					<TextControl
						label={ messages.search }
						type="search"
						value={ search }
						onChange={ ( value ) => setState( { search: value } ) }
					/>
				</div>

				{ this.renderListSection() }
			</div>
		);
	}
}

SearchListControl.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
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
		 * Label for the list of selectable items, only read to screen reader users.
		 */
		list: PropTypes.string,
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
	 * Callback to render each item in the selection list, allows any custom object-type rendering.
	 */
	renderItem: PropTypes.func,
	/**
	 * The list of currently selected items.
	 */
	selected: PropTypes.array.isRequired,
	// from withState
	search: PropTypes.string,
	setState: PropTypes.func,
	// from withSpokenMessages
	debouncedSpeak: PropTypes.func,
	// from withInstanceId
	instanceId: PropTypes.number,
};

export default compose( [
	withState( {
		search: '',
	} ),
	withSpokenMessages,
	withInstanceId,
] )( SearchListControl );
