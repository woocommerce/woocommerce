/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { dispatch, useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { store as commandsStore } from '@wordpress/commands';

// Hook to add tracking to non-WooCommerce commands.
export const useAddTrackingToExternalCommands = ( origin ) => {
	const commandsWithTracking = useRef( [] );
	const { commands, commandLoaders } = useSelect( ( select ) => {
		const { getCommands, getCommandLoaders } = select( commandsStore );
		return {
			commands: getCommands(),
			commandLoaders: getCommandLoaders(),
		};
	}, [] );

	useEffect( () => {
		commands.forEach( ( command ) => {
			if ( ! commandsWithTracking.current.includes( command.name ) ) {
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
				commandsWithTracking.current.push( command.name );
			}
		} );
		commandLoaders.forEach( ( commandLoader ) => {
			if (
				! commandsWithTracking.current.includes( commandLoader.name )
			) {
				dispatch( commandsStore ).registerCommandLoader( {
					...commandLoader,
					hook: ( ...args ) => {
						const commandLoaderProps = commandLoader.hook(
							...args
						);
						const loaderCommandsWithTracking =
							commandLoaderProps.commands.map( ( command ) => {
								return {
									...command,
									callback: ( ...callbackArgs ) => {
										recordEvent(
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
							commands: loaderCommandsWithTracking,
						};
					},
				} );
				commandsWithTracking.current.push( commandLoader.name );
			}
		} );
	}, [ commands, commandLoaders, origin ] );
};
