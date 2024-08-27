/**
 * Internal dependencies
 */
import registerProductEditorUiStore from './store/product-editor-ui';
import registerProductEditorHooks from './wp-hooks';

export * from './components';
export { DETAILS_SECTION_ID, TAB_GENERAL_ID, TRACKS_SOURCE } from './constants';

/**
 * Types
 */
export * from './types';

/**
 * Utils
 */
export * from './utils';

/*
 * Store
 */
export * from './store/product-editor-ui';

/**
 * Hooks
 */
export * from './hooks';
export { useValidation, useValidations } from './contexts/validation-context';
export * from './contexts/validation-context/types';

/**
 * Contexts
 */
export { EditorLoadingContext as __experimentalEditorLoadingContext } from './contexts/editor-loading-context';
export { PostTypeContext } from './contexts/post-type-context';

/**
 * Product data views page.
 */
export * from './products';

// Init the store
registerProductEditorUiStore();

registerProductEditorHooks();
