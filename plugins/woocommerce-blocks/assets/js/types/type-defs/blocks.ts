/**
 * External dependencies
 */
import { LazyExoticComponent } from 'react';

export type RegisteredBlockComponent =
	| LazyExoticComponent< React.ComponentType< unknown > >
	| ( () => JSX.Element | null )
	| null;
