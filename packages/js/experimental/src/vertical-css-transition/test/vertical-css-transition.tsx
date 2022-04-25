/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement, createRef } from '@wordpress/element';
/**
 * Internal dependencies
 */
import {
	VerticalCSSTransition,
	VerticalCSSTransitionProps,
} from '../vertical-css-transition';

describe( 'VerticalCSSTransition', () => {
	const originalClientHeight = Object.getOwnPropertyDescriptor(
		HTMLElement.prototype,
		'clientHeight'
	);

	beforeEach( () => {
		Object.defineProperty( HTMLElement.prototype, 'clientHeight', {
			configurable: true,
			value: 100,
		} );
	} );

	afterEach( () => {
		if ( originalClientHeight ) {
			Object.defineProperty(
				HTMLElement.prototype,
				'offsetHeight',
				originalClientHeight
			);
		}
	} );

	it( 'should set maxHeight of children to container on entering and remove it when entered', ( done ) => {
		const nodeRef = createRef< undefined | HTMLDivElement >();
		let onEnteringCalledCount = 0;
		const props: VerticalCSSTransitionProps = {
			in: false,
			timeout: 0,
			nodeRef: nodeRef as React.RefObject< undefined >,
			classNames: 'test',
			onEntering: () => {
				onEnteringCalledCount++;
				expect(
					nodeRef.current &&
						nodeRef.current.parentElement?.style.maxHeight
				).toBe( '100px' );
			},
			onEntered: () => {
				expect(
					nodeRef.current &&
						nodeRef.current.parentElement?.style.maxHeight
				).toBe( '' );
				expect( onEnteringCalledCount ).toEqual( 1 );
				done();
			},
		};
		const { rerender } = render(
			<VerticalCSSTransition { ...props }>
				<div ref={ nodeRef as React.RefObject< HTMLDivElement > }>
					Test
				</div>
			</VerticalCSSTransition>
		);
		jest.runOnlyPendingTimers();

		rerender(
			<VerticalCSSTransition { ...props } in={ true }>
				<div ref={ nodeRef as React.RefObject< HTMLDivElement > }>
					Test
				</div>
			</VerticalCSSTransition>
		);
		jest.runOnlyPendingTimers();
	} );

	it( 'should update maxHeight when children are updated', ( done ) => {
		const nodeRef = createRef< undefined | HTMLDivElement >();
		let onEnteringCalledCount = 0;
		const props: VerticalCSSTransitionProps = {
			in: false,
			timeout: 0,
			nodeRef: nodeRef as React.RefObject< undefined >,
			classNames: 'test',
			onEntering: () => {
				onEnteringCalledCount++;
				expect(
					nodeRef.current &&
						nodeRef.current.parentElement?.style.maxHeight
				).toBe( '200px' );
			},
			onEntered: () => {
				expect(
					nodeRef.current &&
						nodeRef.current.parentElement?.style.maxHeight
				).toBe( '' );
				expect( onEnteringCalledCount ).toEqual( 1 );
				done();
			},
		};
		const { rerender } = render(
			<VerticalCSSTransition { ...props }>
				<div ref={ nodeRef as React.RefObject< HTMLDivElement > }>
					Test
				</div>
			</VerticalCSSTransition>
		);
		jest.runOnlyPendingTimers();

		rerender(
			<VerticalCSSTransition { ...props } in={ true }>
				<div ref={ nodeRef as React.RefObject< HTMLDivElement > }>
					Test
				</div>
				<div>New child</div>
			</VerticalCSSTransition>
		);
		expect(
			nodeRef.current && nodeRef.current.parentElement?.style.maxHeight
		).toBe( '200px' );
		jest.runOnlyPendingTimers();
	} );

	it( 'should set maxHeight to zero if in is set to false', () => {
		const nodeRef = createRef< undefined | HTMLDivElement >();
		render(
			<VerticalCSSTransition
				in={ false }
				timeout={ 0 }
				nodeRef={ nodeRef as React.RefObject< undefined > }
				classNames="test"
			>
				<div ref={ nodeRef as React.RefObject< HTMLDivElement > }>
					Test
				</div>
			</VerticalCSSTransition>
		);

		expect(
			nodeRef.current && nodeRef.current.parentElement?.style.maxHeight
		).toBe( '0' );
	} );

	it( 'should not set transition variables when not in transition', () => {
		const nodeRef = createRef< undefined | HTMLDivElement >();
		render(
			<VerticalCSSTransition
				in={ true }
				timeout={ 0 }
				nodeRef={ nodeRef as React.RefObject< undefined > }
				classNames="test"
			>
				<div ref={ nodeRef as React.RefObject< HTMLDivElement > }>
					Test
				</div>
			</VerticalCSSTransition>
		);

		expect(
			nodeRef.current &&
				nodeRef.current.parentElement?.style.transitionDuration
		).toBe( '' );
		expect(
			nodeRef.current &&
				nodeRef.current.parentElement?.style.transitionProperty
		).toBe( '' );
	} );

	it( 'should add transition style properties when in transition', ( done ) => {
		const nodeRef = createRef< undefined | HTMLDivElement >();
		render(
			<VerticalCSSTransition
				in={ true }
				appear
				timeout={ 0 }
				nodeRef={ nodeRef as React.RefObject< undefined > }
				classNames="test"
				onEntering={ () => {
					expect(
						nodeRef.current &&
							nodeRef.current.parentElement?.style
								.transitionDuration
					).toBe( '0ms' );
					expect(
						nodeRef.current &&
							nodeRef.current.parentElement?.style
								.transitionProperty
					).toBe( 'max-height' );
					done();
				} }
			>
				<div ref={ nodeRef as React.RefObject< HTMLDivElement > }>
					Test
				</div>
			</VerticalCSSTransition>
		);
	} );

	it( 'should still set css classes on enter transition', ( done ) => {
		const nodeRef = createRef< undefined | HTMLDivElement >();
		const props: VerticalCSSTransitionProps = {
			in: false,
			timeout: 0,
			nodeRef: nodeRef as React.RefObject< undefined >,
			classNames: 'test',
			onEntering: () => {
				expect(
					nodeRef.current &&
						nodeRef.current.classList.contains( 'test-enter' )
				).toEqual( true );
				expect(
					nodeRef.current &&
						nodeRef.current.classList.contains(
							'test-enter-active'
						)
				).toEqual( true );
				done();
			},
		};
		const { rerender } = render(
			<VerticalCSSTransition { ...props }>
				<div ref={ nodeRef as React.RefObject< HTMLDivElement > }>
					Test
				</div>
			</VerticalCSSTransition>
		);
		rerender(
			<VerticalCSSTransition { ...props } in={ true }>
				<div ref={ nodeRef as React.RefObject< HTMLDivElement > }>
					Test
				</div>
			</VerticalCSSTransition>
		);
	} );

	it( 'should still set css classes on exit transition', ( done ) => {
		const nodeRef = createRef< undefined | HTMLDivElement >();
		const props: VerticalCSSTransitionProps = {
			in: true,
			timeout: 0,
			nodeRef: nodeRef as React.RefObject< undefined >,
			classNames: 'test',
			onExiting: () => {
				expect(
					nodeRef.current &&
						nodeRef.current.classList.contains( 'test-exit' )
				).toEqual( true );
				expect(
					nodeRef.current &&
						nodeRef.current.classList.contains( 'test-exit-active' )
				).toEqual( true );
				done();
			},
		};
		const { rerender } = render(
			<VerticalCSSTransition { ...props }>
				<div ref={ nodeRef as React.RefObject< HTMLDivElement > }>
					Test
				</div>
			</VerticalCSSTransition>
		);
		rerender(
			<VerticalCSSTransition { ...props } in={ false }>
				<div ref={ nodeRef as React.RefObject< HTMLDivElement > }>
					Test
				</div>
			</VerticalCSSTransition>
		);
	} );

	describe( 'defaultStyle', () => {
		it( 'should overwrite default style when passed in', ( done ) => {
			const nodeRef = createRef< undefined | HTMLDivElement >();
			render(
				<VerticalCSSTransition
					in={ true }
					appear
					timeout={ 0 }
					nodeRef={ nodeRef as React.RefObject< undefined > }
					classNames="test"
					defaultStyle={ {
						transitionProperty: 'max-height, opacity',
					} }
					onEntering={ () => {
						expect(
							nodeRef.current &&
								nodeRef.current.parentElement?.style
									.transitionProperty
						).toBe( 'max-height, opacity' );
						done();
					} }
				>
					<div ref={ nodeRef as React.RefObject< HTMLDivElement > }>
						Test
					</div>
				</VerticalCSSTransition>
			);
		} );
	} );
} );
