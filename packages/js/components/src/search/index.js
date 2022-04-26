/**
 * External dependencies
 */
import { createElement, Component } from '@wordpress/element';
import { noop } from 'lodash';
import PropTypes from 'prop-types';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import SelectControl from '../select-control';
import {
	attributes,
	countries,
	coupons,
	customers,
	downloadIps,
	emails,
	orders,
	product,
	productCategory,
	taxes,
	usernames,
	variableProduct,
	variations,
} from './autocompleters';

/**
 * A search box which autocompletes results while typing, allowing for the user to select an existing object
 * (product, order, customer, etc). Currently only products are supported.
 */
export class Search extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			options: [],
		};
		this.appendFreeTextSearch = this.appendFreeTextSearch.bind( this );
		this.fetchOptions = this.fetchOptions.bind( this );
		this.updateSelected = this.updateSelected.bind( this );
	}

	getAutocompleter() {
		switch ( this.props.type ) {
			case 'attributes':
				return attributes;
			case 'categories':
				return productCategory;
			case 'countries':
				return countries;
			case 'coupons':
				return coupons;
			case 'customers':
				return customers;
			case 'downloadIps':
				return downloadIps;
			case 'emails':
				return emails;
			case 'orders':
				return orders;
			case 'products':
				return product;
			case 'taxes':
				return taxes;
			case 'usernames':
				return usernames;
			case 'variableProducts':
				return variableProduct;
			case 'variations':
				return variations;
			case 'custom':
				if (
					! this.props.autocompleter ||
					typeof this.props.autocompleter !== 'object'
				) {
					throw new Error(
						"Invalid autocompleter provided to Search component, it requires a completer object when using 'custom' type."
					);
				}
				return this.props.autocompleter;
			default:
				return {};
		}
	}

	getFormattedOptions( options, query ) {
		const autocompleter = this.getAutocompleter();
		const formattedOptions = [];

		options.forEach( ( option ) => {
			const formattedOption = {
				key: autocompleter.getOptionIdentifier( option ),
				label: autocompleter.getOptionLabel( option, query ),
				keywords: autocompleter
					.getOptionKeywords( option )
					.filter( Boolean ),
				value: option,
			};
			formattedOptions.push( formattedOption );
		} );

		return formattedOptions;
	}

	fetchOptions( previousOptions, query ) {
		if ( ! query ) {
			return [];
		}

		const autocompleterOptions = this.getAutocompleter().options;

		// Support arrays, sync- & async functions that returns an array.
		const resolvedOptions = Promise.resolve(
			typeof autocompleterOptions === 'function'
				? autocompleterOptions( query )
				: autocompleterOptions || []
		);
		return resolvedOptions.then( async ( response ) => {
			const options = this.getFormattedOptions( response, query );
			this.setState( { options } );
			return options;
		} );
	}

	updateSelected( selected ) {
		const { onChange } = this.props;
		const autocompleter = this.getAutocompleter();

		const formattedSelections = selected.map( ( option ) => {
			return option.value
				? autocompleter.getOptionCompletion( option.value )
				: option;
		} );

		onChange( formattedSelections );
	}

	appendFreeTextSearch( options, query ) {
		const { allowFreeTextSearch } = this.props;

		if ( ! query || ! query.length ) {
			return [];
		}

		if ( ! allowFreeTextSearch ) {
			return options;
		}
		const autocompleter = this.getAutocompleter();

		return [ ...autocompleter.getFreeTextOptions( query ), ...options ];
	}

	render() {
		const autocompleter = this.getAutocompleter();
		const {
			className,
			inlineTags,
			placeholder,
			selected,
			showClearButton,
			staticResults,
			disabled,
			multiple,
		} = this.props;
		const { options } = this.state;
		const inputType = autocompleter.inputType
			? autocompleter.inputType
			: 'text';

		return (
			<div>
				<SelectControl
					className={ classnames( 'woocommerce-search', className, {
						'is-static-results': staticResults,
					} ) }
					disabled={ disabled }
					hideBeforeSearch
					inlineTags={ inlineTags }
					isSearchable
					getSearchExpression={ autocompleter.getSearchExpression }
					multiple={ multiple }
					placeholder={ placeholder }
					onChange={ this.updateSelected }
					onFilter={ this.appendFreeTextSearch }
					onSearch={ this.fetchOptions }
					options={ options }
					searchDebounceTime={ 500 }
					searchInputType={ inputType }
					selected={ selected }
					showClearButton={ showClearButton }
				/>
			</div>
		);
	}
}

Search.propTypes = {
	/**
	 * Render additional options in the autocompleter to allow free text entering depending on the type.
	 */
	allowFreeTextSearch: PropTypes.bool,
	/**
	 * Class name applied to parent div.
	 */
	className: PropTypes.string,
	/**
	 * Function called when selected results change, passed result list.
	 */
	onChange: PropTypes.func,
	/**
	 * The object type to be used in searching.
	 */
	type: PropTypes.oneOf( [
		'attributes',
		'categories',
		'countries',
		'coupons',
		'customers',
		'downloadIps',
		'emails',
		'orders',
		'products',
		'taxes',
		'usernames',
		'variableProducts',
		'variations',
		'custom',
	] ).isRequired,
	/**
	 * The custom autocompleter to be used in searching when type is 'custom'
	 */
	autocompleter: PropTypes.object,
	/**
	 * A placeholder for the search input.
	 */
	placeholder: PropTypes.string,
	/**
	 * An array of objects describing selected values or optionally a string for a single value.
	 * If the label of the selected value is omitted, the Tag of that value will not
	 * be rendered inside the search box.
	 */
	selected: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.arrayOf(
			PropTypes.shape( {
				key: PropTypes.oneOfType( [
					PropTypes.number,
					PropTypes.string,
				] ).isRequired,
				label: PropTypes.string,
			} )
		),
	] ),
	/**
	 * Render tags inside input, otherwise render below input.
	 */
	inlineTags: PropTypes.bool,
	/**
	 * Render a 'Clear' button next to the input box to remove its contents.
	 */
	showClearButton: PropTypes.bool,
	/**
	 * Render results list positioned statically instead of absolutely.
	 */
	staticResults: PropTypes.bool,
	/**
	 * Whether the control is disabled or not.
	 */
	disabled: PropTypes.bool,
};

Search.defaultProps = {
	allowFreeTextSearch: false,
	onChange: noop,
	selected: [],
	inlineTags: false,
	showClearButton: false,
	staticResults: false,
	disabled: false,
	multiple: true,
};

export default Search;
