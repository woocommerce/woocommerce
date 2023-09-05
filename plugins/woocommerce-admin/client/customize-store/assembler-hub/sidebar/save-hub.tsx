// Reference: https://github.com/WordPress/gutenberg/blob/v16.4.0/packages/edit-site/src/components/save-hub/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useContext, useEffect } from '@wordpress/element';

import { useSelect, useDispatch } from '@wordpress/data';
// @ts-ignore No types for this exist yet.
import { Button, __experimentalHStack as HStack } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
// @ts-ignore No types for this exist yet.
import { store as blockEditorStore } from '@wordpress/block-editor';

// @ts-ignore No types for this exist yet.
import { privateApis as routerPrivateApis } from '@wordpress/router';
// @ts-ignore No types for this exist yet.
import { store as noticesStore } from '@wordpress/notices';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-ignore No types for this exist yet.
import { useEntitiesSavedStatesIsDirty as useIsDirty } from '@wordpress/editor';

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

	const {
		dirtyEntityRecords,
		isDirty,
	}: {
		dirtyEntityRecords: {
			key?: string | number;
			kind: string;
			name: string;
			property?: string;
			title: string;
		}[];
		isDirty: boolean;
	} = useIsDirty();

	const { isSaving } = useSelect(
		( select ) => {
			return {
				isSaving: dirtyEntityRecords.some( ( record ) =>
					// @ts-ignore No types for this exist yet.
					select( coreStore ).isSavingEntityRecord(
						record.kind,
						record.name,
						record.key
					)
				),
			};
		},
		[ dirtyEntityRecords ]
	);

	const {
		// @ts-ignore No types for this exist yet.
		editEntityRecord,
		// @ts-ignore No types for this exist yet.
		saveEditedEntityRecord,
		// @ts-ignore No types for this exist yet.
		__experimentalSaveSpecifiedEntityEdits: saveSpecifiedEntityEdits,
	} = useDispatch( coreStore );

	useEffect( () => {
		dirtyEntityRecords.forEach( ( entity ) => {
			/* This is a hack to reset the entity record when the user navigates away from editing page to main page.
			This is needed because Gutenberg does not provide a way to reset the entity record. Replace this when we have a better way to do this.
			We will need to add different conditions here when we implement editing for other entities.
			 */

			if (
				entity.kind === 'root' &&
				entity.name === 'site' &&
				entity.property
			) {
				// Reset site icon edit
				editEntityRecord(
					'root',
					'site',
					undefined,
					{
						[ entity.property ]: undefined,
					},
					{ undoIgnore: true }
				);
			} else {
				editEntityRecord(
					entity.kind,
					entity.name,
					entity.key,
					{
						selection: undefined,
						blocks: undefined,
						content: undefined,
					},
					{ undoIgnore: true }
				);
			}
		} );
		// Only run when path changes.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ params.path ] );

	const save = async () => {
		removeNotice( saveNoticeId );

		try {
			for ( const { kind, name, key, property } of dirtyEntityRecords ) {
				if ( kind === 'root' && name === 'site' ) {
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
						editEntityRecord( kind, name, key, {
							status: 'publish',
						} );
					}

					await saveEditedEntityRecord( kind, name, key );
					__unstableMarkLastChangeAsPersistent();
				}
			}

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
		if ( params.path === '/customize-store' ) {
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

		// if we have only one unsaved change and it matches current context, we can show a more specific label
		const label = isSaving
			? __( 'Saving', 'woocommerce' )
			: __( 'Save', 'woocommerce' );

		const isDisabled = ! isDirty || isSaving;

		return (
			<Button
				variant="primary"
				onClick={ save }
				isBusy={ isSaving }
				disabled={ isDisabled }
				aria-disabled={ isDisabled }
				className="edit-site-save-hub__button"
				// @ts-ignore No types for this exist yet.
				__next40pxDefaultSize
			>
				{ label }
			</Button>
		);
	};

	return (
		<HStack className="edit-site-save-hub" alignment="right" spacing={ 4 }>
			{ renderButton() }
		</HStack>
	);
};
