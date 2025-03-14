/**
 * External dependencies
 */
import {
	DropZone,
	FormFileUpload,
	Notice,
	Spinner,
	Button,
	Icon,
} from '@wordpress/components';
import { closeSmall, upload } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { useMachine } from '@xstate5/react';
import {
	assertEvent,
	assign,
	enqueueActions,
	fromPromise,
	setup,
} from 'xstate5';
import apiFetch from '@wordpress/api-fetch';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './style.scss';
import { OverwriteConfirmationModal } from '../settings/overwrite-confirmation-modal';
import { getOptionGroupsFromSteps } from './get-option-groups';
import {
	BlueprintQueueResponse,
	BlueprintImportResponse,
	BlueprintStep,
	BlueprintImportStepResponse,
} from './types';

const parseBlueprintSteps = async ( file: File ) => {
	// Create a FileReader instance
	const reader = new FileReader();

	// Create a promise to handle async file reading
	const fileContent: string = await new Promise( ( resolve, reject ) => {
		reader.onload = () => resolve( reader.result as string );
		reader.onerror = () => reject( reader.error );
		reader.readAsText( file );
	} );

	// Parse the file content as JSON
	const steps = JSON.parse( fileContent ).steps;

	// Ensure the parsed data is an array
	if ( ! Array.isArray( steps ) ) {
		throw new Error( 'Invalid JSON format: Expected an array.' );
	}

	return steps;
};

const importBlueprint = async ( steps: BlueprintStep[] ) => {
	const errors = [] as {
		step: string;
		messages: {
			step: string;
			type: string;
			message: string;
		}[];
	}[];

	try {
		// Ensure the parsed data is an array
		if ( ! Array.isArray( steps ) ) {
			throw new Error( 'Invalid JSON format: Expected an array.' );
		}

		const MAX_STEP_SIZE_BYTES =
			window?.wcSettings?.admin?.blueprint_max_step_size_bytes ||
			50 * 1024 * 1024; // defaults to 50MB

		// Loop through each step and send it to the endpoint
		for ( const step of steps ) {
			const stepJson = JSON.stringify( {
				step_definition: step,
			} );
			const stepSize = new Blob( [ stepJson ] ).size;
			if ( stepSize > MAX_STEP_SIZE_BYTES ) {
				errors.push( {
					step: step.step,
					messages: [
						{
							step: step.step,
							type: 'error',
							message: `Step exceeds maximum size limit of ${ (
								MAX_STEP_SIZE_BYTES /
								( 1024 * 1024 )
							).toFixed( 2 ) }MB (Current: ${ (
								stepSize /
								( 1024 * 1024 )
							).toFixed( 2 ) }MB)`,
						},
					],
				} );
				continue; // Skip this step
			}
			const response = await apiFetch< BlueprintImportStepResponse >( {
				path: 'wc-admin/blueprint/import-step',
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: stepJson,
			} );

			if ( ! response.success ) {
				errors.push( {
					step: step.step,
					messages: response.messages,
				} );
			}
		}

		let errorMessage;
		if ( errors.length > 0 ) {
			errorMessage = `${ __(
				'Your Blueprint has been imported, but there were some errors. Please check the messages.',
				'woocommerce'
			) }`;
		} else {
			errorMessage = `${ __(
				'Your Blueprint has been imported!',
				'woocommerce'
			) }`;
		}
		dispatch( 'core/notices' ).createSuccessNotice( errorMessage );
		return errors;
	} catch ( e ) {
		throw new Error( 'Error reading or parsing file' );
	}
};

interface FileUploadContext {
	file?: File;
	steps?: BlueprintStep[];
	error?: Error;
	settings_to_overwrite?: string[];
}

type FileUploadEvents =
	| { type: 'UPLOAD'; file: File }
	| { type: 'SUCCESS' }
	| { type: 'ERROR'; error: Error }
	| { type: 'DISMISS' }
	| { type: 'DISMISS_OVERWRITE_MODAL' }
	| { type: 'IMPORT' }
	| { type: 'CONFIRM_IMPORT' }
	| { type: 'RETRY' }
	| {
			type: `xstate.done.actor.${ number }.fileUpload.uploading`;
			output: BlueprintQueueResponse;
	  }
	| {
			type: `xstate.done.actor.${ number }.fileUpload.importer`;
			output: BlueprintImportResponse;
	  }
	| {
			type: `xstate.error.actor.${ number }.fileUpload.uploading`;
			output: Error;
	  }
	| {
			type: `xstate.error.actor.${ number }.fileUpload.importer`;
			output: Error;
	  };

export const fileUploadMachine = setup( {
	types: {} as {
		context: FileUploadContext;
		events: FileUploadEvents;
	},
	actions: {
		reportSuccess: enqueueActions( ( { event, enqueue } ) => {
			assertEvent( event, 'xstate.done.actor.0.fileUpload.uploading' );
			enqueue.assign( {
				settings_to_overwrite: event.output.settings_to_overwrite,
			} );
		} ),
		reportError: ( { event } ) => {
			if ( event.type === 'ERROR' ) {
				return assign( {
					error: event.error,
				} );
			} else if (
				event.type === 'xstate.error.actor.0.fileUpload.uploading' ||
				event.type === 'xstate.error.actor.0.fileUpload.importer'
			) {
				return assign( {
					error: event.output,
				} );
			}
		},
	},
	actors: {
		importer: fromPromise(
			( { input }: { input: { steps: BlueprintStep[] } } ) =>
				importBlueprint( input.steps )
		),
		stepsParser: fromPromise( ( { input }: { input: { file: File } } ) =>
			parseBlueprintSteps( input.file )
		),
	},
	guards: {
		hasSettingsToOverwrite: ( { context } ) =>
			Boolean(
				context.settings_to_overwrite &&
					context.settings_to_overwrite.length > 0
			),
	},
} ).createMachine( {
	id: 'fileUpload',
	initial: 'idle',
	context: () => ( {} ),
	states: {
		idle: {
			on: {
				UPLOAD: {
					target: 'parsingSteps',
					actions: assign( {
						file: ( { event } ) => event.file,
						error: () => undefined,
					} ),
				},
				ERROR: {
					target: 'error',
					actions: assign( {
						error: ( { event } ) => event?.error as Error,
					} ),
				},
			},
		},
		error: {
			entry: 'reportError',
			always: {
				target: 'idle',
			},
		},
		parsingSteps: {
			invoke: {
				src: 'stepsParser',
				input: ( { context } ) => {
					return {
						file: context.file!,
					};
				},
				onDone: {
					target: 'success',
					actions: assign( {
						error: () => undefined,
						steps: ( { event } ) => event.output,
						settings_to_overwrite: ( { event } ) => {
							return getOptionGroupsFromSteps(
								event.output
							) as string[];
						},
					} ),
				},
				onError: {
					target: 'error',
					actions: assign( {
						error: new Error(
							'Error reading or parsing file. Please check the schema.'
						),
					} ),
				},
			},
		},
		success: {
			on: {
				DISMISS: {
					actions: assign( {
						error: () => undefined,
						file: () => undefined,
						steps: () => undefined,
					} ),
					target: 'idle',
				},
				IMPORT: [
					{
						target: 'overrideModal',
					},
				],
			},
		},
		overrideModal: {
			on: {
				CONFIRM_IMPORT: {
					target: 'importing',
				},
				DISMISS_OVERWRITE_MODAL: {
					target: 'success',
				},
			},
		},
		importing: {
			invoke: {
				src: 'importer',
				input: ( { context } ) => {
					return {
						steps: context.steps!,
					};
				},
				onDone: {
					target: 'importSuccess',
					actions: assign( {
						error: ( { event } ) => {
							if (
								Array.isArray( event.output ) &&
								event.output.length
							) {
								return {
									name: 'BlueprintImportError',
									message: event.output
										.map( ( item ) => {
											const step = `step: ${ item.step }`;
											const errors = item.messages
												.filter(
													( msg ) =>
														msg.type === 'error'
												) // Filter messages with type 'error'
												.map(
													( msg ) =>
														`  ${ msg.message.trim() }.`
												) // Trim and append a period
												.join( '\n' ); // Join messages with newlines
											return `${ step }\nerrors:\n${ errors }`;
										} )
										.join( '\n\n' ),
								};
							}
						},
					} ),
				},
				onError: {
					target: 'error',
				},
			},
		},
		importSuccess: {},
	},
} );

export const BlueprintUploadDropzone = () => {
	const [ state, send ] = useMachine( fileUploadMachine );

	return (
		<>
			{ state.context.error && (
				<div className="blueprint-upload-dropzone-error">
					<Notice status="error" isDismissible={ false }>
						<pre>{ state.context.error.message }</pre>
					</Notice>
				</div>
			) }
			{ ( state.matches( 'idle' ) || state.matches( 'error' ) ) && (
				<div className="blueprint-upload-form">
					<FormFileUpload
						className="blueprint-upload-field"
						accept="application/json, application/zip"
						multiple={ false }
						onChange={ ( evt ) => {
							const file = evt.target.files?.[ 0 ]; // since multiple is disabled it has to be in 0
							if ( file ) {
								send( { type: 'UPLOAD', file } );
							}
						} }
					>
						<div className="blueprint-upload-dropzone">
							<Icon icon={ upload } />
							<p className="blueprint-upload-dropzone-text">
								{ __( 'Drag and drop or ', 'woocommerce' ) }
								<span>
									{ __( 'choose a file', 'woocommerce' ) }
								</span>
							</p>
							<DropZone
								onFilesDrop={ ( files ) => {
									if ( files.length > 1 ) {
										send( {
											type: 'ERROR',
											error: new Error(
												'Only one file can be uploaded at a time'
											),
										} );
									}
									send( {
										type: 'UPLOAD',
										file: files[ 0 ],
									} );
								} }
							></DropZone>
						</div>
					</FormFileUpload>
				</div>
			) }
			{ state.matches( 'importing' ) && (
				<div className="blueprint-upload-form">
					<div className="blueprint-upload-dropzone-uploading">
						<Spinner className="blueprint-upload-dropzone-spinner" />
						<p className="blueprint-upload-dropzone-text">
							{ __( 'Importing your file…', 'woocommerce' ) }
						</p>
					</div>
				</div>
			) }
			{ ( state.matches( 'success' ) ||
				state.matches( 'importSuccess' ) ||
				state.matches( 'overrideModal' ) ) && (
				<div className="blueprint-upload-dropzone-success">
					<p className="blueprint-upload-dropzone-text">
						<span className="blueprint-upload-dropzone-text-file-name">
							{ state.context.file?.name }
						</span>
						<Button
							icon={ <Icon icon={ closeSmall } /> }
							onClick={ () => send( { type: 'DISMISS' } ) }
						/>
					</p>
				</div>
			) }
			{ ( state.matches( 'success' ) ||
				state.matches( 'overrideModal' ) ) && (
				<Button
					className="woocommerce-blueprint-import-button"
					variant="primary"
					onClick={ () => {
						send( { type: 'IMPORT' } );
					} }
				>
					{ __( 'Import', 'woocommerce' ) }
				</Button>
			) }
			{ ( state.matches( 'importing' ) ||
				state.matches( 'overrideModal' ) ) && (
				<OverwriteConfirmationModal
					isOpen={ true }
					isImporting={ state.matches( 'importing' ) }
					onClose={ () =>
						send( { type: 'DISMISS_OVERWRITE_MODAL' } )
					}
					onConfirm={ () => send( { type: 'CONFIRM_IMPORT' } ) }
					overwrittenItems={
						state.context.settings_to_overwrite || []
					}
				/>
			) }
		</>
	);
};
