/**
 * External dependencies
 */
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import {
	BaseControl,
	PanelRow,
	TextControl,
	ToggleControl,
	__experimentalText as Text,
} from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { NAME_SPACE } from './constants';
import { EmailStatus } from './email-status';

const previewTextMaxLength = 150;
const previewTextRecommendedLength = 80;

type SidebarSettings = {
	RichTextWithButton: React.ComponentType< {
		attributeName: string;
		attributeValue: string;
		updateProperty: ( name: string, value: string | boolean ) => void;
		label: string;
		placeholder: string;
		help?: React.ReactNode;
	} >;
	recordEvent: ( name: string, data?: Record< string, unknown > ) => void;
	debouncedRecordEvent: (
		name: string,
		data?: Record< string, unknown >
	) => void;
};

const SidebarSettings = ( {
	RichTextWithButton,
	recordEvent,
	debouncedRecordEvent,
}: SidebarSettings ) => {
	const [ woocommerce_email_data ] = useEntityProp(
		'postType',
		'woo_email',
		'woocommerce_data'
	);

	// Initialize toggle control state
	const [ addBCC, setAddBCC ] = useState( !! woocommerce_email_data?.bcc );
	const [ addCC, setAddCC ] = useState( !! woocommerce_email_data?.cc );

	if ( ! woocommerce_email_data ) {
		return null;
	}

	const updateWooMailProperty = ( name: string, value: string | boolean ) => {
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

	if (
		woocommerce_email_data.email_type ===
		'customer_partially_refunded_order'
	) {
		return (
			<>
				<br />
				<Text>
					{ __(
						'Update this email configuration in the "Order refunded" email.',
						'woocommerce'
					) }
				</Text>
				<br />
			</>
		);
	}

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
			<PanelRow>
				<BaseControl
					__nextHasNoMarginBottom
					label={ __( 'Recipients', 'woocommerce' ) }
					id="woocommerce-email-editor-recipients"
				>
					{ woocommerce_email_data.recipient === null ? (
						<p className="woocommerce-email-editor-recipients-help">
							{ __(
								'This email is sent to Customer.',
								'woocommerce'
							) }
						</p>
					) : (
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							name="recipient"
							data-testid="email_recipient"
							value={ woocommerce_email_data.recipient }
							onChange={ ( value ) => {
								updateWooMailProperty( 'recipient', value );
							} }
							help={ __(
								'Separate with commas to add multiple email addresses.',
								'woocommerce'
							) }
						/>
					) }
				</BaseControl>
			</PanelRow>
			<PanelRow>
				<BaseControl __nextHasNoMarginBottom>
					<ToggleControl
						__nextHasNoMarginBottom
						name="add_cc"
						checked={ addCC }
						label={ __( 'Add CC', 'woocommerce' ) }
						onChange={ ( value ) => {
							setAddCC( value );
							if ( ! value ) {
								updateWooMailProperty( 'cc', '' );
							}
							recordEvent( 'email_cc_toggle_clicked', {
								isEnabled: value,
							} );
						} }
					/>
				</BaseControl>
			</PanelRow>
			{ addCC && (
				<PanelRow>
					<BaseControl __nextHasNoMarginBottom>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							data-testid="email_cc"
							value={ woocommerce_email_data?.cc || '' }
							onChange={ ( value ) => {
								updateWooMailProperty( 'cc', value );
								debouncedRecordEvent(
									'email_cc_input_updated',
									{
										value,
									}
								);
							} }
							help={ __(
								'Add recipients who will receive a copy of the email. Separate multiple addresses with commas.',
								'woocommerce'
							) }
						/>
					</BaseControl>
				</PanelRow>
			) }
			<PanelRow>
				<BaseControl __nextHasNoMarginBottom>
					<ToggleControl
						__nextHasNoMarginBottom
						name="add_bcc"
						checked={ addBCC }
						label={ __( 'Add BCC', 'woocommerce' ) }
						onChange={ ( value ) => {
							setAddBCC( value );
							if ( ! value ) {
								updateWooMailProperty( 'bcc', '' );
							}
							recordEvent( 'email_bcc_toggle_clicked', {
								isEnabled: value,
							} );
						} }
					/>
				</BaseControl>
			</PanelRow>
			{ addBCC && (
				<PanelRow>
					<BaseControl __nextHasNoMarginBottom>
						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							data-testid="email_bcc"
							value={ woocommerce_email_data?.bcc || '' }
							onChange={ ( value ) => {
								updateWooMailProperty( 'bcc', value );
								debouncedRecordEvent(
									'email_bcc_input_updated',
									{
										value,
									}
								);
							} }
							help={ __(
								'Add recipients who will receive a hidden copy of the email. Separate multiple addresses with commas.',
								'woocommerce'
							) }
						/>
					</BaseControl>
				</PanelRow>
			) }
		</>
	);
};

export function modifySidebar() {
	addFilter(
		'woocommerce_email_editor_setting_sidebar_email_status_component',
		NAME_SPACE,
		( _originalComponent, tracking ) => {
			return () => <EmailStatus recordEvent={ tracking.recordEvent } />;
		}
	);
	addFilter(
		'woocommerce_email_editor_setting_sidebar_extension_component',
		NAME_SPACE,
		( RichTextWithButton, tracking ) => {
			return () => (
				<SidebarSettings
					RichTextWithButton={ RichTextWithButton }
					recordEvent={ tracking.recordEvent }
					debouncedRecordEvent={ tracking.debouncedRecordEvent }
				/>
			);
		}
	);
}
