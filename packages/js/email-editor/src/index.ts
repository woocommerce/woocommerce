/**
 * Internal dependencies
 */
import { initialize } from './editor';

/**
 * The unique identifier used to register the email editor data store.
 * This store manages the email editor's state and settings.
 */
export {
	storeName,
	createStore,
	TemplatePreview,
	EmailBuiltStyles,
} from './store';

/**
 * React hooks for email editor functionality.
 *
 * These hooks provide access to email editor state, template previews, and styling
 * capabilities within React components.
 *
 * @example
 * ```jsx
 * import { useIsEmailEditor, usePreviewTemplates, useEmailCss } from '@woocommerce/email-editor';
 *
 * function EmailComponent() {
 *   // Check if we're in the email editor context
 *   const isEmailEditor = useIsEmailEditor();
 *
 *   // Get template previews
 *   const [templates, recentPosts, hasRecentPosts] = usePreviewTemplates();
 *
 *   // Get CSS styles for the email
 *   const [styles] = useEmailCss();
 *
 *   // Use the data...
 * }
 * ```
 *
 * @since 1.0.0
 */
export {
	/**
	 * Hook to detect if the current context is the email editor.
	 *
	 * Performs contextual checks to determine if editing an email post or
	 * associated template. Verifies the email editor store exists and compares
	 * the current post ID and type against the email editor's configuration.
	 *
	 * @return {boolean} True if in the email editor context, false otherwise
	 *
	 * @example
	 * ```js
	 * const isEmailEditor = useIsEmailEditor();
	 * if (!isEmailEditor) {
	 *   return null; // Don't render in non-email contexts
	 * }
	 * ```
	 */
	useIsEmailEditor,

	/**
	 * Hook to generate preview data for email templates and recent posts.
	 *
	 * Processes email templates and patterns to create preview configurations
	 * that combine template layouts with content. Merges template blocks with
	 * pattern blocks by replacing core/post-content blocks with actual content.
	 * Optionally includes recent email posts.
	 *
	 * @param {string}  customEmailContent - Optional custom email content for previews. Pass 'swap' to exclude recent posts
	 * @param {boolean} includeRecentPosts - Whether to include recent email posts (default: true). Recent posts are only included when this is true AND customEmailContent is not 'swap'
	 * @return [TemplatePreview[], TemplatePreview[], boolean] Tuple of template previews, recent post previews, and whether recent posts exist
	 *
	 * @example
	 * ```js
	 * const [templates, recentPosts, hasRecentPosts] = usePreviewTemplates();
	 * templates.forEach(template => {
	 *   // Render template preview
	 * });
	 * ```
	 */
	usePreviewTemplates,

	/**
	 * Hook to generate complete CSS styles for the email editor.
	 *
	 * Merges editor theme settings, user theme customizations, and layout
	 * configurations to produce final CSS output. Handles responsive design
	 * by applying device-specific styles and manages root container styles
	 * including width, padding, and content sizing.
	 *
	 * @return [EmailBuiltStyles[]] Array of style objects with CSS rules for the email editor
	 *
	 * @example
	 * ```js
	 * const [styles] = useEmailCss();
	 * styles.forEach(style => {
	 *   // Apply style.css to the document
	 * });
	 * ```
	 */
	useEmailCss,
} from './hooks';

/**
 * This method is used to initialize the email editor.
 * This method expects some data set on the global window object set on window.WooCommerceEmailEditor
 *
 * {
 *    "current_post_type": "", // The post type of the current post.
 *    "current_post_id": "", // The ID of the current post.
 *    "current_wp_user_email": "", // The email of the current user.
 *    "editor_settings": {}, // The block editor settings.
 *    "editor_theme": {}, // The block editor theme.
 *    "user_theme_post_id": "", // The ID of the user theme post.
 *    "urls": {
 *      "listings": "", // optional The URL for the listings page.
 *      "send": "", // optional The URL for the send button.
 *      "back": "" // optional The URL for the back button (top left corner).
 *    }
 *	}
 *
 * @param htmlId - The ID of the HTML element to initialize the editor in.
 */
export function initializeEditor( htmlId: string ) {
	if ( document.readyState === 'loading' ) {
		window.addEventListener(
			'DOMContentLoaded',
			() => {
				initialize( htmlId );
			},
			{ once: true }
		);
	} else {
		initialize( htmlId );
	}
}

/**
 * Experimental component meant as a replacement for initializeEditor.
 * Still working on the API, so it's not recommended to use it in production.
 *
 * @param postId   - The ID of the post to edit.
 * @param postType - The type of the post to edit.
 * @param config   - The configuration for the editor.
 *
 * @example
 * ```jsx
 * import { ExperimentalEmailEditor } from '@woocommerce/email-editor';
 *
 * <ExperimentalEmailEditor
 *   postId="123"
 *   postType="email"
 *   config={{
 *     editorSettings: {...},
 *     theme: {...},
 *     urls: {...},
 *     userEmail: "user@example.com",
 *     globalStylesPostId: 456
 *   }}
 * />
 */
export { ExperimentalEmailEditor } from './editor';

export type {
	EmailEditorSettings,
	EmailTheme,
	EmailEditorUrls,
} from './store/types';

/**
 * A modal component for sending test emails from the email editor.
 *
 * This component provides a user interface for sending preview emails to test
 * email templates before they are used in production. It includes email validation,
 * sending status feedback, error handling, and success confirmation.
 *
 * The component is typically used within the email editor's preview functionality
 * and integrates with the WordPress editor's preview system.
 *
 * @example
 * ```jsx
 * import { SendPreviewEmail } from '@woocommerce/email-editor';
 *
 * // The component manages its own modal state through the email editor store
 * <SendPreviewEmail />
 * ```
 *
 * Features:
 * - Email address validation
 * - Real-time sending status updates
 * - Error message display with retry capability
 * - Success confirmation with visual feedback
 * - Keyboard accessibility (Enter to send)
 * - Event tracking for analytics
 *
 * @since 1.0.0
 */
export { SendPreviewEmail } from './components/preview';

/**
 * A rich text input component with personalization tags support.
 *
 * This component provides a WordPress RichText editor enhanced with a button
 * that allows users to insert personalization tags (like customer name, order details, etc.)
 * into email content. The component handles tag insertion, validation, and proper
 * HTML comment formatting for personalization tags.
 *
 * @example
 * ```jsx
 * import { RichTextWithButton } from '@woocommerce/email-editor';
 *
 * <RichTextWithButton
 *   label="Email Subject"
 *   placeholder="Enter email subject..."
 *   attributeName="subject"
 *   attributeValue={currentSubject}
 *   updateProperty={(name, value) => setEmailProperty(name, value)}
 *   help="Use personalization tags to customize the subject for each customer"
 * />
 * ```
 *
 * @param props                - Component properties
 * @param props.label          - The label text displayed above the input field
 * @param props.labelSuffix    - Optional additional content to display after the label
 * @param props.help           - Optional help text displayed below the input field
 * @param props.placeholder    - Placeholder text shown when the input is empty
 * @param props.attributeName  - The name of the attribute being edited (used for tracking and updates)
 * @param props.attributeValue - The current value of the rich text content
 * @param props.updateProperty - Callback function to update the property value when content changes
 *
 * @since 1.0.0
 */
export { RichTextWithButton } from './components/personalization-tags/rich-text-with-button';

/**
 * Event tracking utilities for the email editor.
 *
 * These functions provide analytics and usage tracking capabilities for the email editor.
 * All events are prefixed with 'email_editor_events_' and can be disabled via configuration.
 *
 * @see {@link https://github.com/woocommerce/woocommerce/blob/0bed6cbba7e599c6535b777f6dc1e0009b05cb08/packages/js/email-editor/src/telemetry-tracking.md} for more information on the event tracking system.
 *
 * @example
 * ```jsx
 * import { recordEvent, recordEventOnce, isEventTrackingEnabled } from '@woocommerce/email-editor';
 *
 * // Record a user action
 * recordEvent('button_clicked', { buttonType: 'save', location: 'toolbar' });
 *
 * // Record an event only once per session
 * recordEventOnce('editor_loaded', { postType: 'email' });
 *
 * // Check if tracking is enabled before expensive operations
 * if (isEventTrackingEnabled()) {
 *   // Perform tracking-related work
 * }
 * ```
 *
 * @since 1.0.0
 */
export {
	/**
	 * Records an event with optional data for analytics tracking.
	 *
	 * Events are automatically prefixed with 'email_editor_events_' and dispatched
	 * to the global event system. Only records events when tracking is enabled.
	 *
	 * @param name - The event name in snake_case format (e.g., 'button_clicked')
	 * @param data - Optional event data as a JSON-serializable object
	 *
	 * @example
	 * ```js
	 * recordEvent('personalization_tag_inserted', {
	 *   tagType: 'customer_name',
	 *   location: 'subject_line'
	 * });
	 * ```
	 */
	recordEvent,

	/**
	 * Records an event only once per session, preventing duplicate tracking.
	 *
	 * Useful for events that should only be tracked once, such as page loads,
	 * feature discoveries, or one-time user actions. Uses a cache based on
	 * event name and data to determine uniqueness.
	 *
	 * @param name - The event name in snake_case format
	 * @param data - Optional event data as a JSON-serializable object
	 *
	 * @example
	 * ```js
	 * recordEventOnce('email_editor_first_load', {
	 *   userType: 'admin',
	 *   emailType: 'order_confirmation'
	 * });
	 * ```
	 */
	recordEventOnce,

	/**
	 * A debounced version of recordEvent that waits 700ms before recording.
	 *
	 * Prevents excessive event recording for rapid user actions like typing,
	 * scrolling, or mouse movements. The 700ms delay accounts for average
	 * human reaction time plus additional buffer for user interactions.
	 *
	 * @param name - The event name in snake_case format
	 * @param data - Optional event data as a JSON-serializable object
	 *
	 * @example
	 * ```js
	 * // This will only record once even if called multiple times rapidly
	 * debouncedRecordEvent('content_typed', {
	 *   fieldName: 'email_body',
	 *   contentLength: content.length
	 * });
	 * ```
	 */
	debouncedRecordEvent,

	/**
	 * Checks whether event tracking is currently enabled.
	 *
	 * Returns true if the global tracking configuration allows event recording.
	 * Use this to conditionally perform expensive tracking-related operations
	 * or to provide different user experiences based on tracking preferences.
	 *
	 * @return {boolean} True if event tracking is enabled, false otherwise
	 *
	 * @example
	 * ```js
	 * if (isEventTrackingEnabled()) {
	 *   // Perform analytics-related work
	 *   const userBehaviorData = collectDetailedMetrics();
	 *   recordEvent('detailed_metrics_collected', userBehaviorData);
	 * }
	 * ```
	 */
	isEventTrackingEnabled,
} from './events';
