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
import { closeSmall, upload, check, warning } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
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
import { recordEvent } from '@woocommerce/tracks';
import { createInterpolateElement } from '@wordpress/element';
import { getAdminLink } from '@woocommerce/settings';

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
		throw new Error(
			__( 'Invalid JSON format: Expected an array.', 'woocommerce' )
		);
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
			throw new Error(
				__( 'Invalid JSON format: Expected an array.', 'woocommerce' )
			);
		}

		const MAX_STEP_SIZE_BYTES =
			window?.wcSettings?.admin?.blueprint_max_step_size_bytes ||
			50 * 1024 * 1024; // defaults to 50MB

		let sessionToken = '';
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
							message: sprintf(
								/* translators: 1: Maximum size in MB, 2: Current size in MB */ __(
									'Step exceeds maximum size limit of %1$.2fMB (Current: %2$.2fMB)',
									'woocommerce'
								),
								(
									MAX_STEP_SIZE_BYTES /
									( 1024 * 1024 )
								).toFixed( 2 ),
								( stepSize / ( 1024 * 1024 ) ).toFixed( 2 )
							),
						},
					],
				} );
				continue; // Skip this step
			}
			const response = await apiFetch< Response >( {
				path: 'wc-admin/blueprint/import-step',
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-Blueprint-Import-Session': sessionToken,
				},
				body: stepJson,
				parse: false,
			} );

			const data: BlueprintImportStepResponse = await response.json();

			if ( ! data.success ) {
				errors.push( {
					step: step.step,
					messages: data.messages,
				} );
			}

			if ( ! sessionToken ) {
				sessionToken =
					response.headers.get( 'X-Blueprint-Import-Session' ) ?? '';
			}
		}

		if ( errors.length > 0 ) {
			dispatch( 'core/notices' ).createWarningNotice(
				`${ __(
					'Your Blueprint has been imported, but there were some errors. Please check the messages.',
					'woocommerce'
				) }`,
				{
					icon: <Icon icon={ warning } size={ 24 } fill="#d63638" />,
					explicitDismiss: true,
				}
			);
		} else {
			dispatch( 'core/notices' ).createSuccessNotice(
				`${ __( 'Your Blueprint has been imported!', 'woocommerce' ) }`,
				{
					icon: <Icon icon={ check } size={ 24 } fill="#1ed15A" />,
					explicitDismiss: true,
				}
			);
		}
		return errors;
	} catch ( e ) {
		throw e;
	}
};

const checkImportAllowed = async (): Promise< boolean > => {
	try {
		const response = await apiFetch< { import_allowed: boolean } >( {
			path: 'wc-admin/blueprint/import-allowed',
			method: 'GET',
		} );
		return response.import_allowed;
	} catch ( error ) {
		throw new Error(
			__( 'Failed to check if imports are allowed.', 'woocommerce' )
		);
	}
};

interface FileUploadContext {
	file?: File;
	steps?: BlueprintStep[];
	error?: Error;
	settings_to_overwrite?: string[];
	import_allowed?: boolean;
}

type FileUploadEvents =
	| { type: 'UPLOAD'; file: File }
	| { type: 'SUCCESS' }
	| { type: 'ERROR'; error: Error }
	| { type: 'DISMISS_FILE_UPLOAD' }
	| { type: 'DISMISS_OVERWRITE_MODAL' }
	| { type: 'IMPORT' }
	| { type: 'CONFIRM_IMPORT' }
	| { type: 'RETRY' }
	| { type: 'DISMISS_ERRORS' }
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
		reportError: assign( ( { event } ) => {
			recordEvent( 'blueprint_import_error' );

			const error = new Error(
				// default error message if no error is provided
				__(
					'An error occurred while importing your Blueprint.',
					'woocommerce'
				)
			);

			if ( 'error' in event ) {
				error.message = event.error.message;
			} else if ( 'output' in event && 'message' in event.output ) {
				error.message = event.output.message;
			}

			return {
				error,
			};
		} ),
	},
	actors: {
		importer: fromPromise(
			( { input }: { input: { steps: BlueprintStep[] } } ) =>
				importBlueprint( input.steps )
		),
		stepsParser: fromPromise( ( { input }: { input: { file: File } } ) =>
			parseBlueprintSteps( input.file )
		),
		importAllowedChecker: fromPromise( () => checkImportAllowed() ),
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
	initial: 'checkingImportAllowed',
	context: () => ( {} ),
	states: {
		checkingImportAllowed: {
			invoke: {
				src: 'importAllowedChecker',
				onDone: {
					target: 'idle',
					actions: assign( {
						import_allowed: ( { event } ) => event.output,
						error: () => undefined,
					} ),
				},
				onError: {
					target: 'error',
					actions: assign( {
						error: ( { event } ) => event.error as Error,
					} ),
				},
			},
		},
		idle: {
			on: {
				UPLOAD: {
					target: 'parsingSteps',
					guard: ( { context } ) => context.import_allowed === true,
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
							/* translators: Error message when the file is not a valid Blueprint. */
							__(
								'Error reading or parsing file. Please check the schema.',
								'woocommerce'
							)
						),
					} ),
				},
			},
		},
		success: {
			on: {
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

											return sprintf(
												/* translators: 1: Step name 2: Error messages */
												__(
													'Step: %1$s Errors: %2$s',
													'woocommerce'
												),
												item.step,
												errors
											);
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
		importSuccess: {
			entry: ( { context } ) => {
				recordEvent( 'blueprint_import_success', {
					has_partial_errors: Boolean(
						context.error?.name === 'BlueprintImportError'
					),
					steps_count: context.steps?.length || 0,
				} );
			},
			always: 'idle',
		},
	},
	on: {
		DISMISS_FILE_UPLOAD: {
			actions: assign( {
				error: () => undefined,
				file: () => undefined,
				steps: () => undefined,
			} ),
			target: '.idle',
		},
	},
} );

export const BlueprintUploadDropzone = () => {
	const [ state, send ] = useMachine( fileUploadMachine );

	return (
		<>
			{ state.matches( 'checkingImportAllowed' ) && (
				<div className="blueprint-upload-form">
					<div className="blueprint-upload-dropzone-uploading">
						<Spinner />
					</div>
				</div>
			) }
			{ state.context.import_allowed === false &&
				! state.context.error && (
					<Notice
						status="warning"
						isDismissible={ false }
						className="blueprint-upload-dropzone-notice"
					>
						{ createInterpolateElement(
							__(
								'Blueprint imports are disabled by default for live sites. <br/>Enable <link>Coming Soon mode</link> or define "ALLOW_BLUEPRINT_IMPORT_IN_LIVE_MODE" as true.',
								'woocommerce'
							),
							{
								br: <br />,
								link: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
									<a
										href={ getAdminLink(
											'admin.php?page=wc-settings&tab=site-visibility'
										) }
									/>
								),
							}
						) }
					</Notice>
				) }
			{ state.context.error && (
				<div className="blueprint-upload-dropzone-error">
					<Notice
						status="error"
						onDismiss={ () =>
							send( { type: 'DISMISS_FILE_UPLOAD' } )
						}
					>
						<pre>{ state.context.error.message }</pre>
					</Notice>
				</div>
			) }
			{ state.context.import_allowed &&
				( state.matches( 'idle' ) ||
					state.matches( 'error' ) ||
					state.matches( 'parsingSteps' ) ) && (
					<div className="blueprint-upload-form wc-settings-prevent-change-event">
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
								<p className="blueprint-upload-max-size">
									{ __(
										'Maximum size: 50 MB',
										'woocommerce'
									) }
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
							onClick={ () => {
								send( { type: 'DISMISS_FILE_UPLOAD' } );
							} }
						/>
					</p>
				</div>
			) }
			{ ( state.matches( 'success' ) ||
				state.matches( 'overrideModal' ) ) && (
				<Button
					className="woocommerce-blueprint-import-button"
					variant="primary"
					disabled={ ! state.context.import_allowed }
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
