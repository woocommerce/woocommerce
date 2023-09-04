// Reference: https://github.com/WordPress/gutenberg/blob/v16.4.0/packages/edit-site/src/components/save-hub/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';

import { useSelect, useDispatch } from '@wordpress/data';
// @ts-ignore No types for this exist yet.
import { Button, __experimentalHStack as HStack } from '@wordpress/components';
import { __, sprintf, _n } from '@wordpress/i18n';
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
// @ts-ignore No types for this exist yet.
import { store as blockEditorStore } from '@wordpress/block-editor';
// @ts-ignore No types for this exist yet.
import { check } from '@wordpress/icons';
// @ts-ignore No types for this exist yet.
import { privateApis as routerPrivateApis } from '@wordpress/router';
// @ts-ignore No types for this exist yet.
import { store as noticesStore } from '@wordpress/notices';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-ignore No types for this exist yet.
import SaveButton from '@wordpress/edit-site/build-module/components/save-button';

/**
 * Internal dependencies
 */
import { CustomizeStoreContext } from '../';

const { useLocation } = unlock( routerPrivateApis );

const PUBLISH_ON_SAVE_ENTITIES = [
	{
		kind: 'postType',
		name: 'wp_navigation',
	},
];

export const SaveHub = () => {
	const saveNoticeId = 'site-edit-save-notice';
	const { params } = useLocation();
	const { sendEvent } = useContext( CustomizeStoreContext );

	// @ts-ignore No types for this exist yet.
	const { __unstableMarkLastChangeAsPersistent } =
		useDispatch( blockEditorStore );

	const { createSuccessNotice, createErrorNotice, removeNotice } =
		useDispatch( noticesStore );

	const { dirtyCurrentEntity, countUnsavedChanges, isDirty, isSaving } =
		useSelect(
			( select ) => {
				const {
					// @ts-ignore No types for this exist yet.
					__experimentalGetDirtyEntityRecords,
					// @ts-ignore No types for this exist yet.
					isSavingEntityRecord,
				} = select( coreStore );
				const dirtyEntityRecords =
					__experimentalGetDirtyEntityRecords();
				let calcDirtyCurrentEntity = null;

				if ( dirtyEntityRecords.length === 1 ) {
					// if we are on global styles
					if (
						params.path?.includes( 'color-palette' ) ||
						params.path?.includes( 'fonts' )
					) {
						calcDirtyCurrentEntity = dirtyEntityRecords.find(
							// @ts-ignore No types for this exist yet.
							( record ) => record.name === 'globalStyles'
						);
					}
					// if we are on pages
					else if ( params.postId ) {
						calcDirtyCurrentEntity = dirtyEntityRecords.find(
							// @ts-ignore No types for this exist yet.
							( record ) =>
								record.name === params.postType &&
								String( record.key ) === params.postId
						);
					}
				}

				return {
					dirtyCurrentEntity: calcDirtyCurrentEntity,
					isDirty: dirtyEntityRecords.length > 0,
					isSaving: dirtyEntityRecords.some(
						( record: {
							kind: string;
							name: string;
							key: string;
						} ) =>
							isSavingEntityRecord(
								record.kind,
								record.name,
								record.key
							)
					),
					countUnsavedChanges: dirtyEntityRecords.length,
				};
			},
			[ params.path, params.postType, params.postId ]
		);

	const {
		// @ts-ignore No types for this exist yet.
		editEntityRecord,
		// @ts-ignore No types for this exist yet.
		saveEditedEntityRecord,
		// @ts-ignore No types for this exist yet.
		__experimentalSaveSpecifiedEntityEdits: saveSpecifiedEntityEdits,
	} = useDispatch( coreStore );

	const saveCurrentEntity = async () => {
		if ( ! dirtyCurrentEntity ) return;

		removeNotice( saveNoticeId );
		const { kind, name, key, property } = dirtyCurrentEntity;

		try {
			if ( dirtyCurrentEntity.kind === 'root' && name === 'site' ) {
				await saveSpecifiedEntityEdits( 'root', 'site', undefined, [
					property,
				] );
			} else {
				if (
					PUBLISH_ON_SAVE_ENTITIES.some(
						( typeToPublish ) =>
							typeToPublish.kind === kind &&
							typeToPublish.name === name
					)
				) {
					editEntityRecord( kind, name, key, { status: 'publish' } );
				}

				await saveEditedEntityRecord( kind, name, key );
			}

			__unstableMarkLastChangeAsPersistent();

			createSuccessNotice( __( 'Site updated.', 'woocommerce' ), {
				type: 'snackbar',
				id: saveNoticeId,
			} );
		} catch ( error ) {
			createErrorNotice(
				`${ __( 'Saving failed.', 'woocommerce' ) } ${ error }`
			);
		}
	};

	const renderButton = () => {
		// if we have only one unsaved change and it matches current context, we can show a more specific label
		let label = dirtyCurrentEntity
			? __( 'Save', 'woocommerce' )
			: sprintf(
					// translators: %d: number of unsaved changes (number).
					_n(
						'Review %d change…',
						'Review %d changes…',
						countUnsavedChanges,
						'woocommerce'
					),
					countUnsavedChanges
			  );

		if ( isSaving ) {
			label = __( 'Saving', 'woocommerce' );
		}

		if ( dirtyCurrentEntity ) {
			return (
				<Button
					variant="primary"
					onClick={ saveCurrentEntity }
					isBusy={ isSaving }
					disabled={ isSaving }
					aria-disabled={ isSaving }
					className="edit-site-save-hub__button"
					// @ts-ignore No types for this exist yet.

					__next40pxDefaultSize
				>
					{ label }
				</Button>
			);
		}
		const disabled = isSaving || ! isDirty;

		if ( ! isSaving && ! isDirty ) {
			return (
				<Button
					variant="primary"
					onClick={ () => {
						sendEvent( 'FINISH_CUSTOMIZATION' );
					} }
					className="edit-site-save-hub__button"
					// @ts-ignore No types for this exist yet.
					__next40pxDefaultSize
				>
					{ __( 'Done', 'woocommerce' ) }
				</Button>
			);
		}

		return (
			<SaveButton
				className="edit-site-save-hub__button"
				variant={ disabled ? null : 'primary' }
				showTooltip={ false }
				icon={ disabled && ! isSaving ? check : null }
				defaultLabel={ label }
				__next40pxDefaultSize
			/>
		);
	};

	return (
		<HStack className="edit-site-save-hub" alignment="right" spacing={ 4 }>
			{ renderButton() }
		</HStack>
	);
};
