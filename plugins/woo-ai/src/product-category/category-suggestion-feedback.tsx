/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { recordCategoryTracks } from './utils';

export const CategorySuggestionFeedback = () => {
	const [ hide, setHide ] = useState( false );

	const submitFeedback = ( positive: boolean ) => {
		setHide( true );
		recordCategoryTracks( 'feedback', {
			response: positive ? 'positive' : 'negative',
		} );
	};

	return (
		<div className="category-suggestions-feedback">
			{ ! hide && (
				<div className="notice notice-info">
					<span>{ __( 'How did we do?', 'woocommerce' ) }</span>
					<span className="button-group">
						<button
							type="button"
							className="button button-small"
							onClick={ () => submitFeedback( true ) }
						>
							👍
						</button>
						<button
							type="button"
							className="button button-small"
							onClick={ () => submitFeedback( false ) }
						>
							👎
						</button>
					</span>
				</div>
			) }
		</div>
	);
};
