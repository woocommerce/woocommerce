/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect, useDispatch } from '@wordpress/data';

export const TODO_RENAME =
	'woocommerce_block_product_tour_shown';

export const useBlockEditorTour = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { isClosed } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		return {
			isClosed:
				getOption( TODO_RENAME ) === 'yes' ||
				! hasFinishedResolution( 'getOption', [
					TODO_RENAME,
				] ),
		};
	} );

	const dismissModal = () => {
		updateOptions( {
			[ TODO_RENAME ]: 'yes',
		} );
	};

	return {
		dismissModal,
		isClosed,
	};
};
