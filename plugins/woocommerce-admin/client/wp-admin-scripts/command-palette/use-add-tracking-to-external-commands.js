/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { dispatch, useSelect } from '@wordpress/data';
import { recordEvent, queueRecordEvent } from '@woocommerce/tracks';
import { store as commandsStore } from '@wordpress/commands';

// Hook to add tracking to non-WooCommerce commands.
export const useAddTrackingToExternalCommands = ( origin ) => {
	const wasTrackingAdded = useRef( false );
	const { commands, commandLoaders } = useSelect( ( select ) => {
		const { getCommands, getCommandLoaders } = select( commandsStore );
		return {
			commands: getCommands(),
			commandLoaders: getCommandLoaders(),
		};
	}, [] );

	useEffect( () => {
		if ( wasTrackingAdded.current === false ) {
			commands.forEach( ( command ) => {
				dispatch( commandsStore ).registerCommand( {
					...command,
					callback: ( ...args ) => {
						recordEvent( 'woocommerce_command_palette_submit', {
							name: command.name,
							origin,
						} );
						command.callback( ...args );
					},
				} );
			} );
			commandLoaders.forEach( ( commandLoader ) => {
				dispatch( commandsStore ).registerCommandLoader( {
					...commandLoader,
					hook: ( ...args ) => {
						const commandLoaderProps = commandLoader.hook(
							...args
						);
						const commandsWithTracking =
							commandLoaderProps.commands.map( ( command ) => {
								return {
									...command,
									callback: ( ...callbackArgs ) => {
										queueRecordEvent(
											'woocommerce_command_palette_submit',
											{
												name: commandLoader.name,
												title:
													command.label ??
													command.name,
												origin,
											}
										);
										command.callback( ...callbackArgs );
									},
								};
							} );
						return {
							...commandLoaderProps,
							commands: commandsWithTracking,
						};
					},
				} );
			} );
			wasTrackingAdded.current = true;
		}
	}, [ commands, commandLoaders, origin ] );
};
