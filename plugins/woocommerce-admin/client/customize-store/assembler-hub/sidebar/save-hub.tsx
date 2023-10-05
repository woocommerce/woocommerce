// Reference: https://github.com/WordPress/gutenberg/blob/v16.4.0/packages/edit-site/src/components/save-hub/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useContext, useState } from '@wordpress/element';
import { useQuery } from '@woocommerce/navigation';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	// @ts-ignore No types for this exist yet.
	__experimentalHStack as HStack,
	// @ts-ignore No types for this exist yet.
	__experimentalUseNavigator as useNavigator,
	Button,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
// @ts-ignore No types for this exist yet.
import { store as blockEditorStore } from '@wordpress/block-editor';
// @ts-ignore No types for this exist yet.
import { store as noticesStore } from '@wordpress/notices';
// @ts-ignore No types for this exist yet.
import { useEntitiesSavedStatesIsDirty as useIsDirty } from '@wordpress/editor';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { CustomizeStoreContext } from '../';
import { HighlightedBlockContext } from '../context/highlighted-block-context';

const PUBLISH_ON_SAVE_ENTITIES = [
	{
		kind: 'postType',
		name: 'wp_navigation',
	},
];

export const SaveHub = () => {
	const urlParams = useQuery();
	const { sendEvent } = useContext( CustomizeStoreContext );
	const [ isResolving, setIsResolving ] = useState< boolean >( false );
	const navigator = useNavigator();
	const { resetHighlightedBlockIndex } = useContext(
		HighlightedBlockContext
	);
	// @ts-ignore No types for this exist yet.
	const { __unstableMarkLastChangeAsPersistent } =
		useDispatch( blockEditorStore );

	const { createErrorNotice } = useDispatch( noticesStore );

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

	const save = async () => {
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
	};

	const onClickSaveButton = async () => {
		const source = `${ urlParams.path.replace(
			'/customize-store/assembler-hub/',
			''
		) }`;
		recordEvent( 'customize_your_store_assembler_hub_save_click', {
			source,
		} );

		try {
			await save();
			resetHighlightedBlockIndex();
			navigator.goToParent();
		} catch ( error ) {
			createErrorNotice(
				`${ __( 'Saving failed.', 'woocommerce' ) } ${ error }`
			);
		}
	};

	const onDone = async () => {
		recordEvent( 'customize_your_store_assembler_hub_done_click' );
		setIsResolving( true );

		try {
			await save();
			sendEvent( 'FINISH_CUSTOMIZATION' );
		} catch ( error ) {
			createErrorNotice(
				`${ __( 'Saving failed.', 'woocommerce' ) } ${ error }`
			);
			setIsResolving( false );
		}
	};

	const renderButton = () => {
		if ( urlParams.path === '/customize-store/assembler-hub' ) {
			return (
				<Button
					variant="primary"
					onClick={ onDone }
					className="edit-site-save-hub__button"
					// @ts-ignore No types for this exist yet.
					__next40pxDefaultSize
				>
					{ isResolving ? <Spinner /> : __( 'Done', 'woocommerce' ) }
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
				onClick={ onClickSaveButton }
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
