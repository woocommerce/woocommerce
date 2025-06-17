/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { __, _n, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import { trash } from '@wordpress/icons';
import { useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	Button,
	__experimentalText as Text,
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { storeName, CoreDataError, PostWithPermissions } from '../../store';
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

function getModalTitle(
	items: PostWithPermissions[],
	shouldPermanentlyDelete: boolean
) {
	if ( shouldPermanentlyDelete ) {
		return items.length > 1
			? sprintf(
					// translators: %d: number of items to delete.
					_n(
						'Are you sure you want to permanently delete %d item?',
						'Are you sure you want to permanently delete %d items?',
						items.length,
						'woocommerce'
					),
					items.length
			  )
			: sprintf(
					// translators: %s: The post's title
					__(
						'Are you sure you want to permanently delete "%s"?',
						'woocommerce'
					),
					decodeEntities( getItemTitle( items[ 0 ] ) )
			  );
	}

	return items.length > 1
		? sprintf(
				// translators: %d: The number of items (2 or more).
				_n(
					'Are you sure you want to move %d item to the trash ?',
					'Are you sure you want to move %d items to the trash ?',
					items.length,
					'woocommerce'
				),
				items.length
		  )
		: sprintf(
				// translators: %s: The item's title.
				__(
					'Are you sure you want to move "%s" to the trash?',
					'woocommerce'
				),
				getItemTitle( items[ 0 ] )
		  );
}

const getTrashEmailPostAction = () => {
	const shouldPermanentlyDelete = applyFilters(
		'woocommerce_email_editor_trash_modal_should_permanently_delete',
		false
	) as boolean;

	/**
	 * Trash email post action.
	 * A custom action to permanently delete or move to trash email posts.
	 * Cloned from core: https://github.com/WordPress/gutenberg/blob/da7adc0975d4736e555c7f81b8820b0cc4439d6c/packages/fields/src/actions/permanently-delete-post.tsx
	 */
	const trashEmailPost = {
		id: 'trash-email-post',
		label: shouldPermanentlyDelete
			? __( 'Permanently delete', 'woocommerce' )
			: __( 'Move to trash', 'woocommerce' ),
		supportsBulk: true,
		icon: trash,
		isEligible( item: PostWithPermissions ) {
			if (
				item.type === 'wp_template' ||
				item.type === 'wp_template_part' ||
				item.type === 'wp_block'
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
			const { deleteEntityRecord } = useDispatch( coreStore );

			const { urls } = useSelect(
				( select ) => ( {
					urls: select( storeName ).getUrls(),
				} ),
				[]
			);

			const goToListings = () => {
				if ( urls?.listings ) {
					window.location.href = urls.listings;
				}
			};

			return (
				<VStack spacing="5">
					<Text>
						{ getModalTitle( items, shouldPermanentlyDelete ) }
					</Text>
					<HStack justify="right">
						<Button
							variant="tertiary"
							onClick={ () => {
								closeModal?.();
								recordEvent(
									'trash_modal_cancel_button_clicked'
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
									'trash_modal_move_to_trash_button_clicked'
								);
								setIsBusy( true );
								const promiseResult = await Promise.allSettled(
									items.map( ( post ) =>
										deleteEntityRecord(
											'postType',
											post.type,
											post.id,
											{ force: shouldPermanentlyDelete },
											{ throwOnError: true }
										)
									)
								);

								// If all the promises were fulfilled with success.
								if (
									promiseResult.every(
										( { status } ) => status === 'fulfilled'
									)
								) {
									let successMessage;
									if ( promiseResult.length === 1 ) {
										successMessage = shouldPermanentlyDelete
											? sprintf(
													/* translators: The posts's title. */
													__(
														'"%s" permanently deleted.',
														'woocommerce'
													),
													getItemTitle( items[ 0 ] )
											  )
											: sprintf(
													/* translators: The item's title. */
													__(
														'"%s" moved to the trash.',
														'woocommerce'
													),
													getItemTitle( items[ 0 ] )
											  );
									} else {
										successMessage = shouldPermanentlyDelete
											? __(
													'The items were permanently deleted.',
													'woocommerce'
											  )
											: sprintf(
													/* translators: The number of items. */
													_n(
														'%s item moved to the trash.',
														'%s items moved to the trash.',
														items.length,
														'woocommerce'
													),
													items.length
											  );
									}
									createSuccessNotice( successMessage, {
										type: 'snackbar',
										id: 'trash-email-post-action',
									} );
									onActionPerformed?.( items );
									goToListings();
								} else {
									// If there was at lease one failure.
									let errorMessage;
									// If we were trying to permanently delete a single post.
									if ( promiseResult.length === 1 ) {
										const typedError =
											promiseResult[ 0 ] as {
												reason?: CoreDataError;
											};
										if ( typedError.reason?.message ) {
											errorMessage =
												typedError.reason.message;
										} else {
											errorMessage = __(
												'An error occurred while performing the action.',
												'woocommerce'
											);
										}
										// If we were trying to permanently delete multiple posts
									} else {
										const errorMessages = new Set();
										const failedPromises =
											promiseResult.filter(
												( { status } ) =>
													status === 'rejected'
											);
										for ( const failedPromise of failedPromises ) {
											const typedError =
												failedPromise as {
													reason?: CoreDataError;
												};
											if ( typedError.reason?.message ) {
												errorMessages.add(
													typedError.reason.message
												);
											}
										}
										if ( errorMessages.size === 0 ) {
											errorMessage = __(
												'An error occurred while performing the action.',
												'woocommerce'
											);
										} else if ( errorMessages.size === 1 ) {
											errorMessage = sprintf(
												/* translators: %s: an error message */
												__(
													'An error occurred while performing the action: %s',
													'woocommerce'
												),
												[ ...errorMessages ][ 0 ]
											);
										} else {
											errorMessage = sprintf(
												/* translators: %s: a list of comma separated error messages */
												__(
													'Some errors occurred while performing the action: %s',
													'woocommerce'
												),
												[ ...errorMessages ].join( ',' )
											);
										}
									}
									recordEvent(
										'trash_modal_move_to_trash_error',
										{
											errorMessage,
										}
									);
									createErrorNotice( errorMessage, {
										type: 'snackbar',
									} );
								}

								setIsBusy( false );
								closeModal?.();
							} }
							isBusy={ isBusy }
							disabled={ isBusy }
							__next40pxDefaultSize
						>
							{ shouldPermanentlyDelete
								? __( 'Delete permanently', 'woocommerce' )
								: __( 'Move to trash', 'woocommerce' ) }
						</Button>
					</HStack>
				</VStack>
			);
		},
	};

	return trashEmailPost;
};

/**
 * Delete action for PostWithPermissions.
 */
export default getTrashEmailPostAction;
