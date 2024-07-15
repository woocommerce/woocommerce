/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { SectionActions } from '../../../components/block-slot-fill';
import { CustomFields } from '../../../components/custom-fields';
import { ProductEditorBlockEditProps } from '../../../types';
import { CustomFieldsBlockAttributes } from './types';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< CustomFieldsBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	return (
		<div { ...blockProps }>
			<CustomFields
				renderActionButtonsWrapper={ ( buttons ) => (
					<SectionActions>{ buttons }</SectionActions>
				) }
			/>
		</div>
	);
}
