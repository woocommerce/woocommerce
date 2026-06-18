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

const EMPTY_SHOWN_ACTIONS: string[] = [];

export const useCustomerEffortScoreModal = () => {
	const { showCesModal: _showCesModal, showProductMVPFeedbackModal } =
		useDispatch( STORE_KEY );
	const { updateOptions } = useDispatch( optionsStore );

	const { shownForActions, isLoading } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } = select( optionsStore );

		const rawShownForActions = getOption( SHOWN_FOR_ACTIONS_OPTION_NAME );
		const shownForActionsOption = Array.isArray( rawShownForActions )
			? rawShownForActions
			: EMPTY_SHOWN_ACTIONS;

		const resolving = ! hasFinishedResolution( 'getOption', [
			SHOWN_FOR_ACTIONS_OPTION_NAME,
		] );

		return {
			shownForActions: shownForActionsOption,
			isLoading: resolving,
		};
	}, [] );

	const wasPreviouslyShown = ( action: string ) => {
		return shownForActions.includes( action );
	};

	const markCesAsShown = async ( action: string ) => {
		const { getOption } = resolveSelect( optionsStore );

		const rawShownForActions = await getOption(
			SHOWN_FOR_ACTIONS_OPTION_NAME
		);
		const shownForActionsOption = Array.isArray( rawShownForActions )
			? rawShownForActions
			: [];

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
