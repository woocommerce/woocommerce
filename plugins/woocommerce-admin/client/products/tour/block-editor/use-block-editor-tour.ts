/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';

export const TODO_RENAME =
	'woocommerce_block_product_tour_shown';

export const useBlockEditorTour = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const [ isGuideOpen, setIsGuideOpen ] = useState( false );
	const { isTourOpen, isTourClosed } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		const isTourClosed = getOption( TODO_RENAME ) === 'yes' ||
		! hasFinishedResolution( 'getOption', [
			TODO_RENAME,
		] );

		return {
			isTourClosed,
			isTourOpen: !isTourClosed,
		};
	} );

	const dismissModal = () => {
		updateOptions( {
			[ TODO_RENAME ]: 'yes',
		} );
	};

	return {
		dismissModal,
		isTourOpen,
		isTourClosed,
		openGuide: () => setIsGuideOpen( true ),
		isGuideOpen,
	};
};
