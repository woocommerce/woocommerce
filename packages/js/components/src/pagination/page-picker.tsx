/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { uniqueId } from 'lodash';

type PagePickerProps = {
	page: number;
	pageCount: number;
	onPageChange: (
		page: number,
		action?: 'previous' | 'next' | 'goto'
	) => void;
};

export function PagePicker( {
	pageCount,
	page,
	onPageChange,
}: PagePickerProps ) {
	const [ inputValue, setInputValue ] = useState( page );

	function onInputChange( event: React.FormEvent< HTMLInputElement > ) {
		setInputValue( parseInt( event.currentTarget.value, 10 ) );
	}

	function onInputBlur( event: React.FocusEvent< HTMLInputElement > ) {
		const newPage = parseInt( event.target.value, 10 );

		if (
			newPage !== page &&
			Number.isFinite( newPage ) &&
			newPage > 0 &&
			pageCount &&
			pageCount >= newPage
		) {
			onPageChange( newPage, 'goto' );
		}
	}

	function selectInputValue( event: React.MouseEvent< HTMLInputElement > ) {
		event.currentTarget.select();
	}

	const isError = page < 1 || page > pageCount;
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
					onClick={ selectInputValue }
					onChange={ onInputChange }
					onBlur={ onInputBlur }
					value={ inputValue }
					min={ 1 }
					max={ pageCount }
				/>
			</label>
		</div>
	);
}
