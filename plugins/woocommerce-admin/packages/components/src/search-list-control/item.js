/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { escapeRegExp, first, last, isNil } from 'lodash';
import PropTypes from 'prop-types';

function getHighlightedName( name, search ) {
	if ( ! search ) {
		return name;
	}
	const re = new RegExp( escapeRegExp( search ), 'ig' );
	const nameParts = name.split( re );
	return nameParts.map( ( part, i ) => {
		if ( i === 0 ) {
			return part;
		}
		return (
			<Fragment key={ i }>
				<strong>{ search }</strong>
				{ part }
			</Fragment>
		);
	} );
}

function getBreadcrumbsForDisplay( breadcrumbs ) {
	if ( breadcrumbs.length === 1 ) {
		return first( breadcrumbs );
	}
	if ( breadcrumbs.length === 2 ) {
		return first( breadcrumbs ) + ' › ' + last( breadcrumbs );
	}

	return first( breadcrumbs ) + ' … ' + last( breadcrumbs );
}

const SearchListItem = ( {
	countLabel,
	className,
	depth = 0,
	controlId = '',
	item,
	isSelected,
	isSingle,
	onSelect,
	search = '',
	...props
} ) => {
	const showCount = ! isNil( countLabel ) || ! isNil( item.count );
	const classes = [ className, 'woocommerce-search-list__item' ];
	classes.push( `depth-${ depth }` );
	if ( isSingle ) {
		classes.push( 'is-radio-button' );
	}
	if ( showCount ) {
		classes.push( 'has-count' );
	}
	const hasBreadcrumbs = item.breadcrumbs && item.breadcrumbs.length;
	const name = props.name || `search-list-item-${ controlId }`;
	const id = `${ name }-${ item.id }`;

	return (
		<label htmlFor={ id } className={ classes.join( ' ' ) }>
			{ isSingle ? (
				<input
					type="radio"
					id={ id }
					name={ name }
					value={ item.value }
					onChange={ onSelect( item ) }
					checked={ isSelected }
					className="woocommerce-search-list__item-input"
					{ ...props }
				></input>
			) : (
				<input
					type="checkbox"
					id={ id }
					name={ name }
					value={ item.value }
					onChange={ onSelect( item ) }
					checked={ isSelected }
					className="woocommerce-search-list__item-input"
					{ ...props }
				></input>
			) }

			<span className="woocommerce-search-list__item-label">
				{ hasBreadcrumbs ? (
					<span className="woocommerce-search-list__item-prefix">
						{ getBreadcrumbsForDisplay( item.breadcrumbs ) }
					</span>
				) : null }
				<span className="woocommerce-search-list__item-name">
					{ getHighlightedName( item.name, search ) }
				</span>
			</span>

			{ !! showCount && (
				<span className="woocommerce-search-list__item-count">
					{ countLabel || item.count }
				</span>
			) }
		</label>
	);
};

SearchListItem.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * Label to display in the count bubble. Takes preference over `item.count`.
	 */
	countLabel: PropTypes.node,
	/**
	 * Unique id of the parent control.
	 */
	controlId: PropTypes.node,
	/**
	 * Depth, non-zero if the list is hierarchical.
	 */
	depth: PropTypes.number,
	/**
	 * Current item to display.
	 */
	item: PropTypes.object,
	/**
	 * Name of the inputs. Used to group input controls together. See:
	 * https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#attr-name
	 * If not provided, a default name will be generated using the controlId.
	 */
	name: PropTypes.string,
	/**
	 * Whether this item is selected.
	 */
	isSelected: PropTypes.bool,
	/**
	 * Whether this should only display a single item (controls radio vs checkbox icon).
	 */
	isSingle: PropTypes.bool,
	/**
	 * Callback for selecting the item.
	 */
	onSelect: PropTypes.func,
	/**
	 * Search string, used to highlight the substring in the item name.
	 */
	search: PropTypes.string,
};

export default SearchListItem;
