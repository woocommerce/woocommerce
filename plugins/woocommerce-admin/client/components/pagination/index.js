/** @format */

/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { IconButton, SelectControl } from '@wordpress/components';
import classNames from 'classnames';
import PropTypes from 'prop-types';
import { noop, uniqueId } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';

function keyListener( methodToCall, event ) {
	switch ( event.key ) {
		case 'Enter':
			this[ methodToCall ]( event );
			break;
	}
}

class Pagination extends Component {
	constructor( props ) {
		super( props );

		this.previousPage = this.previousPage.bind( this );
		this.nextPage = this.nextPage.bind( this );
		this.selectPageListener = keyListener.bind( this, 'selectPage' );
		this.onPageValueChange = this.onPageValueChange.bind( this );
		this.perPageChange = this.perPageChange.bind( this );
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

	onPageValueChange( event ) {
		const { onPageChange } = this.props;
		onPageChange( parseInt( event.target.value ) );
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
					{ sprintf( __( 'Page %d of %d', 'wc-admin' ), page, this.pageCount ) }
				</span>
				<div className="woocommerce-pagination__page-arrows-buttons">
					<IconButton
						className={ previousLinkClass }
						disabled={ ! ( page > 1 ) }
						onClick={ this.previousPage }
						icon="arrow-left-alt2"
						label={ __( 'Previous Page', 'wc-admin' ) }
						size={ 18 }
					/>
					<IconButton
						className={ nextLinkClass }
						disabled={ ! ( page < this.pageCount ) }
						onClick={ this.nextPage }
						icon="arrow-right-alt2"
						label={ __( 'Next Page', 'wc-admin' ) }
						size={ 18 }
					/>
				</div>
			</div>
		);
	}

	renderPagePicker() {
		const { page } = this.props;
		const isError = page < 1 || page > this.pageCount;
		const inputClass = classNames( 'woocommerce-pagination__page-picker-input', {
			'has-error': isError,
		} );

		const instanceId = uniqueId( 'woocommerce-pagination-page-picker-' );
		return (
			<div className="woocommerce-pagination__page-picker">
				<label htmlFor={ instanceId } className="woocommerce-pagination__page-picker-label">
					{ __( 'Go to page', 'wc-admin' ) }
					<input
						id={ instanceId }
						className={ inputClass }
						aria-invalid={ isError }
						type="number"
						onChange={ this.onPageValueChange }
						onKeyDown={ this.selectPageListener }
						value={ page }
						min={ 1 }
						max={ this.pageCount }
					/>
				</label>
			</div>
		);
	}

	renderPerPagePicker() {
		// TODO Replace this with a styleized Select drop-down/control?
		return (
			<div className="woocommerce-pagination__per-page-picker">
				<SelectControl
					label={ __( 'Rows per page', 'wc-admin' ) }
					value={ this.props.perPage }
					onChange={ this.perPageChange }
					options={ [
						{ value: '25', label: '25' },
						{ value: '50', label: '50' },
						{ value: '75', label: '75' },
						{ value: '100', label: '100' },
						{ value: '250', label: '250' },
						{ value: '500', label: '500' },
					] }
				/>
			</div>
		);
	}

	render() {
		const { total, perPage, className } = this.props;
		this.pageCount = Math.ceil( total / perPage );

		if ( this.pageCount <= 1 ) {
			return null;
		}

		const classes = classNames( 'woocommerce-pagination', className );

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
	page: PropTypes.number.isRequired,
	onPageChange: PropTypes.func,
	perPage: PropTypes.number.isRequired,
	onPerPageChange: PropTypes.func,
	total: PropTypes.number.isRequired,
	className: PropTypes.string,
};

Pagination.defaultProps = {
	onPageChange: noop,
	onPerPageChange: noop,
};

export default Pagination;
