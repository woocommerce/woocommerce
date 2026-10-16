/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, resolveSelect, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { NO_KEY } from './definition';
import { getScreen } from './screen';
import type { SettingsScreenConfig } from './screen';
import { ScreenContent } from './stage';

export type { SettingsScreenConfig };

/**
 * Create the route and stage for a settings screen that an extension registers.
 *
 * The extension's script module exports both, so it can be the route's route and content module.
 */
export function createSettingsScreen( config: SettingsScreenConfig ) {
	const screen = getScreen( config );
	const { kind, name } = screen.entity;

	const route = {
		loader: async () => {
			if ( ! select( coreStore ).getEntityConfig( kind, name ) ) {
				// Core's "Review changes" panel (Cmd/Ctrl+S) names the entity with `label` and `getTitle`.
				await dispatch( coreStore ).addEntities( [
					{
						...screen.entity,
						key: false,
						label: screen.title,
						getTitle: () => screen.title,
					},
				] );
			}
			// The stage shows load errors, so a failed request must not reject the route.
			// resolveSelect() is untyped in this version of @wordpress/data.
			const resolver = (
				resolveSelect as unknown as ( store: typeof coreStore ) => {
					getEntityRecord: (
						kind: string,
						name: string,
						key: string
					) => Promise< unknown >;
				}
			 )( coreStore );
			await resolver
				.getEntityRecord( kind, name, NO_KEY )
				.catch( () => undefined );
		},
	};

	const stage = () => <ScreenContent screen={ screen } />;

	return { route, stage };
}
