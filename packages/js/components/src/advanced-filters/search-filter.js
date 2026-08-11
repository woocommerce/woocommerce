/**
 * External dependencies
 */
import { createElement, Component, Fragment } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { find, isEqual } from 'lodash';
import PropTypes from 'prop-types';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import Search from '../search';
import {
	backwardsCompatibleCreateInterpolateElement as createInterpolateElement,
	textContent,
} from './utils';

class SearchFilter extends Component {
	constructor( { filter, config, query } ) {
		super( ...arguments );
		this.onSearchChange = this.onSearchChange.bind( this );
		this.state = {
			selected: [],
		};

		this.updateLabels = this.updateLabels.bind( this );

		if ( filter.value.length ) {
			config.input
				.getLabels( filter.value, query )
				.then( this.updateLabels );
		}
	}

	componentDidUpdate( prevProps ) {
		const { config, filter, query } = this.props;
		const { filter: prevFilter } = prevProps;

		if ( filter.value.length && ! isEqual( prevFilter, filter ) ) {
			const { selected } = this.state;
			const selectedIds = selected.map( ( item ) => String( item.key ) );
			const filterIds = filter.value
				.split( ',' )
				.filter( Boolean )
				.map( String );
			const haveIdsChanged =
				filterIds.length !== selectedIds.length ||
				filterIds.some( ( id ) => ! selectedIds.includes( id ) );

			if ( haveIdsChanged ) {
				config.input
					.getLabels( filter.value, query )
					.then( this.updateLabels );
			}
		}
	}

	updateLabels( selected ) {
		const normalizedSelected = selected.map( ( item ) => ( {
			...item,
			key: item.key ?? item.id,
		} ) );
		const prevIds = this.state.selected.map( ( item ) => item.key );
		const ids = normalizedSelected.map( ( item ) => item.key );

		if ( ! isEqual( [ ...ids ].sort(), [ ...prevIds ].sort() ) ) {
			this.setState( { selected: normalizedSelected } );
		}
	}

	onSearchChange( values ) {
		this.setState( {
			selected: values,
		} );
		const { onFilterChange } = this.props;
		const idList = values.map( ( value ) => value.key ).join( ',' );
		onFilterChange( { property: 'value', value: idList } );
	}

	getScreenReaderText( filter, config ) {
		const { selected } = this.state;

		if ( selected.length === 0 ) {
			return '';
		}

		const rule = find( config.rules, { value: filter.rule } ) || {};
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
					type={ input.type }
					autocompleter={ input.autocompleter }
					placeholder={ labels.placeholder }
					selected={ selected }
					inlineTags
					aria-label={ labels.filter }
				/>
			),
		} );

		const screenReaderText = this.getScreenReaderText( filter, config );

		return (
			<fieldset
				className="woocommerce-filters-advanced__line-item"
				tabIndex="0"
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

SearchFilter.propTypes = {
	/**
	 * The configuration object for the single filter to be rendered.
	 */
	config: PropTypes.shape( {
		labels: PropTypes.shape( {
			placeholder: PropTypes.string,
			rule: PropTypes.string,
			title: PropTypes.string,
		} ),
		rules: PropTypes.arrayOf( PropTypes.object ),
		input: PropTypes.object,
	} ).isRequired,
	/**
	 * The activeFilter handed down by AdvancedFilters.
	 */
	filter: PropTypes.shape( {
		key: PropTypes.string,
		rule: PropTypes.string,
		value: PropTypes.string,
	} ).isRequired,
	/**
	 * Function to be called on update.
	 */
	onFilterChange: PropTypes.func.isRequired,
	/**
	 * The query string represented in object form.
	 */
	query: PropTypes.object,
};

export default SearchFilter;
