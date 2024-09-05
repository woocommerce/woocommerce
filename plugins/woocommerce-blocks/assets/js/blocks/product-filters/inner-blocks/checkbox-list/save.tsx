/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { BlockAttributes } from './types';
import { getCheckboxListStyles } from './utils';

const Save = ( { attributes }: { attributes: BlockAttributes } ) => {
	const {
		optionElementBorder,
		customOptionElementBorder,
		optionElementSelected,
		customOptionElementSelected,
		optionElement,
		customOptionElement,
	} = attributes;

	return (
		<div
			{ ...useBlockProps.save( {
				style: getCheckboxListStyles( attributes ),
				className: clsx( 'wc-block-product-filter-checkbox-list', {
					'has-option-element-border-color':
						optionElementBorder || customOptionElementBorder,
					'has-option-element-selected-color':
						optionElementSelected || customOptionElementSelected,
					'has-option-element-color':
						optionElement || customOptionElement,
				} ),
			} ) }
		/>
	);
};

export default Save;
