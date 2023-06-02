/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';

export const BLOCK_EDITOR_TOUR_SHOWN_OPTION =
	'woocommerce_block_product_tour_shown';

export const useBlockEditorTourOptions = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const [ isGuideOpen, setIsGuideOpen ] = useState( false );
	const { isTourOpen, isTourClosed } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		const isTourClosed = getOption( BLOCK_EDITOR_TOUR_SHOWN_OPTION ) === 'yes' ||
		! hasFinishedResolution( 'getOption', [
			BLOCK_EDITOR_TOUR_SHOWN_OPTION,
		] );

		return {
			isTourClosed,
			isTourOpen: !isTourClosed,
		};
	} );

	const dismissModal = () => {
		updateOptions( {
			[ BLOCK_EDITOR_TOUR_SHOWN_OPTION ]: 'yes',
		} );
	};

	return {
		dismissModal,
		isTourOpen,
		isTourClosed,
		openGuide: () => setIsGuideOpen( true ),
		closeGuide: () => setIsGuideOpen( false ),
		isGuideOpen,
	};
};
