/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import { StrictMode, useLayoutEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useListEntrance } from '../use-list-entrance';

const List = () => {
	const ref = useRef< HTMLUListElement | null >( null );
	const start = useListEntrance( ref );
	useLayoutEffect( () => start(), [ start ] );
	return <ul ref={ ref } data-testid="list" />;
};

describe( 'useListEntrance', () => {
	let finish: () => void;

	beforeEach( () => {
		// One running animation in the list. Like a browser animation, it reports `finished` once `finish` is called.
		let resolveFinished = () => undefined as void;
		const animation = {
			playState: 'running',
			finished: new Promise< void >( ( resolve ) => {
				resolveFinished = resolve;
			} ),
			effect: { getComputedTiming: () => ( { endTime: 200 } ) },
		};
		finish = () => {
			animation.playState = 'finished';
			resolveFinished();
		};
		Element.prototype.getAnimations = () => [
			animation as unknown as Animation,
		];
	} );

	afterEach( () => {
		// @ts-expect-error jsdom has no getAnimations.
		delete Element.prototype.getAnimations;
	} );

	it( 'marks the list until its entrances end', async () => {
		const { getByTestId } = render( <List /> );
		const list = getByTestId( 'list' );

		expect( list ).toHaveAttribute( 'data-wc-list-entering' );
		await act( async () => finish() );
		expect( list ).not.toHaveAttribute( 'data-wc-list-entering' );
	} );

	it( 'leaves no attribute behind when StrictMode replays its effects', async () => {
		const { getByTestId } = render(
			<StrictMode>
				<List />
			</StrictMode>
		);
		const list = getByTestId( 'list' );

		expect( list ).toHaveAttribute( 'data-wc-list-entering' );
		await act( async () => finish() );
		expect( list ).not.toHaveAttribute( 'data-wc-list-entering' );
	} );
} );
