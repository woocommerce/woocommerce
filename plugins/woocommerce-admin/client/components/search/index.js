/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { findIndex, noop } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Autocomplete from './autocomplete';
import { product } from './autocompleters';
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
		};

		this.selectResult = this.selectResult.bind( this );
		this.removeResult = this.removeResult.bind( this );
		this.updateSearch = this.updateSearch.bind( this );
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
			default:
				return {};
		}
	}

	render() {
		const autocompleter = this.getAutocompleter();
		const { selected } = this.props;
		const { value = '' } = this.state;
		return (
			<div className="woocommerce-search">
				<Autocomplete completer={ autocompleter } onSelect={ this.selectResult }>
					{ ( { listBoxId, activeId, onChange } ) => (
						<input
							type="search"
							value={ value }
							className="woocommerce-search__input"
							onChange={ this.updateSearch( onChange ) }
							aria-owns={ listBoxId }
							aria-activedescendant={ activeId }
						/>
					) }
				</Autocomplete>
				{ selected.length ? (
					<div className="woocommerce-search__token-list">
						{ selected.map( ( item, i ) => {
							const screenReaderLabel = sprintf(
								__( '%1$s (%2$s of %3$s)', 'wc-admin' ),
								item.label,
								i,
								selected.length
							);
							return (
								<Tag
									key={ item.id }
									id={ item.id }
									label={ item.label }
									remove={ this.removeResult }
									removeLabel={ __( 'Remove product', 'wc-admin' ) }
									screenReaderLabel={ screenReaderLabel }
								/>
							);
						} ) }
					</div>
				) : null }
			</div>
		);
	}
}

Search.propTypes = {
	/**
	 * Function called when selected results change, passed result list.
	 */
	onChange: PropTypes.func,
	/**
	 * The object type to be used in searching.
	 */
	type: PropTypes.oneOf( [ 'products', 'orders', 'customers' ] ).isRequired,
	/**
	 * An array of objects describing selected values
	 */
	selected: PropTypes.arrayOf(
		PropTypes.shape( {
			id: PropTypes.number.isRequired,
			label: PropTypes.string.isRequired,
		} )
	),
};

Search.defaultProps = {
	onChange: noop,
	selected: [],
};

export default Search;
