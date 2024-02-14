/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import type { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { store as productEditorUiStore } from '../../store/product-editor-ui';
import { PrepublishButtonProps } from './types';
import { useValidations } from '../../contexts/validation-context';
import { TRACKS_SOURCE } from '../../constants';

export function PrepublishButton( {
	productId,
	productType = 'product',
}: PrepublishButtonProps ) {
	const { openPrepublishPanel } = useDispatch( productEditorUiStore );
	const { isValidating } = useValidations< Product >();
	const { isSaving, isDirty } = useSelect(
		( select ) => {
			const {
				// @ts-expect-error There are no types for this.
				isSavingEntityRecord,
				// @ts-expect-error There are no types for this.
				hasEditsForEntityRecord,
			} = select( 'core' );

			return {
				isSaving: isSavingEntityRecord< boolean >(
					'postType',
					productType,
					productId
				),
				isDirty: hasEditsForEntityRecord(
					'postType',
					productType,
					productId
				),
			};
		},
		[ productId, productType ]
	);

	const isBusy = isSaving || isValidating;
	const isDisabled = isBusy || ! isDirty;

	return (
		<Button
			onClick={ () => {
				recordEvent( 'product_prepublish_panel', {
					source: TRACKS_SOURCE,
					action: 'view',
				} );
				openPrepublishPanel();
			} }
			isBusy={ isBusy }
			aria-disabled={ isDisabled }
			children={ __( 'Publish', 'woocommerce' ) }
			variant={ 'primary' }
		/>
	);
}
