/**
 * External dependencies
 */
import { createElement, Component } from '@wordpress/element';
import { noop } from 'lodash';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import SelectControl from '../select-control';
import type { Option } from '../select-control/types';
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
	type OptionCompletionValue,
} from './autocompleters';
import type { SearchProps, SearchState } from './types';

/**
 * A search box which autocompletes results while typing, allowing for the user to select an existing object
 * (product, order, customer, etc). Currently only products are supported.
 */
export class Search extends Component< SearchProps, SearchState > {
	static defaultProps = {
		allowFreeTextSearch: false,
		onChange: noop,
		selected: [],
		inlineTags: false,
		showClearButton: false,
		staticResults: false,
		disabled: false,
		multiple: true,
	};

	constructor( props: SearchProps ) {
		super( props );
		this.state = {
			options: [],
		};
		this.appendFreeTextSearch = this.appendFreeTextSearch.bind( this );
		this.fetchOptions = this.fetchOptions.bind( this );
		this.updateSelected = this.updateSelected.bind( this );
		this.onChange = this.onChange.bind( this );
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
				throw new Error(
					`No autocompleter found for type: ${ this.props.type }`
				);
		}
	}

	getFormattedOptions( options: unknown[], query: string ) {
		const autocompleter = this.getAutocompleter();
		const formattedOptions: Option[] = [];

		options.forEach( ( option ) => {
			const formattedOption = {
				key: autocompleter.getOptionIdentifier( option ).toString(),
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

	fetchOptions( previousOptions: unknown[], query: string | null ) {
		if ( ! query ) {
			return Promise.resolve( [] );
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

	updateSelected( selected: Option[] ) {
		// eslint-disable-next-line @typescript-eslint/no-unused-vars
		const { onChange: onChangeProp = ( _option: unknown[] ) => {} } =
			this.props;
		const autocompleter = this.getAutocompleter();

		const formattedSelections = selected.map<
			Option | OptionCompletionValue
		>( ( option ) => {
			return option.value
				? autocompleter.getOptionCompletion( option.value )
				: option;
		} );

		onChangeProp( formattedSelections );
	}

	onChange( selected: Option[] | string ) {
		if ( Array.isArray( selected ) ) {
			this.updateSelected( selected );
		}
	}

	appendFreeTextSearch( options: Option[], query: string | null ) {
		const { allowFreeTextSearch } = this.props;

		if ( ! query || ! query.length ) {
			return [];
		}

		const autocompleter = this.getAutocompleter();

		if (
			! allowFreeTextSearch ||
			typeof autocompleter.getFreeTextOptions !== 'function'
		) {
			return options;
		}

		return [
			...( autocompleter.getFreeTextOptions( query ) as Option[] ),
			...options,
		];
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
					onChange={ this.onChange }
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
