/**
 * Internal dependencies
 */
import { SuggestionPillItem } from './suggestion-pill-item';

type SuggestionPillsProps = {
	suggestions: string[];
	onSuggestionClick: ( suggestion: string ) => void;
};

export const SuggestionPills: React.FC< SuggestionPillsProps > = ( {
	suggestions,
	onSuggestionClick,
} ) => (
	<ul className="woo-ai-suggestion-pills">
		{ suggestions.map( ( suggestion, index ) => (
			<SuggestionPillItem
				key={ index }
				suggestion={ suggestion }
				onSuggestionClick={ () => onSuggestionClick( suggestion ) }
			/>
		) ) }
	</ul>
);
