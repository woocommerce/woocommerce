/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { dispatch, useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { store as commandsStore } from '@wordpress/commands';

// Hook to add tracking to non-WooCommerce commands.
export const useAddTrackingToExternalCommands = ( origin ) => {
	const originRef = useRef( origin );
	const commandsWithTracking = useRef( [] );
	const commandLoadersWithTracking = useRef( [] );
	const { commands, commandLoaders, isCommandPaletteOpen } = useSelect(
		( select ) => {
			const { getCommands, getCommandLoaders, isOpen } =
				select( commandsStore );
			return {
				commands: getCommands(),
				commandLoaders: getCommandLoaders(),
				isCommandPaletteOpen: isOpen(),
			};
		},
		[]
	);

	useEffect( () => {
		originRef.current = origin;
	}, [ origin ] );

	useEffect( () => {
		if ( ! isCommandPaletteOpen ) {
			commandsWithTracking.current = [];
			commandLoadersWithTracking.current = [];
		}
	}, [ isCommandPaletteOpen ] );

	useEffect( () => {
		commands.forEach( ( command ) => {
			if ( ! commandsWithTracking.current.includes( command.name ) ) {
				dispatch( commandsStore ).registerCommand( {
					...command,
					callback: ( ...args ) => {
						recordEvent( 'woocommerce_command_palette_submit', {
							name: command.name,
							label: command.label,
							origin: originRef.current,
						} );
						command.callback( ...args );
					},
				} );
				commandsWithTracking.current.push( command.name );
			}
		} );
		commandLoaders.forEach( ( commandLoader ) => {
			if (
				! commandLoadersWithTracking.current.includes(
					commandLoader.name
				)
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
												label:
													command.label ??
													command.name,
												origin: originRef.current,
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
				commandLoadersWithTracking.current.push( commandLoader.name );
			}
		} );
	}, [ commands, commandLoaders ] );
};
