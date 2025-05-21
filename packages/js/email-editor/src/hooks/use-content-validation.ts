/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import { useSelect, subscribe } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import {
	EmailContentValidationRule,
	storeName as emailEditorStore,
} from '../store';
import { useShallowEqual } from './use-shallow-equal';
import { useValidationNotices } from './use-validation-notices';

// Shared reference to an empty array for cases where it is important to avoid
// returning a new array reference on every invocation
const EMPTY_ARRAY = [];

export type ContentValidationData = {
	isInvalid: boolean;
	validateContent: () => boolean;
};

export const useContentValidation = (): ContentValidationData => {
	const { addValidationNotice, hasValidationNotice, removeValidationNotice } =
		useValidationNotices();

	const { editedContent, editedTemplateContent } = useSelect(
		( mapSelect ) => ( {
			editedContent:
				mapSelect( emailEditorStore ).getEditedEmailContent(),
			editedTemplateContent:
				mapSelect( emailEditorStore ).getCurrentTemplateContent(),
		} )
	);

	const rules: EmailContentValidationRule[] = applyFilters(
		'woocommerce_email_editor_content_validation_rules',
		EMPTY_ARRAY
	) as EmailContentValidationRule[];

	const content = useShallowEqual( editedContent );
	const templateContent = useShallowEqual( editedTemplateContent );

	const validateContent = useCallback( (): boolean => {
		let isValid = true;
		rules.forEach( ( { id, testContent, message, actions } ) => {
			// Check both content and template content for the rule.
			if ( testContent( content + templateContent ) ) {
				addValidationNotice( id, message, actions );
				isValid = false;
			} else if ( hasValidationNotice( id ) ) {
				removeValidationNotice( id );
			}
		} );
		return isValid;
	}, [
		content,
		templateContent,
		addValidationNotice,
		removeValidationNotice,
		hasValidationNotice,
		rules,
	] );

	// Subscribe to updates so notices can be dismissed once resolved.
	subscribe( () => {
		if ( ! hasValidationNotice() ) {
			return;
		}
		validateContent();
	}, coreDataStore );

	return {
		isInvalid: hasValidationNotice(),
		validateContent,
	};
};
