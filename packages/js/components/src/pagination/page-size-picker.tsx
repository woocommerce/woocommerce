/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export const DEFAULT_PER_PAGE_OPTIONS = [ 25, 50, 75, 100 ];

type PageSizePickerProps = {
	page: number;
	perPage: number;
	total: number;
	onPageChange: (
		page: number,
		action?: 'previous' | 'next' | 'goto'
	) => void;
	onPerPageChange?: ( perPage: number ) => void;
	perPageOptions?: number[];
};

export function PageSizePicker( {
	perPage,
	page,
	total,
	onPageChange,
	onPerPageChange = () => {},
	perPageOptions = DEFAULT_PER_PAGE_OPTIONS,
}: PageSizePickerProps ) {
	function perPageChange( newPerPage: string ) {
		onPerPageChange( parseInt( newPerPage, 10 ) );
		const newMaxPage = Math.ceil( total / parseInt( newPerPage, 10 ) );
		if ( page > newMaxPage ) {
			onPageChange( newMaxPage );
		}
	}

	// @todo Replace this with a styleized Select drop-down/control?
	const pickerOptions = perPageOptions.map( ( option ) => {
		return { value: option.toString(), label: option.toString() };
	} );

	return (
		<div className="woocommerce-pagination__per-page-picker">
			<SelectControl
				label={ __( 'Rows per page', 'woocommerce' ) }
				// @ts-expect-error outdated types file.
				labelPosition="side"
				value={ perPage.toString() }
				onChange={ perPageChange }
				options={ pickerOptions }
			/>
		</div>
	);
}
