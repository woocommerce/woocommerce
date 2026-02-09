/**
 * External dependencies
 */
import { AddressAutocompleteResult } from '@woocommerce/types';
import { decodeEntities } from '@wordpress/html-entities';
import { sanitizeHTML } from '@woocommerce/sanitize';

/**
 * Get highlighted label parts based on matches returned by `search` results.
 */
function getHighlightedLabel(
	label: string,
	matches: { offset: number; length: number }[]
): React.ReactNode[] {
	// Sanitize label for display.
	const sanitizedLabel = decodeEntities( label );
	const parts: React.ReactNode[] = [];
	let lastIndex = 0;

	// Validate matches array.
	if ( ! Array.isArray( matches ) ) {
		// If matches is invalid, just return plain text.
		return [ sanitizedLabel ];
	}

	// Validate matches.
	const safeMatches = matches.filter(
		( match ) =>
			match &&
			typeof match.offset === 'number' &&
			typeof match.length === 'number' &&
			match.offset >= 0 &&
			match.length > 0 &&
			match.offset + match.length <= sanitizedLabel.length
	);

	safeMatches.forEach( ( match, index ) => {
		// Add text before match.
		if ( match.offset > lastIndex ) {
			parts.push( sanitizedLabel.slice( lastIndex, match.offset ) );
		}

		// Add bold matched text.
		parts.push(
			<strong key={ `match-${ index }` }>
				{ sanitizedLabel.slice(
					match.offset,
					match.offset + match.length
				) }
			</strong>
		);

		lastIndex = match.offset + match.length;
	} );

	// Add remaining text.
	if ( lastIndex < sanitizedLabel.length ) {
		parts.push( sanitizedLabel.slice( lastIndex ) );
	}

	return parts;
}

export const Suggestions = ( {
	suggestions,
	branding,
	selectedSuggestion,
	addressType,
	onSuggestionClick,
}: {
	suggestions: AddressAutocompleteResult[];
	branding?: string;
	selectedSuggestion: number;
	addressType: string;
	onSuggestionClick: ( suggestionId: string ) => void;
} ) => {
	if ( ! suggestions ) {
		return null;
	}

	const listId = `address-suggestions-${ addressType }-list`;

	return (
		<div
			className="wc-block-components-address-autocomplete-suggestions"
			role="region"
			aria-live="polite"
		>
			<ul
				className="suggestions-list"
				id={ listId }
				role="listbox"
				aria-label="Address suggestions"
			>
				{ suggestions.slice( 0, 5 ).map( ( item, index ) => (
					// eslint-disable-next-line jsx-a11y/click-events-have-key-events -- keypress is handled by AddressAutocomplete component.
					<li
						key={ item.id }
						id={ `suggestion-item-${ addressType }-${ index }` }
						className={ `wc-block-components-address-autocomplete-suggestion${
							selectedSuggestion === index ? ' active' : ''
						}` }
						role="option"
						tabIndex={ -1 }
						aria-selected={ selectedSuggestion === index }
						onClick={ () => onSuggestionClick( item.id ) }
						style={ { cursor: 'pointer' } }
					>
						{ getHighlightedLabel(
							item?.label,
							item?.matchedSubstrings || []
						) }
					</li>
				) ) }
			</ul>
			{ branding ? (
				<div
					className="woocommerce-address-autocomplete-branding"
					dangerouslySetInnerHTML={ {
						__html: sanitizeHTML( branding ),
					} }
				/>
			) : null }
		</div>
	);
};
