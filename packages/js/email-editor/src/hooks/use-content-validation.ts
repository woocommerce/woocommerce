/**
 * External dependencies
 */
import { useCallback, useEffect } from '@wordpress/element';
import { useSelect, subscribe } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { applyFilters, addFilter, removeFilter } from '@wordpress/hooks';

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

export const validateEmailContent = (
	content: string,
	templateContent: string,
	{
		addValidationNotice,
		hasValidationNotice,
		removeValidationNotice,
	}: {
		addValidationNotice: (
			id: string,
			message: string,
			actions: unknown[]
		) => void;
		hasValidationNotice: ( id?: string ) => boolean;
		removeValidationNotice: ( id: string ) => void;
	}
): boolean => {
	const rules: EmailContentValidationRule[] = applyFilters(
		'woocommerce_email_editor_content_validation_rules',
		EMPTY_ARRAY
	) as EmailContentValidationRule[];

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

	const content = useShallowEqual( editedContent );
	const templateContent = useShallowEqual( editedTemplateContent );

	const validateContent = useCallback( (): boolean => {
		return validateEmailContent( content, templateContent, {
			addValidationNotice,
			hasValidationNotice,
			removeValidationNotice,
		} );
	}, [
		content,
		templateContent,
		addValidationNotice,
		removeValidationNotice,
		hasValidationNotice,
	] );

	useEffect( () => {
		const filterHandler = async ( edits: Record< string, unknown > ) => {
			const isValid = validateContent();
			if ( ! isValid ) {
				throw new Error();
			}
			return edits;
		};

		addFilter(
			'editor.preSavePost',
			'woocommerce/email-editor/validate-content',
			filterHandler
		);

		return () => {
			removeFilter(
				'editor.preSavePost',
				'woocommerce/email-editor/validate-content'
			);
		};
	}, [ validateContent ] );

	// Subscribe to updates so notices can be dismissed once resolved.
	useEffect( () => {
		const unsubscribe = subscribe( () => {
			if ( ! hasValidationNotice() ) {
				return;
			}
			validateContent();
		}, coreDataStore );

		return () => unsubscribe();
	}, [ hasValidationNotice, validateContent ] );

	return {
		isInvalid: hasValidationNotice(),
		validateContent,
	};
};
