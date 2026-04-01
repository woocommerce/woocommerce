/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { store as coreStore } from '@wordpress/core-data';
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

// eslint-disable-next-line @woocommerce/dependency-group
import type { PostWithPermissions } from '@woocommerce/email-editor';

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

const getResetNotificationEmailContentAction = () => {
	/**
	 * Reset notification email content action.
	 * Resets a woo_email post content to the original state as distributed by the plugin.
	 */
	const resetNotificationEmailContent = {
		id: 'reset-notification-email-content',
		label: __( 'Reset', 'woocommerce' ),
		supportsBulk: false,
		icon: backup,
		isEligible( item: PostWithPermissions ) {
			if (
				item.type === 'wp_template' ||
				item.type === 'wp_template_part' ||
				item.type === 'wp_block'
			) {
				return false;
			}
			const { permissions } = item;
			return permissions?.update;
		},
		hideModalHeader: true,
		modalFocusOnMount: 'firstContentElement',
		RenderModal: ( {
			items,
			closeModal,
			onActionPerformed,
		}: {
			items: PostWithPermissions[];
			closeModal?: () => void;
			onActionPerformed?: ( items: PostWithPermissions[] ) => void;
		} ) => {
			const [ isBusy, setIsBusy ] = useState( false );
			const { createSuccessNotice, createErrorNotice } =
				useDispatch( noticesStore );
			const { editEntityRecord, saveEditedEntityRecord } =
				useDispatch( coreStore );

			const item = items[ 0 ];
			const modalTitle = sprintf(
				// translators: %s: The email's title
				__(
					'Are you sure you want to reset "%s" content to the default?',
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
							} }
							disabled={ isBusy }
							__next40pxDefaultSize
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ async () => {
								setIsBusy( true );

								try {
									const response = ( await apiFetch( {
										path: `/woocommerce-email-editor/v1/emails/${ item.id }/default-content`,
									} ) ) as { content: string };

									const blocks = parse(
										response.content || ''
									);

									await editEntityRecord(
										'postType',
										item.type,
										item.id,
										{
											blocks,
											content: serialize( blocks ),
										}
									);

									await saveEditedEntityRecord(
										'postType',
										item.type,
										item.id,
										{}
									);

									const successMessage = sprintf(
										/* translators: The email's title. */
										__(
											'"%s" content reset to default.',
											'woocommerce'
										),
										getItemTitle( item )
									);

									createSuccessNotice( successMessage, {
										type: 'snackbar',
										id: 'reset-notification-email-content-action',
									} );

									onActionPerformed?.( items );
								} catch ( error ) {
									let errorMessage = __(
										'An error occurred while resetting the email content.',
										'woocommerce'
									);

									if (
										error &&
										typeof error === 'object' &&
										'message' in error
									) {
										errorMessage = String( error.message );
									}

									createErrorNotice( errorMessage, {
										type: 'snackbar',
									} );
								} finally {
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

	return resetNotificationEmailContent;
};

export default getResetNotificationEmailContentAction;
