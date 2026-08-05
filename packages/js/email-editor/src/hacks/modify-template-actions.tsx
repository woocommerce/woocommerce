/**
 * Modify template actions for email templates.
 *
 * This is a workaround for the new "Active Templates" feature in WordPress/Gutenberg.
 * When the Active Templates feature is enabled, it introduces new template actions
 * that don't align well with the email editor's requirements. The email editor currently
 * does not have template management capabilities so we miss tools needed for Active Templates.
 *
 * This solution maintains the previous reset template behavior until we identify a
 * better long-term approach that aligns with WordPress core's Active Templates feature.
 */

/**
 * Internal dependencies
 */
import { registerEntityAction, unregisterEntityAction } from '../private-apis';
import { addActionForEmail } from '../config-tools/filters';
import getResetEmailTemplateAction from '../components/sidebar/reset-email-template';

/**
 * Remove the default duplicate action and add custom reset action for email templates.
 *
 * @param postType - The post type to modify actions for.
 */
const modifyActionsForPostType = ( postType: string ) => {
	// Only modify actions for wp_template post type.
	if ( postType !== 'wp_template' ) {
		return;
	}
	// Remove the default duplicate action.
	unregisterEntityAction( 'postType', postType, 'duplicate-post' );

	// Remove the default Gutenberg reset action.
	unregisterEntityAction( 'postType', postType, 'reset-post' );

	// Add the custom reset email template action.
	registerEntityAction( 'postType', postType, getResetEmailTemplateAction() );
};

/**
 * Modify template actions for email editor.
 * - Removes duplicate action (not needed for email templates)
 * - Replaces Gutenberg's reset action with custom implementation
 */
function modifyTemplateActions() {
	// Available in WordPress 6.8+
	addActionForEmail(
		'core.registerPostTypeSchema',
		'woocommerce-email-editor/modify-template-actions',
		( postType ) => {
			modifyActionsForPostType( postType );
		}
	);

	// Support for WordPress 6.7+
	addActionForEmail(
		'core.registerPostTypeActions',
		'woocommerce-email-editor/modify-template-actions',
		( postType ) => {
			modifyActionsForPostType( postType );
		}
	);
}

export { modifyTemplateActions };
