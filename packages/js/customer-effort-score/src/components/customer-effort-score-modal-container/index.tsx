/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { optionsStore, useUser } from '@woocommerce/data';
import { createElement } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { CustomerFeedbackModal } from '../customer-feedback-modal';
import { getStoreAgeInWeeks } from '../../utils';
import { ADMIN_INSTALL_TIMESTAMP_OPTION_NAME } from '../../constants';
import store from '../../store';

const CustomerEffortScoreModal = () => {
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
				void hideCesModal();
				visibleCESModalData.props?.onRecordScore?.();
			} }
			onCloseModal={ () => {
				visibleCESModalData.props?.onCloseModal?.();
				void hideCesModal();
			} }
			shouldShowComments={ visibleCESModalData.props?.shouldShowComments }
			getExtraFieldsToBeShown={
				visibleCESModalData.getExtraFieldsToBeShown
			}
			validateExtraFields={ visibleCESModalData.validateExtraFields }
		/>
	);
};

export const CustomerEffortScoreModalContainer = () => {
	const { currentUserCan } = useUser();
	const visibleCESModalData = useSelect(
		( select ) => select( store ).getVisibleCESModalData(),
		[]
	);
	const canAccessCustomerEffortScore =
		currentUserCan( 'manage_woocommerce' ) ||
		currentUserCan( 'edit_others_shop_orders' );

	if ( ! canAccessCustomerEffortScore || ! visibleCESModalData ) {
		return null;
	}

	return <CustomerEffortScoreModal />;
};
