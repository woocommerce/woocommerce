/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, createRef } from '@wordpress/element';
import { withInstanceId } from '@wordpress/compose';
import { findIndex, noop } from 'lodash';
import Gridicon from 'gridicons';
import PropTypes from 'prop-types';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Autocomplete from './autocomplete';
import { product, productCategory, coupons } from './autocompleters';
import Tag from 'components/tag';
import './style.scss';

/**
 * A search box which autocompletes results while typing, allowing for the user to select an existing object
 * (product, order, customer, etc). Currently only products are supported.
 */
class Search extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			value: '',
			isActive: false,
		};

		this.input = createRef();

		this.selectResult = this.selectResult.bind( this );
		this.removeResult = this.removeResult.bind( this );
		this.updateSearch = this.updateSearch.bind( this );
		this.onFocus = this.onFocus.bind( this );
		this.onBlur = this.onBlur.bind( this );
	}

	selectResult( value ) {
		const { selected, onChange } = this.props;
		// Check if this is already selected
		const isSelected = findIndex( selected, { id: value.id } );
		if ( -1 === isSelected ) {
			this.setState( { value: '' } );
			onChange( [ ...selected, value ] );
		}
	}

	removeResult( id ) {
		return () => {
			const { selected, onChange } = this.props;
			const i = findIndex( selected, { id } );
			onChange( [ ...selected.slice( 0, i ), ...selected.slice( i + 1 ) ] );
		};
	}

	updateSearch( onChange ) {
		return event => {
			const value = event.target.value || '';
			this.setState( { value } );
			onChange( event );
		};
	}

	getAutocompleter() {
		switch ( this.props.type ) {
			case 'products':
				return product;
			case 'product_cats':
				return productCategory;
			case 'coupons':
				return coupons;
			default:
				return {};
		}
	}

	renderTags() {
		const { selected } = this.props;
		return selected.length ? (
			<div className="woocommerce-search__token-list">
				{ selected.map( ( item, i ) => {
					const screenReaderLabel = sprintf(
						__( '%1$s (%2$s of %3$s)', 'wc-admin' ),
						item.label,
						i + 1,
						selected.length
					);
					return (
						<Tag
							key={ item.id }
							id={ item.id }
							label={ item.label }
							remove={ this.removeResult }
							screenReaderLabel={ screenReaderLabel }
						/>
					);
				} ) }
			</div>
		) : null;
	}

	onFocus() {
		this.setState( { isActive: true } );
	}

	onBlur() {
		this.setState( { isActive: false } );
	}

	render() {
		const autocompleter = this.getAutocompleter();
		const { placeholder, inlineTags, selected, instanceId, className } = this.props;
		const { value = '', isActive } = this.state;
		const aria = {
			'aria-labelledby': this.props[ 'aria-labelledby' ],
			'aria-label': this.props[ 'aria-label' ],
		};
		return (
			<div className={ classnames( 'woocommerce-search', className ) }>
				<Gridicon className="woocommerce-search__icon" icon="search" size={ 18 } />
				<Autocomplete completer={ autocompleter } onSelect={ this.selectResult }>
					{ ( { listBoxId, activeId, onChange } ) =>
						// Disable reason: The div below visually simulates an input field. Its
						// child input is the actual input and responds accordingly to all keyboard
						// events, but click events need to be passed onto the child input. There
						// is no appropriate aria role for describing this situation, which is only
						// for the benefit of sighted users.
						/* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
						inlineTags ? (
							<div
								className={ classnames( 'woocommerce-search__inline-container', {
									'is-active': isActive,
								} ) }
								onClick={ () => {
									this.input.current.focus();
								} }
							>
								{ this.renderTags() }
								<input
									ref={ this.input }
									type="text"
									size={
										( ( value.length === 0 && placeholder && placeholder.length ) ||
											value.length ) + 1
									}
									value={ value }
									placeholder={ ( selected.length === 0 && placeholder ) || '' }
									className="woocommerce-search__inline-input"
									onChange={ this.updateSearch( onChange ) }
									aria-owns={ listBoxId }
									aria-activedescendant={ activeId }
									onFocus={ this.onFocus }
									onBlur={ this.onBlur }
									aria-describedby={
										selected.length ? `search-inline-input-${ instanceId }` : null
									}
									{ ...aria }
								/>
								<span id={ `search-inline-input-${ instanceId }` } className="screen-reader-text">
									{ __( 'Move backward for selected items' ) }
								</span>
							</div>
						) : (
							<input
								type="search"
								value={ value }
								placeholder={ placeholder }
								className="woocommerce-search__input"
								onChange={ this.updateSearch( onChange ) }
								aria-owns={ listBoxId }
								aria-activedescendant={ activeId }
								{ ...aria }
							/>
						)
					}
				</Autocomplete>
				{ ! inlineTags && this.renderTags() }
			</div>
		);
		/* eslint-enable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
	}
}

Search.propTypes = {
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
	type: PropTypes.oneOf( [ 'products', 'product_cats', 'orders', 'customers', 'coupons' ] )
		.isRequired,
	/**
	 * A placeholder for the search input.
	 */
	placeholder: PropTypes.string,
	/**
	 * An array of objects describing selected values.
	 */
	selected: PropTypes.arrayOf(
		PropTypes.shape( {
			id: PropTypes.number.isRequired,
			label: PropTypes.string.isRequired,
		} )
	),
	/**
	 * Render tags inside input, otherwise render below input
	 */
	inlineTags: PropTypes.bool,
};

Search.defaultProps = {
	onChange: noop,
	selected: [],
	inlineTags: false,
};

export default withInstanceId( Search );
