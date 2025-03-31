/**
 * External dependencies
 */
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { NAME_SPACE } from './constants';

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
const SidebarSettings = ( { RichTextWithButton } ) => {
	const [ woocommerce_email_data ] = useEntityProp(
		'postType',
		'woo_email',
		'woocommerce_data'
	);

	const updateWooMailProperty = ( name: string, value: string ) => {
		const editedPost = select( coreDataStore ).getEditedEntityRecord(
			'postType',
			'woo_email',
			window.WooCommerceEmailEditor.current_post_id
		);

		// @ts-expect-error Property 'mailpoet_data' does not exist on type 'Updatable<Attachment<any>>'.
		const woocommerce_data = editedPost?.woocommerce_data || {};
		void dispatch( coreDataStore ).editEntityRecord(
			'postType',
			'woo_email',
			window.WooCommerceEmailEditor.current_post_id,
			{
				woocommerce_data: {
					...woocommerce_data,
					[ name ]: value,
				},
			},
		);
	};

	return (
		<>
			<br />
			<RichTextWithButton
				attributeName="subject"
				attributeValue={ woocommerce_email_data.subject }
				updateProperty={ updateWooMailProperty }
				label={ __( 'Subject', 'woocommerce' ) }
				placeholder={ woocommerce_email_data.default_subject }
			/>

			<br />
			<RichTextWithButton
				attributeName="heading"
				attributeValue={ woocommerce_email_data.heading }
				updateProperty={ updateWooMailProperty }
				label={ __( 'Heading', 'woocommerce' ) }
				placeholder={ woocommerce_email_data.default_heading}
			/>
		</>
	);
};

export function modifySidebar() {
	addFilter(
		'woocommerce_email_editor_setting_sidebar_extension_component',
		NAME_SPACE,
		( RichTextWithButton ) => {
			return () => (
				<SidebarSettings RichTextWithButton={ RichTextWithButton } />
			);
		}
	);
}
