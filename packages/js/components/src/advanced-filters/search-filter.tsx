/**
 * External dependencies
 */
import { createElement, Component, Fragment } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { find, isEqual } from 'lodash';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import Search from '../search';
import type { SearchProps, SearchType } from '../search';
import {
	backwardsCompatibleCreateInterpolateElement as createInterpolateElement,
	textContent,
} from './utils';
import type {
	ActiveFilter,
	ActiveFilterValue,
	FilterComponentProps,
	FilterConfig,
	FilterRule,
	Query,
	SearchLabel,
} from './types';

export type SearchFilterProps = FilterComponentProps;

/**
 * A selected search value. Keys come from API ids, so they may be numeric at runtime.
 */
export type SearchSelection = {
	key: string | number;
	label: string;
	id?: string | number;
};

type SearchFilterState = {
	selected: SearchSelection[];
};

const normalizeFilterValue = ( value: ActiveFilterValue | undefined ) => {
	if ( Array.isArray( value ) ) {
		return value.join( ',' );
	}
	return typeof value === 'string' ? value : '';
};

class SearchFilter extends Component< SearchFilterProps, SearchFilterState > {
	constructor( props: SearchFilterProps ) {
		super( props );
		const { filter, query } = props;
		this.onSearchChange = this.onSearchChange.bind( this );
		this.state = {
			selected: [],
		};

		this.updateLabels = this.updateLabels.bind( this );

		const filterValue = normalizeFilterValue( filter.value );
		if ( filterValue.length ) {
			this.loadLabels( filterValue, query );
		}
	}

	componentDidUpdate( prevProps: SearchFilterProps ) {
		const { filter, query } = this.props;
		const { filter: prevFilter } = prevProps;
		const filterValue = normalizeFilterValue( filter.value );

		if ( filterValue.length && ! isEqual( prevFilter, filter ) ) {
			const { selected } = this.state;
			const selectedIds = selected.map( ( item ) => String( item.key ) );
			const filterIds = filterValue
				.split( ',' )
				.filter( Boolean )
				.map( String );
			const haveIdsChanged =
				filterIds.length !== selectedIds.length ||
				filterIds.some( ( id ) => ! selectedIds.includes( id ) );

			if ( haveIdsChanged ) {
				this.loadLabels( filterValue, query );
			}
		}
	}

	loadLabels( filterValue: string, query?: Query ) {
		void this.props.config.input
			.getLabels?.( filterValue, query )
			.then( ( selected ) => {
				if (
					filterValue ===
					normalizeFilterValue( this.props.filter.value )
				) {
					this.updateLabels( selected );
				}
			} );
	}

	updateLabels( selected: SearchLabel[] ) {
		const normalizedSelected = selected
			.map( ( item ) => ( {
				...item,
				key: item.key ?? item.id,
			} ) )
			.filter(
				( item ): item is SearchSelection => item.key !== undefined
			);
		const prevIds = this.state.selected.map( ( item ) => item.key );
		const ids = normalizedSelected.map( ( item ) => item.key );

		if ( ! isEqual( [ ...ids ].sort(), [ ...prevIds ].sort() ) ) {
			this.setState( { selected: normalizedSelected } );
		}
	}

	onSearchChange( values: SearchSelection[] ) {
		this.setState( {
			selected: values,
		} );
		const { onFilterChange } = this.props;
		const idList = values.map( ( value ) => value.key ).join( ',' );
		onFilterChange( { property: 'value', value: idList } );
	}

	getScreenReaderText( filter: ActiveFilter, config: FilterConfig ) {
		const { selected } = this.state;

		if ( selected.length === 0 ) {
			return '';
		}

		const rule: Partial< FilterRule > =
			find( config.rules, { value: filter.rule } ) || {};
		const filterStr = selected.map( ( item ) => item.label ).join( ', ' );

		return textContent(
			createInterpolateElement( config.labels.title, {
				filter: <Fragment>{ filterStr }</Fragment>,
				rule: <Fragment>{ rule.label }</Fragment>,
				title: <Fragment />,
			} )
		);
	}

	render() {
		const { className, config, filter, onFilterChange, isEnglish } =
			this.props;
		const { selected } = this.state;
		const { rule } = filter;
		const { input, labels, rules } = config;
		const children = createInterpolateElement( labels.title, {
			title: <span className={ className } />,
			rule: (
				<SelectControl
					__next40pxDefaultSize
					className={ clsx(
						className,
						'woocommerce-filters-advanced__rule'
					) }
					options={ rules }
					value={ rule }
					onChange={ ( value ) =>
						onFilterChange( { property: 'rule', value } )
					}
					aria-label={ labels.rule }
				/>
			),
			filter: (
				<Search
					className={ clsx(
						className,
						'woocommerce-filters-advanced__input'
					) }
					onChange={ this.onSearchChange }
					type={ input.type as SearchType }
					autocompleter={ input.autocompleter }
					placeholder={ labels.placeholder }
					// Search types keys as strings, but ids resolved from the API are numeric.
					selected={ selected as SearchProps[ 'selected' ] }
					inlineTags
					aria-label={ labels.filter }
				/>
			),
		} );

		const screenReaderText = this.getScreenReaderText( filter, config );

		return (
			<fieldset
				className="woocommerce-filters-advanced__line-item"
				tabIndex={ 0 }
			>
				<legend className="screen-reader-text">
					{ labels.add || '' }
				</legend>
				<div
					className={ clsx(
						'woocommerce-filters-advanced__fieldset',
						{
							'is-english': isEnglish,
						}
					) }
				>
					{ children }
				</div>
				{ screenReaderText && (
					<span className="screen-reader-text">
						{ screenReaderText }
					</span>
				) }
			</fieldset>
		);
	}
}

export default SearchFilter;
