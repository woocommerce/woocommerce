/**
 * Internal dependencies
 */
import { ProductDataSuggestion } from '../utils/types';

export const SuggestionItem = ( {
	suggestion,
	onSuggestionClick,
}: {
	suggestion: ProductDataSuggestion;
	onSuggestionClick: ( suggestion: ProductDataSuggestion ) => void;
} ) => (
	<li className="suggestion-item">
		<button
			className="button select-suggestion"
			type="button"
			onClick={ () => onSuggestionClick( suggestion ) }
		>
			<div className="suggestion-content-container">
				<p className="suggestion-content">{ suggestion.content }</p>
				<p className="suggestion-reason">{ suggestion.reason }</p>
			</div>
			<div className="button use-suggestion">Use</div>
		</button>
	</li>
);
