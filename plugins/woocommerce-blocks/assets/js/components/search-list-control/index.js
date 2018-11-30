/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import {
	Button,
	MenuItem,
	MenuGroup,
	TextControl,
	withSpokenMessages,
} from '@wordpress/components';
import { Component } from '@wordpress/element';
import { compose, withInstanceId, withState } from '@wordpress/compose';
import { escapeRegExp, findIndex } from 'lodash';
import PropTypes from 'prop-types';
import { Tag } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';

const defaultMessages = {
	clear: __( 'Clear all selected items', 'woocommerce' ),
	list: __( 'Results', 'woocommerce' ),
	noResults: __( 'No results for %s', 'woocommerce' ),
	search: __( 'Search for items', 'woocommerce' ),
	selected: ( n ) =>
		sprintf( _n( '%d item selected', '%d items selected', n, 'woocommerce' ), n ),
	updated: __( 'Search results updated.', 'woocommerce' ),
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
		this.defaultRenderItem = this.defaultRenderItem.bind( this );
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
		if ( ! search ) {
			return list.filter( ( item ) => item && ! this.isSelected( item ) );
		}
		const messages = { ...defaultMessages, ...this.props.messages };
		const re = new RegExp( escapeRegExp( search ), 'i' );
		this.props.debouncedSpeak( messages.updated );
		return list
			.map( ( item ) => ( re.test( item.name ) ? item : false ) )
			.filter( ( item ) => item && ! this.isSelected( item ) );
	}

	getHighlightedName( name, search ) {
		if ( ! search ) {
			return name;
		}
		const re = new RegExp( escapeRegExp( search ), 'ig' );
		return name.replace( re, '<strong>$&</strong>' );
	}

	defaultRenderItem( { getHighlightedName, item, onSelect, search } ) {
		return (
			<MenuItem
				key={ item.id }
				className="woocommerce-search-list__item"
				onClick={ onSelect( item ) }
			>
				<span
					className="woocommerce-search-list__item-name"
					dangerouslySetInnerHTML={ {
						__html: getHighlightedName( item.name, search ),
					} }
				/>
			</MenuItem>
		);
	}

	render() {
		const { className = '', search, selected, setState } = this.props;
		const messages = { ...defaultMessages, ...this.props.messages };
		const list = this.getFilteredList( this.props.list, search );
		const noResults = search ? sprintf( messages.noResults, search ) : null;
		const renderItem = this.props.renderItem || this.defaultRenderItem;
		const selectedCount = selected.length;

		return (
			<div className={ `woocommerce-search-list ${ className }` }>
				{ selectedCount > 0 ? (
					<div className="woocommerce-search-list__selected">
						<div className="woocommerce-search-list__selected-header">
							<strong>{ messages.selected( selectedCount ) }</strong>
							<Button isLink isDestructive onClick={ this.onClear } aria-label={ messages.clear }>
								{ __( 'Clear all', 'woocommerce' ) }
							</Button>
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
				) : null }

				<div className="woocommerce-search-list__search">
					<TextControl
						label={ messages.search }
						type="search"
						value={ search }
						onChange={ ( value ) => setState( { search: value } ) }
					/>
				</div>

				{ ! list.length ? (
					noResults
				) : (
					<MenuGroup
						label={ messages.list }
						className="woocommerce-search-list__list"
					>
						{ list.map( ( item ) =>
							renderItem( {
								getHighlightedName: this.getHighlightedName,
								item,
								onSelect: this.onSelect,
								search,
							} )
						) }
					</MenuGroup>
				) }
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
