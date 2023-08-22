/**
 * External dependencies
 */
import classNames from 'classnames';
import {
	useState,
	useEffect,
	Children,
	createElement,
	Fragment,
} from '@wordpress/element';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import ProgressBar from './ProgressBar';

export const Loader = ( {
	children,
	className,
}: {
	children: ReactNode;
	className?: string;
} ) => {
	return (
		<div
			className={ classNames(
				'woocommerce-onboarding-loader',
				className
			) }
		>
			{ children }
		</div>
	);
};

type withClassName = {
	className?: string;
};

type withReactChildren = {
	children: ReactNode;
};

Loader.Layout = ( {
	children,
	className,
}: withClassName & withReactChildren ) => {
	return (
		<div
			className={ classNames(
				'woocommerce-onboarding-loader-wrapper',
				className
			) }
		>
			{ children }
		</div>
	);
};

Loader.Illustration = ( { children }: withReactChildren ) => {
	return <>{ children }</>;
};

Loader.Title = ( {
	children,
	className,
}: withClassName & withReactChildren ) => {
	return (
		<h1
			className={ classNames(
				'woocommerce-onboarding-loader__title',
				className
			) }
		>
			{ children }
		</h1>
	);
};

Loader.ProgressBar = ( {
	progress,
	className,
}: { progress: number } & withClassName ) => {
	return (
		<ProgressBar
			className={ classNames( 'progress-bar', className ) }
			percent={ progress ?? 0 }
			color={ 'var(--wp-admin-theme-color)' }
			bgcolor={ '#E0E0E0' }
		/>
	);
};

Loader.Subtext = ( {
	children,
	className,
}: withReactChildren & withClassName ) => {
	return (
		<p
			className={ classNames(
				'woocommerce-onboarding-loader__paragraph',
				className
			) }
		>
			{ children }
		</p>
	);
};

const LoaderSequence = ( {
	interval,
	children,
}: { interval: number } & withReactChildren ) => {
	const [ index, setIndex ] = useState( 0 );

	useEffect( () => {
		const rotateInterval = setInterval( () => {
			setIndex(
				( prevIndex ) => ( prevIndex + 1 ) % Children.count( children )
			);
		}, interval );

		return () => clearInterval( rotateInterval );
	}, [ interval, children ] );

	const childToDisplay = Children.toArray( children )[ index ];
	return <>{ childToDisplay }</>;
};

Loader.Sequence = LoaderSequence; // eslint rule-of-hooks can't handle the compound component definition directly
