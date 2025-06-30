// Reference: https://github.com/WordPress/gutenberg/blob/v16.4.0/packages/edit-site/src/components/save-hub/index.js
/**
 * External dependencies
 */
import {
	useCallback,
	useContext,
	useEffect,
	useState,
} from '@wordpress/element';
import { useQuery } from '@woocommerce/navigation';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	__experimentalHStack as HStack,
	Button,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { store as coreStore } from '@wordpress/core-data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as noticesStore } from '@wordpress/notices';
// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntitiesSavedStatesIsDirty as useIsDirty } from '@wordpress/editor';
// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useIsSiteEditorLoading } from '@wordpress/edit-site/build-module/components/layout/hooks';

/**
 * Internal dependencies
 */
import { CustomizeStoreContext } from '../';
import { trackEvent } from '~/customize-store/tracking';
import { useEditorBlocks } from '../hooks/use-editor-blocks';
import { useIsNoBlocksPlaceholderPresent } from '../hooks/block-placeholder/use-is-no-blocks-placeholder-present';
import '../gutenberg-styles/save-hub.scss';

const PUBLISH_ON_SAVE_ENTITIES = [
	{
		kind: 'postType',
		name: 'wp_navigation',
	},
];
let shouldTriggerSave = true;

export const SaveHub = () => {
	const urlParams = useQuery();
	const { sendEvent } = useContext( CustomizeStoreContext );
	const [ isResolving, setIsResolving ] = useState< boolean >( false );

	const currentTemplateId: string | undefined = useSelect(
		( select ) =>
			select( coreStore ).getDefaultTemplateId( { slug: 'home' } ),
		[]
	);

	const [ blocks ] = useEditorBlocks(
		'wp_template',
		currentTemplateId || ''
	);

	const isNoBlocksPlaceholderPresent =
		useIsNoBlocksPlaceholderPresent( blocks );

	const isEditorLoading = useIsSiteEditorLoading();
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

	const {
		editEntityRecord,
		saveEditedEntityRecord,
		__experimentalSaveSpecifiedEntityEdits: saveSpecifiedEntityEdits,
	} = useDispatch( coreStore );

	const save = useCallback( async () => {
		for ( const { kind, name, key, property } of dirtyEntityRecords ) {
			if ( kind === 'root' && name === 'site' ) {
				await saveSpecifiedEntityEdits(
					'root',
					'site',
					undefined,
					[ property ],
					undefined
				);
			} else {
				if (
					PUBLISH_ON_SAVE_ENTITIES.some(
						( typeToPublish ) =>
							typeToPublish.kind === kind &&
							typeToPublish.name === name
					) &&
					typeof key !== 'undefined'
				) {
					editEntityRecord( kind, name, key, {
						status: 'publish',
					} );
				}

				await saveEditedEntityRecord( kind, name, key, undefined );
				__unstableMarkLastChangeAsPersistent();
			}
		}
	}, [
		dirtyEntityRecords,
		editEntityRecord,
		saveEditedEntityRecord,
		saveSpecifiedEntityEdits,
		__unstableMarkLastChangeAsPersistent,
	] );

	const isMainScreen = urlParams.path === '/customize-store/assembler-hub';

	// Trigger a save when the editor is loaded and there are unsaved changes in main screen. This is needed to ensure FE is displayed correctly because some patterns have dynamic attributes that only generate in Editor.
	useEffect( () => {
		if ( isEditorLoading ) {
			return;
		}

		if ( ! isMainScreen ) {
			shouldTriggerSave = false;
			return;
		}

		if ( shouldTriggerSave && isDirty ) {
			save();
			shouldTriggerSave = false;
		}
	}, [ isEditorLoading, isDirty, isMainScreen, save ] );

	const onDone = async () => {
		trackEvent( 'customize_your_store_assembler_hub_done_click' );
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

	if ( isMainScreen ) {
		return (
			<HStack
				className="woocommerce-edit-site-save-hub"
				alignment="right"
				spacing={ 4 }
			>
				<Button
					variant="primary"
					onClick={ onDone }
					className="woocommerce-edit-site-save-hub__button"
					disabled={
						isResolving ||
						isEditorLoading ||
						isNoBlocksPlaceholderPresent
					}
					aria-disabled={ isResolving }
					__next40pxDefaultSize
				>
					{ isResolving ? (
						<Spinner />
					) : (
						__( 'Finish customizing', 'woocommerce' )
					) }
				</Button>
			</HStack>
		);
	}

	return null;
};
