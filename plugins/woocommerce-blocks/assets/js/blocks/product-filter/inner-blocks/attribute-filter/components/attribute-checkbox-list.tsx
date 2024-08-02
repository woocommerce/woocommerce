/**
 * External dependencies
 */
import { AttributeTerm } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import CheckboxListPreview from './checkbox-list-preview';

type Props = {
	attributeTerms: AttributeTerm[];
	showCounts?: boolean;
};

export const AttributeCheckboxList = ( {
	attributeTerms,
	showCounts,
}: Props ) => (
	<CheckboxListPreview
		items={ attributeTerms.map( ( term ) => {
			if ( showCounts ) return `${ term.name } (${ term.count })`;
			return term.name;
		} ) }
	/>
);
