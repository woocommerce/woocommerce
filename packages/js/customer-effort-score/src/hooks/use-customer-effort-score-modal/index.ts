/**
 * External dependencies
 */
import { resolveSelect, useDispatch, useSelect } from '@wordpress/data';
import { optionsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { SHOWN_FOR_ACTIONS_OPTION_NAME } from '../../constants';
import { STORE_KEY } from '../../store';

export const useCustomerEffortScoreModal = () => {
	const { showCesModal: _showCesModal, showProductMVPFeedbackModal } =
		useDispatch( STORE_KEY );
	const { updateOptions } = useDispatch( optionsStore );

	const { wasPreviouslyShown, isLoading } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } = select( optionsStore );

		const shownForActionsOption =
			( getOption( SHOWN_FOR_ACTIONS_OPTION_NAME ) as string[] ) || [];

		const resolving = ! hasFinishedResolution( 'getOption', [
			SHOWN_FOR_ACTIONS_OPTION_NAME,
		] );

		return {
			wasPreviouslyShown: ( action: string ) => {
				return shownForActionsOption.includes( action );
			},
			isLoading: resolving,
		};
	}, [] );

	const markCesAsShown = async ( action: string ) => {
		const { getOption } = resolveSelect( optionsStore );

		const shownForActionsOption =
			( ( await getOption(
				SHOWN_FOR_ACTIONS_OPTION_NAME
			) ) as string[] ) || [];

		updateOptions( {
			[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
				action,
				...shownForActionsOption,
			],
		} );
	};

	const showCesModal = (
		surveyProps = {},
		props = {},
		onSubmitNoticeProps = {},
		tracksProps = {}
	) => {
		_showCesModal( surveyProps, props, onSubmitNoticeProps, tracksProps );
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore We don't have type definitions for this.
		markCesAsShown( surveyProps.action );
	};

	return {
		wasPreviouslyShown,
		isLoading,
		showCesModal,
		showProductMVPFeedbackModal,
	};
};
