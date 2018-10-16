/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Dropdown, IconButton } from '@wordpress/components';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { find, omit, partial, pick, last, get, includes } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import AnimationSlider from 'components/animation-slider';
import DropdownButton from 'components/dropdown-button';
import Search from 'components/search';
import { updateQueryString } from 'lib/nav-utils';
import './style.scss';

export const DEFAULT_FILTER = 'all';

/**
 * Modify a url query parameter via a dropdown selection of configurable options.
 * This component manipulates the `filter` query parameter.
 */
class FilterPicker extends Component {
	constructor( props ) {
		super( props );

		const selectedFilter = this.getFilter();
		this.state = {
			nav: selectedFilter.path || [],
			animate: null,
			selectedTag: null,
		};

		this.selectSubFilter = this.selectSubFilter.bind( this );
		this.getVisibleFilters = this.getVisibleFilters.bind( this );
		this.updateSelectedTag = this.updateSelectedTag.bind( this );
		this.onTagChange = this.onTagChange.bind( this );
		this.goBack = this.goBack.bind( this );

		if ( selectedFilter.settings && selectedFilter.settings.getLabels ) {
			const { query } = this.props;
			const { param, getLabels } = selectedFilter.settings;
			getLabels( query[ param ] ).then( this.updateSelectedTag );
		}
	}

	updateSelectedTag( tags ) {
		this.setState( { selectedTag: tags[ 0 ] } );
	}

	getAllFilters( filters ) {
		const allFilters = [];
		filters.forEach( f => {
			if ( ! f.subFilters ) {
				allFilters.push( f );
			} else {
				allFilters.push( omit( f, 'subFilters' ) );
				const subFilters = this.getAllFilters( f.subFilters );
				allFilters.push( ...subFilters );
			}
		} );
		return allFilters;
	}

	getFilter( value = false ) {
		const { filters, query } = this.props;
		const allFilters = this.getAllFilters( filters );
		value = value || query.filter || DEFAULT_FILTER;
		return find( allFilters, { value } ) || {};
	}

	getButtonLabel( selectedFilter ) {
		if ( 'Search' === selectedFilter.component ) {
			const { selectedTag } = this.state;
			return [ selectedTag && selectedTag.label, get( selectedFilter, 'settings.labels.button' ) ];
		}
		return selectedFilter ? [ selectedFilter.label ] : [];
	}

	getVisibleFilters( filters, nav ) {
		if ( nav.length === 0 ) {
			return filters;
		}
		const value = nav[ 0 ];
		const nextFilters = find( filters, { value } );
		return this.getVisibleFilters( nextFilters && nextFilters.subFilters, nav.slice( 1 ) );
	}

	selectSubFilter( value ) {
		// Add the value onto the nav path
		this.setState( prevState => ( { nav: [ ...prevState.nav, value ], animate: 'left' } ) );
	}

	goBack() {
		// Remove the last item from the nav path
		this.setState( prevState => ( { nav: prevState.nav.slice( 0, -1 ), animate: 'right' } ) );
	}

	update( value, additionalQueries = {} ) {
		const { path, query } = this.props;
		// Keep only time related queries when updating to a new filter
		const timeRelatedQueries = pick( query, [ 'period', 'compare', 'before', 'after' ] );
		const update = {
			filter: 'all' === value ? undefined : value,
			...additionalQueries,
		};
		updateQueryString( update, path, timeRelatedQueries );
	}

	onTagChange( filter, onClose, tags ) {
		const tag = last( tags );
		const { value, settings } = filter;
		const { param } = settings;
		if ( tag ) {
			this.update( value, { [ param ]: tag.id } );
			onClose();
		} else {
			this.update( 'all' );
		}
		this.updateSelectedTag( [ tag ] );
	}

	renderButton( filter, onClose ) {
		if ( filter.component ) {
			const { type, labels } = filter.settings;
			const { selectedTag } = this.state;
			return (
				<Search
					className="woocommerce-filters-filter__search"
					type={ type }
					placeholder={ labels.placeholder }
					selected={ selectedTag ? [ selectedTag ] : [] }
					onChange={ partial( this.onTagChange, filter, onClose ) }
					inlineTags
				/>
			);
		}

		const selectFilter = event => {
			onClose( event );
			this.update( filter.value );
			this.setState( { selectedTag: null } );
		};

		const selectSubFilter = partial( this.selectSubFilter, filter.value );

		return (
			<Button
				className="woocommerce-filters-filter__button"
				onClick={ filter.subFilters ? selectSubFilter : selectFilter }
			>
				{ filter.label }
			</Button>
		);
	}

	render() {
		const { filters } = this.props;
		const { nav, animate } = this.state;
		const visibleFilters = this.getVisibleFilters( filters, nav );
		const parentFilter = nav.length ? this.getFilter( nav[ nav.length - 1 ] ) : false;
		const selectedFilter = this.getFilter();
		return (
			<div className="woocommerce-filters-filter">
				<p>{ __( 'Show', 'wc-admin' ) }:</p>
				<Dropdown
					contentClassName="woocommerce-filters-filter__content"
					position="bottom"
					expandOnMobile
					headerTitle={ __( 'filter report to show:', 'wc-admin' ) }
					renderToggle={ ( { isOpen, onToggle } ) => (
						<DropdownButton
							onClick={ onToggle }
							isOpen={ isOpen }
							labels={ this.getButtonLabel( selectedFilter ) }
						/>
					) }
					renderContent={ ( { onClose } ) => (
						<AnimationSlider animationKey={ nav } animate={ animate } focusOnChange>
							{ () => (
								<ul className="woocommerce-filters-filter__content-list">
									{ parentFilter && (
										<li className="woocommerce-filters-filter__content-list-item">
											<IconButton
												className="woocommerce-filters-filter__button"
												onClick={ this.goBack }
												icon="arrow-left-alt2"
											>
												{ parentFilter.label }
											</IconButton>
										</li>
									) }
									{ visibleFilters.map( filter => (
										<li
											key={ filter.value }
											className={ classnames( 'woocommerce-filters-filter__content-list-item', {
												'is-selected':
													selectedFilter.value === filter.value ||
													( selectedFilter.path && includes( selectedFilter.path, filter.value ) ),
											} ) }
										>
											{ this.renderButton( filter, onClose ) }
										</li>
									) ) }
								</ul>
							) }
						</AnimationSlider>
					) }
				/>
			</div>
		);
	}
}

FilterPicker.propTypes = {
	/**
	 * An array of filters and subFilters to construct the menu.
	 */
	filters: PropTypes.arrayOf(
		PropTypes.shape( {
			/**
			 * A custom component used instead of a button, might have special handling for filtering. TBD, not yet implemented.
			 */
			component: PropTypes.string,
			/**
			 * The label for this filter. Optional only for custom component filters.
			 */
			label: PropTypes.string,
			/**
			 * An array representing the "path" to this filter, if nested.
			 */
			path: PropTypes.string,
			/**
			 * An array of more filter objects that act as "children" to this item.
			 * This set of filters is shown if the parent filter is clicked.
			 */
			subFilters: PropTypes.array,
			/**
			 * The value for this filter, used to set the `filter` query param when clicked, if there are no `subFilters`.
			 */
			value: PropTypes.string.isRequired,
		} )
	).isRequired,
	/**
	 * The `path` parameter supplied by React-Router.
	 */
	path: PropTypes.string.isRequired,
	/**
	 * The query string represented in object form.
	 */
	query: PropTypes.object,
};

FilterPicker.defaultProps = {
	query: {},
};

export default FilterPicker;
