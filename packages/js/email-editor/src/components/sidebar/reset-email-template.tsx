/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { store as coreStore, type WpTemplate } from '@wordpress/core-data';
import { backup } from '@wordpress/icons';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import {
	Button,
	__experimentalText as Text,
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import { parse, serialize } from '@wordpress/blocks';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { type PostWithPermissions } from '../../store';
import { recordEvent } from '../../events';

function getItemTitle( item: {
	title: string | { rendered: string } | { raw: string };
} ) {
	if ( typeof item.title === 'string' ) {
		return decodeEntities( item.title );
	}
	if ( item.title && 'rendered' in item.title ) {
		return decodeEntities( item.title.rendered );
	}
	if ( item.title && 'raw' in item.title ) {
		return decodeEntities( item.title.raw );
	}
	return '';
}

const getResetEmailTemplateAction = () => {
	/**
	 * Reset email template action.
	 * Resets a wp_template to its default state by deleting the customized version.
	 */
	const resetEmailTemplate = {
		id: 'reset-email-template',
		label: __( 'Reset', 'woocommerce' ),
		supportsBulk: false,
		icon: backup,
		isEligible( item: PostWithPermissions ) {
			// Only for wp_template post type
			if ( item.type !== 'wp_template' ) {
				return false;
			}
			// Match Gutenberg's isTemplateRevertible logic:
			// Must be customized AND have an original to revert to
			if ( item.source !== 'custom' ) {
				return false;
			}
			if (
				! ( Boolean( item.plugin ) || Boolean( item.has_theme_file ) )
			) {
				return false;
			}
			const { permissions } = item;
			return permissions?.delete;
		},
		hideModalHeader: true,
		modalFocusOnMount: 'firstContentElement',
		RenderModal: ( { items, closeModal, onActionPerformed } ) => {
			const [ isBusy, setIsBusy ] = useState( false );
			const { createSuccessNotice, createErrorNotice } =
				useDispatch( noticesStore );
			const {
				invalidateResolution,
				editEntityRecord,
				saveEditedEntityRecord,
			} = useDispatch( coreStore );

			const item = items[ 0 ];
			const modalTitle = sprintf(
				// translators: %s: The template's title
				__(
					'Are you sure you want to reset "%s" to default?',
					'woocommerce'
				),
				getItemTitle( item )
			);

			return (
				<VStack spacing="5">
					<Text>{ modalTitle }</Text>
					<HStack justify="right">
						<Button
							variant="tertiary"
							onClick={ () => {
								closeModal?.();
								recordEvent(
									'reset_modal_cancel_button_clicked'
								);
							} }
							disabled={ isBusy }
							__next40pxDefaultSize
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ async () => {
								recordEvent(
									'reset_modal_confirm_button_clicked'
								);
								setIsBusy( true );

								try {
									// Fetch the original template from theme/plugin file
									// Uses the origin field to get the original source version
									const fileTemplate = ( await apiFetch( {
										path: `/wp/v2/templates/${ item.id }?context=edit&source=${ item.origin }`,
									} ) ) as WpTemplate;

									// Parse blocks from the original template content
									const blocks = parse(
										fileTemplate.content?.raw || ''
									);

									// Apply the reset with original blocks
									editEntityRecord(
										'postType',
										item.type,
										item.id,
										{
											blocks,
											content: serialize( blocks ),
											source: item.origin,
										}
									);

									// Save the entity to persist the reset and clear dirty state
									await saveEditedEntityRecord(
										'postType',
										item.type,
										item.id,
										{}
									);

									// Delete the custom database post so WordPress falls back to the file version
									// This ensures source becomes 'plugin'/'theme' instead of staying 'custom'
									await apiFetch( {
										path: `/wp/v2/templates/${ item.id }`,
										method: 'DELETE',
									} );

									// Invalidate to ensure editor and actions menu see the file version
									invalidateResolution( 'getEntityRecord', [
										'postType',
										item.type,
										item.id,
									] );

									const successMessage = sprintf(
										/* translators: The template's title. */
										__(
											'"%s" reset to default.',
											'woocommerce'
										),
										getItemTitle( item )
									);

									createSuccessNotice( successMessage, {
										type: 'snackbar',
										id: 'reset-email-template-action',
									} );

									onActionPerformed?.( items );
									setIsBusy( false );
									closeModal?.();
								} catch ( error ) {
									let errorMessage = __(
										'An error occurred while resetting the template.',
										'woocommerce'
									);

									if (
										error &&
										typeof error === 'object' &&
										'message' in error
									) {
										errorMessage = String( error.message );
									}

									recordEvent( 'reset_modal_error', {
										errorMessage,
									} );

									createErrorNotice( errorMessage, {
										type: 'snackbar',
									} );

									setIsBusy( false );
									closeModal?.();
								}
							} }
							isBusy={ isBusy }
							disabled={ isBusy }
							__next40pxDefaultSize
						>
							{ __( 'Reset', 'woocommerce' ) }
						</Button>
					</HStack>
				</VStack>
			);
		},
	};

	return resetEmailTemplate;
};

/**
 * Reset email template action for PostWithPermissions.
 */
export default getResetEmailTemplateAction;
