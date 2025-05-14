/**
 * External dependencies
 */
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { NAME_SPACE } from './constants';

const previewTextMaxLength = 150;
const previewTextRecommendedLength = 80;

// @ts-expect-error RichTextWithButton has default any type and is not exported yet.
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
			}
		);
	};

	const previewTextLength = woocommerce_email_data?.preheader?.length ?? 0;

	return (
		<>
			<br />
			{ woocommerce_email_data.email_type ===
			'customer_refunded_order' ? (
				<>
					<RichTextWithButton
						attributeName="subject_full"
						attributeValue={ woocommerce_email_data.subject_full }
						updateProperty={ updateWooMailProperty }
						label={ __( 'Full Refund Subject', 'woocommerce' ) }
						placeholder={ woocommerce_email_data.default_subject }
					/>
					<br />
					<RichTextWithButton
						attributeName="subject_partial"
						attributeValue={
							woocommerce_email_data.subject_partial
						}
						updateProperty={ updateWooMailProperty }
						label={ __( 'Partial Refund Subject', 'woocommerce' ) }
						placeholder={ woocommerce_email_data.default_subject }
					/>
				</>
			) : (
				<RichTextWithButton
					attributeName="subject"
					attributeValue={ woocommerce_email_data.subject }
					updateProperty={ updateWooMailProperty }
					label={ __( 'Subject', 'woocommerce' ) }
					placeholder={ woocommerce_email_data.default_subject }
				/>
			) }

			<br />
			<RichTextWithButton
				attributeName="preheader"
				attributeValue={ woocommerce_email_data.preheader }
				updateProperty={ updateWooMailProperty }
				label={ __( 'Preview text', 'woocommerce' ) }
				help={
					<span
						className={ clsx(
							'woocommerce-settings-panel__preview-text-length',
							{
								'woocommerce-settings-panel__preview-text-length-warning':
									previewTextLength >
									previewTextRecommendedLength,
								'woocommerce-settings-panel__preview-text-length-error':
									previewTextLength > previewTextMaxLength,
							}
						) }
					>
						{ previewTextLength }/{ previewTextMaxLength }
					</span>
				}
				placeholder={ __(
					'Shown as a preview in the inbox, next to the subject line.',
					'woocommerce'
				) }
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
