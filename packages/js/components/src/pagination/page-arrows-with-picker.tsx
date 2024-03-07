/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement, useEffect, useState } from '@wordpress/element';
import { chevronLeft, chevronRight } from '@wordpress/icons';
import { sprintf, __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { uniqueId } from 'lodash';

type PageArrowsWithPickerProps = {
	currentPage: number;
	pageCount: number;
	setCurrentPage: (
		page: number,
		action?: 'previous' | 'next' | 'goto'
	) => void;
};

export function PageArrowsWithPicker( {
	pageCount,
	currentPage,
	setCurrentPage,
}: PageArrowsWithPickerProps ) {
	const [ inputValue, setInputValue ] = useState( currentPage );

	useEffect( () => {
		if ( currentPage !== inputValue ) {
			setInputValue( currentPage );
		}
	}, [ currentPage ] );

	function onInputChange( event: React.FormEvent< HTMLInputElement > ) {
		setInputValue( parseInt( event.currentTarget.value, 10 ) );
	}

	function onInputBlur( event: React.FocusEvent< HTMLInputElement > ) {
		const newPage = parseInt( event.target.value, 10 );

		if (
			newPage !== currentPage &&
			Number.isFinite( newPage ) &&
			newPage > 0 &&
			pageCount &&
			pageCount >= newPage
		) {
			setCurrentPage( newPage, 'goto' );
		} else {
			setInputValue( currentPage );
		}
	}

	function previousPage( event: React.MouseEvent ) {
		event.stopPropagation();
		if ( currentPage - 1 < 1 ) {
			return;
		}
		setInputValue( currentPage - 1 );
		setCurrentPage( currentPage - 1, 'previous' );
	}

	function nextPage( event: React.MouseEvent ) {
		event.stopPropagation();
		if ( currentPage + 1 > pageCount ) {
			return;
		}
		setInputValue( currentPage + 1 );
		setCurrentPage( currentPage + 1, 'next' );
	}

	if ( pageCount <= 1 ) {
		return null;
	}

	const previousLinkClass = classNames( 'woocommerce-pagination__link', {
		'is-active': currentPage > 1,
	} );

	const nextLinkClass = classNames( 'woocommerce-pagination__link', {
		'is-active': currentPage < pageCount,
	} );
	const isError = currentPage < 1 || currentPage > pageCount;
	const inputClass = classNames(
		'woocommerce-pagination__page-arrow-picker-input',
		{
			'has-error': isError,
		}
	);

	const instanceId = uniqueId( 'woocommerce-pagination-page-picker-' );
	return (
		<div className="woocommerce-pagination__page-arrows">
			<Button
				className={ previousLinkClass }
				icon={ chevronLeft }
				disabled={ ! ( currentPage > 1 ) }
				onClick={ previousPage }
				label={ __( 'Previous Page', 'woocommerce' ) }
			/>
			<input
				id={ instanceId }
				className={ inputClass }
				aria-invalid={ isError }
				type="number"
				onChange={ onInputChange }
				onBlur={ onInputBlur }
				value={ inputValue }
				min={ 1 }
				max={ pageCount }
			/>
			{ sprintf(
				/* translators: %d: total number of pages */
				__( 'of %d', 'woocommerce' ),
				pageCount
			) }
			<Button
				className={ nextLinkClass }
				icon={ chevronRight }
				disabled={ ! ( currentPage < pageCount ) }
				onClick={ nextPage }
				label={ __( 'Next Page', 'woocommerce' ) }
			/>
		</div>
	);
}
