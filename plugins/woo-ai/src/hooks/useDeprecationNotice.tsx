/**
 * External dependencies
 */
import { createInterpolateElement, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

type NoticeItem = {
	notice: { id: string };
};

// Global state to track if notice has been shown
let globalNoticeId: string | null = null;

export const useDeprecationNotice = () => {
	const { createNotice, removeNotice } = useDispatch( 'core/notices' );
	const [ noticeId, setNoticeId ] = useState< string | null >(
		globalNoticeId
	);
	const [ isDismissed, setIsDismissed ] = useState< boolean >(
		window.wooAITracker?.dismissed ?? false
	);

	const handleDismiss = async () => {
		removeNotice( noticeId );
		setNoticeId( null );

		try {
			if ( ! window.ajaxurl || ! window.wooAITracker?.nonce ) {
				return;
			}

			const formData = new FormData();
			formData.append( 'action', 'woo_ai_dismiss_deprecation' );
			formData.append( 'nonce', window.wooAITracker.nonce );

			await fetch( window.ajaxurl, {
				method: 'POST',
				body: formData,
			} );
			setIsDismissed( true );
		} catch ( error ) {
			// Silently fail to avoid breaking main functionality
		}
	};

	const showDeprecationNotice = async () => {
		if ( isDismissed ) {
			return;
		}

		if ( noticeId ) {
			removeNotice( noticeId );
			setNoticeId( null );
		}

		if ( globalNoticeId ) {
			removeNotice( globalNoticeId );
			setNoticeId( null );
			globalNoticeId = null;
		}

		const noticePromise: Promise< NoticeItem > = createNotice(
			'info',
			__( 'Notice: WooAI is being deprecated.', 'woocommerce' ),
			{
				type: 'snackbar',
				actions: [
					{
						label: createInterpolateElement(
							'<LearnMore /> <Separator /> <Dismiss />',
							{
								LearnMore: (
									<a
										className="woo-ai-deprecation-notice-link"
										href="https://woocommerce.com/document/woo-ai-deprecation/"
									>
										{ __( 'Learn More', 'woocommerce' ) }
									</a>
								),
								Separator: (
									<span className="woo-ai-deprecation-notice-separator">
										|
									</span>
								),
								Dismiss: (
									<button
										className="woo-ai-deprecation-notice-action"
										onClick={ handleDismiss }
									>
										{ __( 'Dismiss', 'woocommerce' ) }
									</button>
								),
							}
						),
					},
				],
			}
		);

		return noticePromise.then( ( item: NoticeItem ) => {
			setNoticeId( item.notice.id );
			globalNoticeId = item.notice.id;
		} );
	};

	return {
		showDeprecationNotice,
		removeDeprecationNotice: () => {
			if ( noticeId ) {
				removeNotice( noticeId );
				setNoticeId( null );
				globalNoticeId = null;
			}
		},
		isDismissed,
	};
};
