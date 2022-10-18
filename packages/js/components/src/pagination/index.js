/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createElement, Component } from '@wordpress/element';
import { Button, SelectControl } from '@wordpress/components';
import classNames from 'classnames';
import PropTypes from 'prop-types';
import { noop, uniqueId } from 'lodash';
import { Icon, chevronLeft, chevronRight } from '@wordpress/icons';

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
		onPageChange( page - 1, 'previous' );
	}

	nextPage( event ) {
		event.stopPropagation();
		const { page, onPageChange } = this.props;
		if ( page + 1 > this.pageCount ) {
			return;
		}
		onPageChange( page + 1, 'next' );
	}

	perPageChange( perPage ) {
		const { onPerPageChange, onPageChange, total, page } = this.props;

		onPerPageChange( parseInt( perPage, 10 ) );
		const newMaxPage = Math.ceil( total / parseInt( perPage, 10 ) );
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
		const { onPageChange, page } = this.props;
		const newPage = parseInt( event.target.value, 10 );

		if (
			newPage !== page &&
			Number.isFinite( newPage ) &&
			newPage > 0 &&
			this.pageCount &&
			this.pageCount >= newPage
		) {
			onPageChange( newPage, 'goto' );
		}
	}

	selectInputValue( event ) {
		event.target.select();
	}

	renderPageArrows() {
		const { page, showPageArrowsLabel } = this.props;

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
				{ showPageArrowsLabel && (
					<span
						className="woocommerce-pagination__page-arrows-label"
						role="status"
						aria-live="polite"
					>
						{ sprintf(
							__( 'Page %d of %d', 'woocommerce' ),
							page,
							this.pageCount
						) }
					</span>
				) }
				<div className="woocommerce-pagination__page-arrows-buttons">
					<Button
						className={ previousLinkClass }
						disabled={ ! ( page > 1 ) }
						onClick={ this.previousPage }
						label={ __( 'Previous Page', 'woocommerce' ) }
					>
						<Icon icon={ chevronLeft } />
					</Button>
					<Button
						className={ nextLinkClass }
						disabled={ ! ( page < this.pageCount ) }
						onClick={ this.nextPage }
						label={ __( 'Next Page', 'woocommerce' ) }
					>
						<Icon icon={ chevronRight } />
					</Button>
				</div>
			</div>
		);
	}

	renderPagePicker() {
		const { page } = this.props;
		const { inputValue } = this.state;
		const isError = page < 1 || page > this.pageCount;
		const inputClass = classNames(
			'woocommerce-pagination__page-picker-input',
			{
				'has-error': isError,
			}
		);

		const instanceId = uniqueId( 'woocommerce-pagination-page-picker-' );
		return (
			<div className="woocommerce-pagination__page-picker">
				<label
					htmlFor={ instanceId }
					className="woocommerce-pagination__page-picker-label"
				>
					{ __( 'Go to page', 'woocommerce' ) }
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
		const pickerOptions = PER_PAGE_OPTIONS.map( ( option ) => {
			return { value: option, label: option };
		} );

		return (
			<div className="woocommerce-pagination__per-page-picker">
				<SelectControl
					label={ __( 'Rows per page', 'woocommerce' ) }
					labelPosition="side"
					value={ this.props.perPage }
					onChange={ this.perPageChange }
					options={ pickerOptions }
				/>
			</div>
		);
	}

	render() {
		const { total, perPage, className, showPagePicker, showPerPagePicker } =
			this.props;
		this.pageCount = Math.ceil( total / perPage );

		const classes = classNames( 'woocommerce-pagination', className );

		if ( this.pageCount <= 1 ) {
			return (
				( total > PER_PAGE_OPTIONS[ 0 ] && (
					<div className={ classes }>
						{ this.renderPerPagePicker() }
					</div>
				) ) ||
				null
			);
		}

		return (
			<div className={ classes }>
				{ this.renderPageArrows() }
				{ showPagePicker && this.renderPagePicker() }
				{ showPerPagePicker && this.renderPerPagePicker() }
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
	/**
	 * Whether the page picker should be rendered.
	 */
	showPagePicker: PropTypes.bool,
	/**
	 * Whether the perPage picker should be rendered.
	 */
	showPerPagePicker: PropTypes.bool,
	/**
	 * Whether the page arrows label should be rendered.
	 */
	showPageArrowsLabel: PropTypes.bool,
};

Pagination.defaultProps = {
	onPageChange: noop,
	onPerPageChange: noop,
	showPagePicker: true,
	showPerPagePicker: true,
	showPageArrowsLabel: true,
};

export default Pagination;
