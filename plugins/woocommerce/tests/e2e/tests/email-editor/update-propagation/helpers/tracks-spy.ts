/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { createClient } from '@woocommerce/e2e-utils-playwright';
import { expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { admin } from '../../../../test-data/data';
import playwrightConfig from '../../../../playwright.config';
import { TEST_HELPER_API_BASE } from './classifications';
import { enableTracksLog, disableTracksLog } from './test-helper-plugin';

const baseURL = playwrightConfig.use?.baseURL ?? '';

export type TracksEvent = {
	name: string;
	properties: Record< string, unknown >;
	timestamp_ms: number;
};

export interface TracksSpy {
	drain(): Promise< TracksEvent[] >;
	expectFired( name: string, count?: number ): Promise< void >;
	expectNotFired( name: string ): Promise< void >;
	reset(): Promise< void >;
}

declare global {
	interface Window {
		__capturedTracksEvents?: TracksEvent[];
		wcTracks?: {
			recordEvent?: (
				name: string,
				properties?: Record< string, unknown >
			) => void;
		};
	}
}

function apiClient() {
	return createClient( baseURL, {
		type: 'basic',
		username: admin.username,
		password: admin.password,
	} );
}

/**
 * Attach a client+server Tracks spy to a Page. The client-side hook patches
 * `window.wcTracks.recordEvent` (the dispatch target used by `@woocommerce/tracks`)
 * to capture events as they fire. The server-side mirror reads the
 * Tracks_Recorder log via the test-helper plugin's REST endpoint.
 *
 * `drain()` merges and dedupes both buffers; tests assert against the merged set.
 */
export async function attachTracksSpy( page: Page ): Promise< TracksSpy > {
	await enableTracksLog();

	await page.addInitScript( () => {
		window.__capturedTracksEvents = [];

		const installPatch = (): boolean => {
			if (
				! window.wcTracks ||
				typeof window.wcTracks.recordEvent !== 'function'
			) {
				return false;
			}
			const original = window.wcTracks.recordEvent;
			window.wcTracks.recordEvent = function (
				name: string,
				properties?: Record< string, unknown >
			) {
				try {
					window.__capturedTracksEvents!.push( {
						name,
						properties: properties ?? {},
						timestamp_ms: Date.now(),
					} );
				} catch {}
				return original.call( this, name, properties );
			};
			return true;
		};

		if ( ! installPatch() ) {
			document.addEventListener( 'DOMContentLoaded', () => {
				installPatch();
			} );
		}
	} );

	// addInitScript only instruments future documents. Patch the currently-loaded
	// page too — idempotent via a __wcSpyWrapped guard so a second attachTracksSpy
	// call (or a same-document re-attach) doesn't double-wrap.
	await page.evaluate( () => {
		window.__capturedTracksEvents = window.__capturedTracksEvents ?? [];
		if (
			! window.wcTracks ||
			typeof window.wcTracks.recordEvent !== 'function'
		) {
			return;
		}
		const current = window.wcTracks.recordEvent as ( (
			...args: unknown[]
		) => unknown ) & { __wcSpyWrapped?: boolean };
		if ( current.__wcSpyWrapped ) {
			return;
		}
		const original = current;
		const wrapped = function (
			name: string,
			properties?: Record< string, unknown >
		) {
			try {
				window.__capturedTracksEvents!.push( {
					name,
					properties: properties ?? {},
					timestamp_ms: Date.now(),
				} );
			} catch {}
			return original.call( this, name, properties );
		};
		(
			wrapped as typeof wrapped & { __wcSpyWrapped: boolean }
		 ).__wcSpyWrapped = true;
		window.wcTracks.recordEvent =
			wrapped as typeof window.wcTracks.recordEvent;
	} );

	const drain = async (): Promise< TracksEvent[] > => {
		const clientEvents = await page.evaluate( () => {
			const events = window.__capturedTracksEvents ?? [];
			window.__capturedTracksEvents = [];
			return events;
		} );

		const client = apiClient();
		const serverRes = await client.get(
			`${ TEST_HELPER_API_BASE }/tracks`
		);
		const serverEvents = ( serverRes?.data?.events ?? [] ) as TracksEvent[];
		await client.delete( `${ TEST_HELPER_API_BASE }/tracks`, {} );

		const seen = new Set< string >();
		const merged: TracksEvent[] = [];
		for ( const evt of [ ...clientEvents, ...serverEvents ] ) {
			const key = `${ evt.name }|${ evt.timestamp_ms }`;
			if ( seen.has( key ) ) {
				continue;
			}
			seen.add( key );
			merged.push( evt );
		}
		merged.sort( ( a, b ) => a.timestamp_ms - b.timestamp_ms );
		return merged;
	};

	return {
		drain,
		expectFired: async ( name: string, count?: number ) => {
			const events = await drain();
			const matches = events.filter( ( e ) => e.name === name );
			if ( count !== undefined ) {
				expect( matches.length ).toBe( count );
			} else {
				expect( matches.length ).toBeGreaterThan( 0 );
			}
		},
		expectNotFired: async ( name: string ) => {
			const events = await drain();
			expect( events.filter( ( e ) => e.name === name ).length ).toBe(
				0
			);
		},
		reset: async () => {
			await page.evaluate( () => {
				window.__capturedTracksEvents = [];
			} );
			const client = apiClient();
			await client.delete( `${ TEST_HELPER_API_BASE }/tracks`, {} );
		},
	};
}

export async function detachTracksSpy(): Promise< void > {
	await disableTracksLog();
}
