/** @format */

/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { IconButton, SelectControl } from '@wordpress/components';
import classNames from 'classnames';
import PropTypes from 'prop-types';
import { isFinite, noop, uniqueId } from 'lodash';

const PER_PAGE_OPTIONS = [ 25, 50, 75, 100 ];

/**
 * Use `Pagination` to allow navigation between pages that represent a collection of items.
 * The component allows for selecting a new page and items per page options.
 */
class Pagination extends Component {
	constructor( props ) {
		super( props );

		this.state = {
				inputValue: this.props.page,
		};

		this.previousPage = this.previousPage.bind( this );
		this.nextPage = this.nextPage.bind( this );
		this.onInputChange = this.onInputChange.bind( this );
		this.onInputBlur = this.onInputBlur.bind( this );
		this.perPageChange = this.perPageChange.bind( this );
		this.selectInputValue = this.selectInputValue.bind( this );
	}

	previousPage( event ) {
		event.stopPropagation();
		const { page, onPageChange } = this.props;
		if ( page - 1 < 1 ) {
			return;
		}
		onPageChange( page - 1 );
	}

	nextPage( event ) {
		event.stopPropagation();
		const { page, onPageChange } = this.props;
		if ( page + 1 > this.pageCount ) {
			return;
		}
		onPageChange( page + 1 );
	}

	perPageChange( perPage ) {
		const { onPerPageChange, onPageChange, total, page } = this.props;

		onPerPageChange( parseInt( perPage ) );
		const newMaxPage = Math.ceil( total / parseInt( perPage ) );
		if ( page > newMaxPage ) {
			onPageChange( newMaxPage );
		}
	}

	onInputChange( event ) {
		this.setState( {
				inputValue: event.target.value,
		} );
	}

	onInputBlur( event ) {
		const { onPageChange } = this.props;
		const newPage = parseInt( event.target.value, 10 );

		if ( isFinite( newPage ) && newPage > 0 && this.pageCount && this.pageCount >= newPage ) {
			onPageChange( newPage );
		}
	}

	selectInputValue( event ) {
		event.target.select();
	}

	renderPageArrows() {
		const { page } = this.props;

		if ( this.pageCount <= 1 ) {
			return null;
		}

		const previousLinkClass = classNames( 'woocommerce-pagination__link', {
			'is-active': page > 1,
		} );

		const nextLinkClass = classNames( 'woocommerce-pagination__link', {
			'is-active': page < this.pageCount,
		} );

		return (
			<div className="woocommerce-pagination__page-arrows">
				<span
					className="woocommerce-pagination__page-arrows-label"
					role="status"
					aria-live="polite"
				>
					{ sprintf( __( 'Page %d of %d', 'woocommerce-admin' ), page, this.pageCount ) }
				</span>
				<div className="woocommerce-pagination__page-arrows-buttons">
					<IconButton
						className={ previousLinkClass }
						disabled={ ! ( page > 1 ) }
						onClick={ this.previousPage }
						icon="arrow-left-alt2"
						label={ __( 'Previous Page', 'woocommerce-admin' ) }
						size={ 18 }
					/>
					<IconButton
						className={ nextLinkClass }
						disabled={ ! ( page < this.pageCount ) }
						onClick={ this.nextPage }
						icon="arrow-right-alt2"
						label={ __( 'Next Page', 'woocommerce-admin' ) }
						size={ 18 }
					/>
				</div>
			</div>
		);
	}

	renderPagePicker() {
		const { page } = this.props;
		const { inputValue } = this.state;
		const isError = page < 1 || page > this.pageCount;
		const inputClass = classNames( 'woocommerce-pagination__page-picker-input', {
			'has-error': isError,
		} );

		const instanceId = uniqueId( 'woocommerce-pagination-page-picker-' );
		return (
			<div className="woocommerce-pagination__page-picker">
				<label htmlFor={ instanceId } className="woocommerce-pagination__page-picker-label">
					{ __( 'Go to page', 'woocommerce-admin' ) }
					<input
						id={ instanceId }
						className={ inputClass }
						aria-invalid={ isError }
						type="number"
						onClick={ this.selectInputValue }
						onChange={ this.onInputChange }
						onBlur={ this.onInputBlur }
						value={ inputValue }
						min={ 1 }
						max={ this.pageCount }
					/>
				</label>
			</div>
		);
	}

	renderPerPagePicker() {
		// @todo Replace this with a styleized Select drop-down/control?
		const pickerOptions = PER_PAGE_OPTIONS.map( option => {
			return { value: option, label: option };
		} );

		return (
			<div className="woocommerce-pagination__per-page-picker">
				<SelectControl
					label={ __( 'Rows per page', 'woocommerce-admin' ) }
					value={ this.props.perPage }
					onChange={ this.perPageChange }
					options={ pickerOptions }
				/>
			</div>
		);
	}

	render() {
		const { total, perPage, className } = this.props;
		this.pageCount = Math.ceil( total / perPage );

		const classes = classNames( 'woocommerce-pagination', className );

		if ( this.pageCount <= 1 ) {
			return (
				( total > PER_PAGE_OPTIONS[ 0 ] && (
					<div className={ classes }>{ this.renderPerPagePicker() }</div>
				) ) ||
				null
			);
		}

		return (
			<div className={ classes }>
				{ this.renderPageArrows() }
				{ this.renderPagePicker() }
				{ this.renderPerPagePicker() }
			</div>
		);
	}
}

Pagination.propTypes = {
	/**
	 * The current page of the collection.
	 */
	page: PropTypes.number.isRequired,
	/**
	 * A function to execute when the page is changed.
	 */
	onPageChange: PropTypes.func,
	/**
	 * The amount of results that are being displayed per page.
	 */
	perPage: PropTypes.number.isRequired,
	/**
	 * A function to execute when the per page option is changed.
	 */
	onPerPageChange: PropTypes.func,
	/**
	 * The total number of results.
	 */
	total: PropTypes.number.isRequired,
	/**
	 * Additional classNames.
	 */
	className: PropTypes.string,
};

Pagination.defaultProps = {
	onPageChange: noop,
	onPerPageChange: noop,
};

export default Pagination;
