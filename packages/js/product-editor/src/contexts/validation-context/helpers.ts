/**
 * Internal dependencies
 */
import { ValidationErrors } from './types';

export function findFirstInvalidElement< E extends Element = Element >(
	elementsMap: Record< string, E >,
	errors: ValidationErrors
): E | undefined {
	const fieldRefsWithError = Object.entries( elementsMap ).filter(
		( [ validatorId, element ] ) =>
			// Pick the element if it is under the selected tab.
			element?.closest( '.is-selected[role="tabpanel"]' ) &&
			Boolean( errors[ validatorId ] )
	);

	const [ firstFieldRefWithError ] = fieldRefsWithError.sort(
		( [ , firstElement ], [ , secondElement ] ) => {
			if (
				// eslint-disable-next-line no-bitwise
				firstElement.compareDocumentPosition( secondElement ) &
				Node.DOCUMENT_POSITION_FOLLOWING
			) {
				return -1;
			}
			return 1;
		}
	);

	const [ , firstElementWithError ] = firstFieldRefWithError ?? [];

	return firstElementWithError;
}
