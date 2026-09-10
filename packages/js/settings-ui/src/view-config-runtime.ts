/**
 * External dependencies
 */
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { useViewConfig } from '@wordpress/views';

const VIEW_CONFIG_TIMEOUT_MS = 5000;

export type ViewConfigRuntimeResult =
	| { status: 'loading' }
	| { status: 'failed'; error: unknown }
	| { status: 'resolved'; config: unknown };

type ResolutionSelectors = {
	hasFinishedResolution: (
		selectorName: string,
		args: readonly unknown[]
	) => boolean;
	getResolutionError: (
		selectorName: string,
		args: readonly unknown[]
	) => unknown;
};

/**
 * Keep the WordPress View Config and resolution APIs private so callers only
 * depend on terminal states that WooCommerce controls.
 */
export const useViewConfigRuntime = ( {
	kind,
	name,
}: {
	kind: string;
	name: string;
} ): ViewConfigRuntimeResult => {
	const config = useViewConfig( { kind, name } );
	const { hasFinished, resolutionError } = useSelect(
		( select ) => {
			const selectors = select(
				coreStore
			) as unknown as ResolutionSelectors;
			const args = [ kind, name ] as const;

			return {
				hasFinished: selectors.hasFinishedResolution(
					'getViewConfig',
					args
				),
				resolutionError: selectors.getResolutionError(
					'getViewConfig',
					args
				),
			};
		},
		[ kind, name ]
	);
	const identity = `${ kind }\u0000${ name }`;
	const [ timedOutIdentity, setTimedOutIdentity ] = useState< string >();
	const hasTimedOut = timedOutIdentity === identity;

	useEffect( () => {
		if ( hasFinished || hasTimedOut || resolutionError ) {
			return undefined;
		}

		const timeout = window.setTimeout(
			() => setTimedOutIdentity( identity ),
			VIEW_CONFIG_TIMEOUT_MS
		);

		return () => window.clearTimeout( timeout );
	}, [ hasFinished, hasTimedOut, identity, resolutionError ] );

	return useMemo( () => {
		if ( resolutionError ) {
			return { status: 'failed', error: resolutionError };
		}

		if ( hasTimedOut ) {
			return {
				status: 'failed',
				error: new Error( 'view_config_timeout' ),
			};
		}

		if ( ! hasFinished ) {
			return { status: 'loading' };
		}

		return { status: 'resolved', config };
	}, [ config, hasFinished, hasTimedOut, resolutionError ] );
};
