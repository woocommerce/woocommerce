/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export const DEFAULT_PER_PAGE_OPTIONS = [ 25, 50, 75, 100 ];

type PageSizePickerProps = {
	currentPage: number;
	perPage: number;
	total: number;
	setCurrentPage: (
		page: number,
		action?: 'previous' | 'next' | 'goto'
	) => void;
	setPerPageChange?: ( perPage: number ) => void;
	perPageOptions?: number[];
	label?: string;
};

export function PageSizePicker( {
	perPage,
	currentPage,
	total,
	setCurrentPage,
	setPerPageChange = () => {},
	perPageOptions = DEFAULT_PER_PAGE_OPTIONS,
	label = __( 'Rows per page', 'woocommerce' ),
}: PageSizePickerProps ) {
	function perPageChange( newPerPage: string ) {
		setPerPageChange( parseInt( newPerPage, 10 ) );
		const newMaxPage = Math.ceil( total / parseInt( newPerPage, 10 ) );
		if ( currentPage > newMaxPage ) {
			setCurrentPage( newMaxPage );
		}
	}

	// @todo Replace this with a styleized Select drop-down/control?
	const pickerOptions = perPageOptions.map( ( option ) => {
		return { value: option.toString(), label: option.toString() };
	} );

	return (
		<div className="woocommerce-pagination__per-page-picker">
			<SelectControl
				label={ label }
				labelPosition="side"
				value={ perPage.toString() }
				onChange={ perPageChange }
				options={ pickerOptions }
			/>
		</div>
	);
}
