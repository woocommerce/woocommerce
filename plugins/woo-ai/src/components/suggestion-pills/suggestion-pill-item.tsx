type SuggestionPillItemProps = {
	suggestion: string;
	onSuggestionClick: ( suggestion: string ) => void;
};

export const SuggestionPillItem: React.FC< SuggestionPillItemProps > = ( {
	suggestion,
	onSuggestionClick,
} ) => (
	<li className="woo-ai-suggestion-pills__item">
		<button
			className="button woo-ai-suggestion-pills__select-suggestion"
			type="button"
			onClick={ () => onSuggestionClick( suggestion ) }
		>
			<span>+ </span>
			<span className="suggestion-content">{ suggestion }</span>
		</button>
	</li>
);
