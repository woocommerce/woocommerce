/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { closeSmall } from '@wordpress/icons';
import { Pill } from '@woocommerce/components';
import { CustomerFeedbackModal } from '@woocommerce/customer-effort-score';
import { recordEvent } from '@woocommerce/tracks';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './product-mvp-ces-footer.scss';
import { getStoreAgeInWeeks } from './utils';
import {
	ADMIN_INSTALL_TIMESTAMP_OPTION_NAME,
	ALLOW_TRACKING_OPTION_NAME,
	SHOWN_FOR_ACTIONS_OPTION_NAME,
} from './constants';
import { WooFooterItem } from '~/layout/footer';

export const PRODUCT_MVP_CES_ACTION_OPTION_NAME =
	'woocommerce_ces_product_mvp_ces_action';

export const ProductMVPCESFooter: React.FC = () => {
	const [ showFeedbackModal, setShowFeedbackModal ] = useState( false );
	const { createSuccessNotice } = useDispatch( 'core/notices' );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const {
		storeAgeInWeeks,
		cesAction,
		allowTracking,
		cesShownForActions,
		resolving: isLoading,
	} = useSelect( ( select ) => {
		const { getOption, isResolving } = select( OPTIONS_STORE_NAME );

		const action = getOption(
			PRODUCT_MVP_CES_ACTION_OPTION_NAME
		) as string;

		const shownForActions =
			( getOption( SHOWN_FOR_ACTIONS_OPTION_NAME ) as string[] ) || [];

		const adminInstallTimestamp =
			( getOption( ADMIN_INSTALL_TIMESTAMP_OPTION_NAME ) as number ) || 0;

		const allowTrackingOption =
			getOption( ALLOW_TRACKING_OPTION_NAME ) || 'no';

		const resolving =
			isResolving( 'getOption', [ SHOWN_FOR_ACTIONS_OPTION_NAME ] ) ||
			isResolving( 'getOption', [
				PRODUCT_MVP_CES_ACTION_OPTION_NAME,
			] ) ||
			adminInstallTimestamp === null ||
			isResolving( 'getOption', [
				ADMIN_INSTALL_TIMESTAMP_OPTION_NAME,
			] ) ||
			isResolving( 'getOption', [ ALLOW_TRACKING_OPTION_NAME ] );

		return {
			cesShownForActions: shownForActions,
			allowTracking: allowTrackingOption === 'yes',
			storeAgeInWeeks: getStoreAgeInWeeks( adminInstallTimestamp ),
			cesAction: action,
			resolving,
		};
	} );

	const shareFeedback = () => {
		setShowFeedbackModal( true );
		updateOptions( {
			[ SHOWN_FOR_ACTIONS_OPTION_NAME ]: [
				cesAction,
				...cesShownForActions,
			],
		} );
	};

	const onDisablingCES = () => {
		updateOptions( {
			[ PRODUCT_MVP_CES_ACTION_OPTION_NAME ]: 'hide',
		} );
	};

	const recordScore = ( score: number, comments: string ) => {
		recordEvent( 'ces_feedback', {
			action: cesAction,
			score,
			comments: comments || '',
			store_age: storeAgeInWeeks,
		} );
		createSuccessNotice(
			__(
				"Thanks for the feedback. We'll put it to good use!",
				'woocommerce'
			),
			{
				type: 'snackbar',
				icon: <span>🌟</span>,
			}
		);
		setShowFeedbackModal( false );
	};

	const showCESFooter =
		! isLoading && allowTracking && cesAction && cesAction !== 'hide';

	return (
		<>
			{ showCESFooter && (
				<WooFooterItem>
					<div className="woocommerce-product-mvp-ces-footer">
						<Pill>{ __( 'BETA', 'woocommerce' ) }</Pill>
						{ __(
							"You're using the new product editor (currently in development). How is your experience so far?",
							'woocommerce'
						) }
						<Button variant="secondary" onClick={ shareFeedback }>
							{ __( 'Share feedback', 'woocommerce' ) }
						</Button>
						<Button onClick={ onDisablingCES } variant="tertiary">
							{ __( 'Turn it off', 'woocommerce' ) }
						</Button>
						<Button
							className="woocommerce-product-mvp-ces-footer__close-button"
							icon={ closeSmall }
							label={ __(
								'Remove share feedback',
								'woocommerce'
							) }
							onClick={ onDisablingCES }
						></Button>
					</div>
				</WooFooterItem>
			) }
			{ showFeedbackModal && (
				<CustomerFeedbackModal
					label={ __(
						"How's your experience with the product editor?",
						'woocommerce'
					) }
					recordScoreCallback={ recordScore }
					onCloseModal={ () => setShowFeedbackModal( false ) }
				/>
			) }
		</>
	);
};
