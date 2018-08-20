/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { Dropdown, Button, Dashicon } from '@wordpress/components';
import { find, partial } from 'lodash';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import AnimationSlider from 'components/animation-slider';
import DropdownButton from 'components/dropdown-button';
import { getQuery, updateQueryString } from 'lib/nav-utils';
import './style.scss';

export const DEFAULT_FILTER = 'all';

class FilterPicker extends Component {
	constructor( props ) {
		super( props );

		const { filterPaths } = props;
		this.state = {
			nav: filterPaths[ this.getFilterValue() ],
			animate: null,
		};

		this.getSelectedFilter = this.getSelectedFilter.bind( this );
		this.selectSubFilters = this.selectSubFilters.bind( this );
		this.getVisibleFilters = this.getVisibleFilters.bind( this );
		this.goBack = this.goBack.bind( this );
	}

	getFilterValue() {
		const query = getQuery();
		return query.filter || DEFAULT_FILTER;
	}

	getSelectedFilter() {
		const { filters, filterPaths } = this.props;
		const value = this.getFilterValue();
		const filterPath = filterPaths[ value ];
		const visibleFilters = this.getVisibleFilters( filters, [ ...filterPath ] );
		return find( visibleFilters, filter => filter.value === value );
	}

	getLabels( selectedFilter ) {
		// @TODO: handle single product secondary labels
		return selectedFilter ? [ selectedFilter.label ] : [];
	}

	selectSubFilters( value ) {
		const nav = [ ...this.state.nav ];
		nav.push( value );
		this.setState( { nav, animate: 'left' } );
	}

	getVisibleFilters( filters, nav ) {
		if ( nav.length === 0 ) {
			return filters;
		}
		const value = nav.shift();
		const nextFilters = find( filters, filter => value === filter.value );
		return this.getVisibleFilters( nextFilters && nextFilters.subFilters, nav );
	}

	goBack() {
		const nav = [ ...this.state.nav ];
		nav.pop();
		this.setState( { nav, animate: 'right' } );
	}

	renderButton( filter, onClose ) {
		if ( filter.subFilters ) {
			return (
				<Button
					className="woocommerce-filters-filter__button"
					onClick={ partial( this.selectSubFilters, filter.value ) }
				>
					{ filter.label }
				</Button>
			);
		}

		if ( filter.component ) {
			return (
				<Fragment>
					<Button
						className="woocommerce-filters-filter__button has-parent-nav"
						onClick={ this.goBack }
					>
						<Dashicon icon="arrow-left-alt2" />
						{ filter.label }
					</Button>
					<input
						type="text"
						style={ { width: '100%', margin: '0' } }
						placeholder="Search Placeholder"
					/>
				</Fragment>
			);
		}

		const onClick = event => {
			onClose( event );
			updateQueryString( { filter: filter.value } );
		};

		return (
			<Button className="woocommerce-filters-filter__button" onClick={ onClick }>
				{ filter.label }
			</Button>
		);
	}

	render() {
		const { filters } = this.props;
		const { nav, animate } = this.state;
		const visibleFilters = this.getVisibleFilters( filters, [ ...nav ] );
		const selectedFilter = this.getSelectedFilter();
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
							labels={ this.getLabels( selectedFilter ) }
						/>
					) }
					renderContent={ ( { onClose } ) => (
						<AnimationSlider animationKey={ nav } animate={ animate } focusOnChange>
							{ () => (
								<ul className="woocommerce-filters-filter__content-list">
									{ visibleFilters.map( filter => (
										<li
											key={ filter.value }
											className={ classnames( 'woocommerce-filters-filter__content-list-item', {
												'is-selected': selectedFilter.value === filter.value,
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
	filters: PropTypes.array.isRequired,
	filterPaths: PropTypes.object.isRequired,
};

export default FilterPicker;
