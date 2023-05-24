/**
 * Internal dependencies
 */
import { ProductDataSuggestion } from '../shared/types';

const SuggestionItem = ( {
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
			<p className="suggestion-content">{ suggestion.content }</p>
			<p className="suggestion-reason">{ suggestion.reason }</p>
		</button>
	</li>
);

export default SuggestionItem;
