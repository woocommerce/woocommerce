/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment, createRef } from '@wordpress/element';
import { Dropdown, Button, Dashicon } from '@wordpress/components';
import { stringify as stringifyQueryObject } from 'qs';
import { omit, find, partial } from 'lodash';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import DropdownButton from 'components/dropdown-button';
import Link from 'components/link';
import './style.scss';

class FilterPicker extends Component {
	constructor( props ) {
		super( props );

		const { filterPaths, getQueryParamValue } = props;
		this.state = {
			nav: filterPaths[ getQueryParamValue() ],
		};
		this.listRef = createRef();

		this.getSelectionPath = this.getSelectionPath.bind( this );
		this.getOtherQueries = this.getOtherQueries.bind( this );
		this.getSelectedFilter = this.getSelectedFilter.bind( this );
		this.selectSubFilters = this.selectSubFilters.bind( this );
		this.getVisibleFilters = this.getVisibleFilters.bind( this );
		this.goBack = this.goBack.bind( this );
	}

	getOtherQueries( query ) {
		const { queryParam } = this.props;
		return omit( query, queryParam );
	}

	getSelectionPath( filter ) {
		const { path, query, queryParam } = this.props;
		const otherQueries = this.getOtherQueries( query );
		const data = {
			[ queryParam ]: filter.value,
		};
		const queryString = stringifyQueryObject( Object.assign( otherQueries, data ) );
		return `${ path }?${ queryString }`;
	}

	getSelectedFilter() {
		const { filters, getQueryParamValue, filterPaths } = this.props;
		const value = getQueryParamValue();
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
		this.setState( { nav } );
		this.focusFirstFilter();
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
		this.setState( { nav } );
		this.focusFirstFilter();
	}

	focusFirstFilter() {
		setTimeout( () => {
			const list = this.listRef.current;
			if ( list.children.length && list.children[ 0 ].children.length ) {
				list.children[ 0 ].children[ 0 ].focus();
			}
		}, 0 );
	}

	renderButton( filter, onClose ) {
		if ( filter.subFilters ) {
			return (
				<Button
					className="woocommerce-filter-picker__content-list-item-btn"
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
						className="woocommerce-filter-picker__content-list-item-btn has-parent-nav"
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

		return (
			<Link
				className="woocommerce-filter-picker__content-list-item-btn components-button"
				to={ this.getSelectionPath( filter ) }
				onClick={ onClose }
			>
				{ filter.label }
			</Link>
		);
	}

	render() {
		const { filters } = this.props;
		const visibleFilters = this.getVisibleFilters( filters, [ ...this.state.nav ] );
		const selectedFilter = this.getSelectedFilter();
		return (
			<div className="woocommerce-filter-picker">
				<p>{ __( 'Show', 'wc-admin' ) }:</p>
				<Dropdown
					contentClassName="woocommerce-filter-picker__content"
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
						<ul className="woocommerce-filter-picker__content-list" ref={ this.listRef }>
							{ visibleFilters.map( filter => (
								<li
									className={ classnames( 'woocommerce-filter-picker__content-list-item', {
										'is-selected': selectedFilter.value === filter.value,
									} ) }
								>
									{ this.renderButton( filter, onClose ) }
								</li>
							) ) }
						</ul>
					) }
				/>
			</div>
		);
	}
}

FilterPicker.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
	filters: PropTypes.array.isRequired,
	filterPaths: PropTypes.object.isRequired,
	queryParam: PropTypes.string.isRequired,
	getQueryParamValue: PropTypes.func.isRequired,
};

export default FilterPicker;
