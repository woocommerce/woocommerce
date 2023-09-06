/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { recordCategoryTracks } from './utils';

const WOO_AI_HIDE_CATEGORY_SUGGESTION_FEEDBACK =
	'woo-ai-hide-category-suggestion-feedback';

export const CategorySuggestionFeedback = () => {
	const [ showFeedbackBox, setShowFeedbackBox ] = useState(
		window.localStorage.getItem(
			WOO_AI_HIDE_CATEGORY_SUGGESTION_FEEDBACK
		) === null
	);
	const [ dismissing, setDismissing ] = useState( false );

	const submitFeedback = ( positive: boolean ) => {
		setShowFeedbackBox( false );
		recordCategoryTracks( 'feedback', {
			response: positive ? 'positive' : 'negative',
		} );
	};

	// hide the feedback notice and store the user's preference in local storage so we don't show it again
	const hideFeedbackBoxForever = () => {
		setShowFeedbackBox( false );
		window.localStorage.setItem(
			WOO_AI_HIDE_CATEGORY_SUGGESTION_FEEDBACK,
			'1'
		);
	};

	return (
		<div>
			{ showFeedbackBox && (
				<div className="category-suggestions-feedback notice notice-info">
					<div hidden={ dismissing }>
						<p>Did you find the suggested categories useful?</p>
						<p>
							<span className="button-group">
								<button
									type="button"
									className="button"
									title={ __( 'Yes', 'woocommerce' ) }
									onClick={ () => submitFeedback( true ) }
								>
									👍 Yes
								</button>
								<button
									type="button"
									className="button"
									title={ __( 'No', 'woocommerce' ) }
									onClick={ () => submitFeedback( false ) }
								>
									👎
								</button>
							</span>
						</p>
						<button
							type="button"
							className="notice-dismiss"
							title={ __(
								'Dismiss and do not show this again',
								'woocommerce'
							) }
							onClick={ () => setDismissing( true ) }
						></button>
					</div>
					<div hidden={ ! dismissing }>
						<p>
							{ __(
								"Are you sure you don't want to provide feedback?",
								'woocommerce'
							) }
						</p>
						<p>
							{ __(
								'Your feedback helps us improve the product and provide better suggestions.',
								'woocommerce'
							) }
						</p>
						<p>
							<span className="button-group">
								<button
									type="button"
									className="button"
									onClick={ () => setDismissing( false ) }
								>
									{ __( 'Provide feedback', 'woocommerce' ) }
								</button>

								<button
									type="button"
									className="button"
									onClick={ () => {
										setShowFeedbackBox( false );
										setDismissing( false );
									} }
								>
									Dismiss
								</button>
							</span>
						</p>
						<p>
							<small>
								<button
									type="button"
									className="button-link"
									onClick={ () => {
										hideFeedbackBoxForever();
										setDismissing( false );
									} }
								>
									Dismiss and do not show again
								</button>
							</small>
						</p>
					</div>
				</div>
			) }
		</div>
	);
};
