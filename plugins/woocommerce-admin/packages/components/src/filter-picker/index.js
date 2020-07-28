/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Dropdown } from '@wordpress/components';
import { focus } from '@wordpress/dom';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import { find, partial, last, get, includes } from 'lodash';
import PropTypes from 'prop-types';
import { Icon, chevronLeft } from '@wordpress/icons';
import {
	flattenFilters,
	getPersistedQuery,
	updateQueryString,
} from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import AnimationSlider from '../animation-slider';
import DropdownButton from '../dropdown-button';
import Search from '../search';

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
		this.onContentMount = this.onContentMount.bind( this );
		this.goBack = this.goBack.bind( this );

		if ( selectedFilter.settings && selectedFilter.settings.getLabels ) {
			const { query } = this.props;
			const { param: filterParam, getLabels } = selectedFilter.settings;
			getLabels( query[ filterParam ], query ).then(
				this.updateSelectedTag
			);
		}
	}

	componentDidUpdate( { query: prevQuery } ) {
		const { query: nextQuery, config } = this.props;
		if ( prevQuery[ config.param ] !== nextQuery[ [ config.param ] ] ) {
			const selectedFilter = this.getFilter();
			if ( selectedFilter && selectedFilter.component === 'Search' ) {
				/* eslint-disable react/no-did-update-set-state */
				this.setState( { nav: selectedFilter.path || [] } );
				/* eslint-enable react/no-did-update-set-state */
				const {
					param: filterParam,
					getLabels,
				} = selectedFilter.settings;
				getLabels( nextQuery[ filterParam ], nextQuery ).then(
					this.updateSelectedTag
				);
			}
		}
	}

	updateSelectedTag( tags ) {
		this.setState( { selectedTag: tags[ 0 ] } );
	}

	getFilter( value ) {
		const { config, query } = this.props;
		const allFilters = flattenFilters( config.filters );
		value =
			value ||
			query[ config.param ] ||
			config.defaultValue ||
			DEFAULT_FILTER;
		return find( allFilters, { value } ) || {};
	}

	getButtonLabel( selectedFilter ) {
		if ( selectedFilter.component === 'Search' ) {
			const { selectedTag } = this.state;
			return [
				selectedTag && selectedTag.label,
				get( selectedFilter, 'settings.labels.button' ),
			];
		}
		return selectedFilter ? [ selectedFilter.label ] : [];
	}

	getVisibleFilters( filters, nav ) {
		if ( nav.length === 0 ) {
			return filters;
		}
		const value = nav[ 0 ];
		const nextFilters = find( filters, { value } );
		return this.getVisibleFilters(
			nextFilters && nextFilters.subFilters,
			nav.slice( 1 )
		);
	}

	selectSubFilter( value ) {
		// Add the value onto the nav path
		this.setState( ( prevState ) => ( {
			nav: [ ...prevState.nav, value ],
			animate: 'left',
		} ) );
	}

	goBack() {
		// Remove the last item from the nav path
		this.setState( ( prevState ) => ( {
			nav: prevState.nav.slice( 0, -1 ),
			animate: 'right',
		} ) );
	}

	update( value, additionalQueries = {} ) {
		const { path, query, config, onFilterSelect } = this.props;
		// Keep only time related queries when updating to a new filter
		const persistedQuery = getPersistedQuery( query );
		const update = {
			[ config.param ]:
				( config.defaultValue || DEFAULT_FILTER ) === value
					? undefined
					: value,
			...additionalQueries,
		};
		// Keep any url parameters as designated by the config
		config.staticParams.forEach( ( param ) => {
			update[ param ] = query[ param ];
		} );
		updateQueryString( update, path, persistedQuery );
		onFilterSelect( update );
	}

	onTagChange( filter, onClose, config, tags ) {
		const tag = last( tags );
		const { value, settings } = filter;
		const { param: filterParam } = settings;
		if ( tag ) {
			this.update( value, { [ filterParam ]: tag.key } );
			onClose();
		} else {
			this.update( config.defaultValue || DEFAULT_FILTER );
		}
		this.updateSelectedTag( [ tag ] );
	}

	renderButton( filter, onClose, config ) {
		if ( filter.component ) {
			const { type, labels } = filter.settings;
			const persistedFilter = this.getFilter();
			const selectedTag =
				persistedFilter.value === filter.value
					? this.state.selectedTag
					: null;

			return (
				<Search
					className="woocommerce-filters-filter__search"
					type={ type }
					placeholder={ labels.placeholder }
					selected={ selectedTag ? [ selectedTag ] : [] }
					onChange={ partial(
						this.onTagChange,
						filter,
						onClose,
						config
					) }
					inlineTags
					staticResults
				/>
			);
		}

		const selectFilter = ( event ) => {
			onClose( event );
			this.update( filter.value, filter.query || {} );
			this.setState( { selectedTag: null } );
		};

		const selectSubFilter = partial( this.selectSubFilter, filter.value );
		const selectedFilter = this.getFilter();
		const buttonIsSelected =
			selectedFilter.value === filter.value ||
			( selectedFilter.path &&
				includes( selectedFilter.path, filter.value ) );
		const onClick = ( event ) => {
			if ( buttonIsSelected ) {
				// Don't navigate if the button is already selected.
				onClose( event );
				return;
			}

			if ( filter.subFilters ) {
				selectSubFilter( event );
				return;
			}

			selectFilter( event );
		};

		return (
			<Button
				className="woocommerce-filters-filter__button"
				onClick={ onClick }
			>
				{ filter.label }
			</Button>
		);
	}

	onContentMount( content ) {
		const { nav } = this.state;
		const parentFilter = nav.length
			? this.getFilter( nav[ nav.length - 1 ] )
			: false;
		const focusableIndex = parentFilter ? 1 : 0;
		const focusable = focus.tabbable.find( content )[ focusableIndex ];
		setTimeout( () => {
			focusable.focus();
		}, 0 );
	}

	render() {
		const { config } = this.props;
		const { nav, animate } = this.state;
		const visibleFilters = this.getVisibleFilters( config.filters, nav );
		const parentFilter = nav.length
			? this.getFilter( nav[ nav.length - 1 ] )
			: false;
		const selectedFilter = this.getFilter();
		return (
			<div className="woocommerce-filters-filter">
				{ config.label && (
					<span className="woocommerce-filters-label">
						{ config.label }:
					</span>
				) }
				<Dropdown
					contentClassName="woocommerce-filters-filter__content"
					position="bottom"
					expandOnMobile
					headerTitle={ __(
						'filter report to show:',
						'woocommerce-admin'
					) }
					renderToggle={ ( { isOpen, onToggle } ) => (
						<DropdownButton
							onClick={ onToggle }
							isOpen={ isOpen }
							labels={ this.getButtonLabel( selectedFilter ) }
						/>
					) }
					renderContent={ ( { onClose } ) => (
						<AnimationSlider
							animationKey={ nav }
							animate={ animate }
							onExited={ this.onContentMount }
						>
							{ () => (
								<ul className="woocommerce-filters-filter__content-list">
									{ parentFilter && (
										<li className="woocommerce-filters-filter__content-list-item">
											<Button
												className="woocommerce-filters-filter__button"
												onClick={ this.goBack }
											>
												<Icon icon={ chevronLeft } />
												{ parentFilter.label }
											</Button>
										</li>
									) }
									{ visibleFilters.map( ( filter ) => (
										<li
											key={ filter.value }
											className={ classnames(
												'woocommerce-filters-filter__content-list-item',
												{
													'is-selected':
														selectedFilter.value ===
															filter.value ||
														( selectedFilter.path &&
															includes(
																selectedFilter.path,
																filter.value
															) ),
												}
											) }
										>
											{ this.renderButton(
												filter,
												onClose,
												config
											) }
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
	config: PropTypes.shape( {
		/**
		 * A label above the filter selector.
		 */
		label: PropTypes.string,
		/**
		 * Url parameters to persist when selecting a new filter.
		 */
		staticParams: PropTypes.array.isRequired,
		/**
		 * The url paramter this filter will modify.
		 */
		param: PropTypes.string.isRequired,
		/**
		 * The default paramter value to use instead of 'all'.
		 */
		defaultValue: PropTypes.string,
		/**
		 * Determine if the filter should be shown. Supply a function with the query object as an argument returning a boolean.
		 */
		showFilters: PropTypes.func.isRequired,
		/**
		 * An array of filter a user can select.
		 */
		filters: PropTypes.arrayOf(
			PropTypes.shape( {
				/**
				 * The chart display mode to use for charts displayed when this filter is active.
				 */
				chartMode: PropTypes.oneOf( [
					'item-comparison',
					'time-comparison',
				] ),
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
		),
	} ).isRequired,
	/**
	 * The `path` parameter supplied by React-Router.
	 */
	path: PropTypes.string.isRequired,
	/**
	 * The query string represented in object form.
	 */
	query: PropTypes.object,
	/**
	 * Function to be called after filter selection.
	 */
	onFilterSelect: PropTypes.func,
};

FilterPicker.defaultProps = {
	query: {},
	onFilterSelect: () => {},
};

export default FilterPicker;
