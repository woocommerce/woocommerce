/**
 * Internal dependencies
 */
import { AttributeSuggestion } from '../shared/types';

const SuggestionItem = ( {
	suggestion,
	onSuggestionClick,
}: {
	suggestion: AttributeSuggestion;
	onSuggestionClick: ( suggestion: AttributeSuggestion ) => void;
} ) => (
	<li className="suggestion-item">
		<button
			className="button select-suggestion"
			type="button"
			onClick={ () => onSuggestionClick( suggestion ) }
		>
			<p className="suggestion-content">{ suggestion.content }</p>
			<p className="suggestion-reason">
				<span className="dashicons dashicons-info"></span>
				<span>{ suggestion.reason }</span>
			</p>
		</button>
	</li>
);

export default SuggestionItem;
