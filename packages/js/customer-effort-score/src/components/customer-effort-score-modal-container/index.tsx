/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { optionsStore } from '@woocommerce/data';
import { createElement } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { CustomerFeedbackModal } from '../';
import { getStoreAgeInWeeks } from '../../utils';
import { ADMIN_INSTALL_TIMESTAMP_OPTION_NAME } from '../../constants';
import store from '../../store';

export const CustomerEffortScoreModalContainer: React.FC = () => {
	const { createSuccessNotice } = useDispatch( 'core/notices' );
	const { hideCesModal } = useDispatch( store );
	const {
		storeAgeInWeeks,
		resolving: isLoading,
		visibleCESModalData,
	} = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } = select( optionsStore );
		const { getVisibleCESModalData } = select( store );

		const adminInstallTimestamp =
			( getOption( ADMIN_INSTALL_TIMESTAMP_OPTION_NAME ) as number ) || 0;

		const resolving =
			adminInstallTimestamp === null ||
			! hasFinishedResolution( 'getOption', [
				ADMIN_INSTALL_TIMESTAMP_OPTION_NAME,
			] );

		return {
			storeAgeInWeeks: getStoreAgeInWeeks( adminInstallTimestamp ),
			visibleCESModalData: getVisibleCESModalData(),
			resolving,
		};
	}, [] );

	const recordScore = (
		score: number,
		secondScore: number,
		comments: string,
		extraFieldsValues: { [ key: string ]: string } = {}
	) => {
		recordEvent( 'ces_feedback', {
			action: visibleCESModalData.action,
			score,
			score_second_question: secondScore ?? null,
			score_combined: score + ( secondScore ?? 0 ),
			comments: comments || '',
			...extraFieldsValues,
			store_age: storeAgeInWeeks,
			...visibleCESModalData.tracksProps,
		} );

		createSuccessNotice(
			visibleCESModalData.onSubmitLabel ||
				__(
					"Thanks for the feedback. We'll put it to good use!",
					'woocommerce'
				),
			visibleCESModalData.onSubmitNoticeProps || {}
		);
	};

	if ( ! visibleCESModalData || isLoading ) {
		return null;
	}

	return (
		<CustomerFeedbackModal
			title={ visibleCESModalData.title }
			description={ visibleCESModalData.description }
			showDescription={ visibleCESModalData.showDescription }
			firstQuestion={ visibleCESModalData.firstQuestion }
			secondQuestion={ visibleCESModalData.secondQuestion }
			recordScoreCallback={ ( ...args ) => {
				recordScore( ...args );
				hideCesModal();
				visibleCESModalData.props?.onRecordScore?.();
			} }
			onCloseModal={ () => {
				visibleCESModalData.props?.onCloseModal?.();
				hideCesModal();
			} }
			shouldShowComments={ visibleCESModalData.props?.shouldShowComments }
			getExtraFieldsToBeShown={
				visibleCESModalData.getExtraFieldsToBeShown
			}
			validateExtraFields={ visibleCESModalData.validateExtraFields }
		/>
	);
};
