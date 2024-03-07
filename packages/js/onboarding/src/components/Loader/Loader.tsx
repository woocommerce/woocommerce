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
			<div
				className={ classNames(
					'woocommerce-onboarding-loader-container',
					className
				) }
			>
				{ children }
			</div>
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
	shouldLoop = true,
	children,
	onChange = () => {},
}: {
	interval: number;
	shouldLoop?: boolean;
	onChange?: ( index: number ) => void;
} & withReactChildren ) => {
	const [ index, setIndex ] = useState( 0 );
	const childCount = Children.count( children );

	useEffect( () => {
		const rotateInterval = setInterval( () => {
			setIndex( ( prevIndex ) => {
				const nextIndex = prevIndex + 1;

				if ( shouldLoop ) {
					const updatedIndex = nextIndex % childCount;
					onChange( updatedIndex );
					return updatedIndex;
				}
				if ( nextIndex < childCount ) {
					onChange( nextIndex );
					return nextIndex;
				}
				clearInterval( rotateInterval );
				return prevIndex;
			} );
		}, interval );

		return () => clearInterval( rotateInterval );
	}, [ interval, children, shouldLoop, childCount ] );

	const childToDisplay = Children.toArray( children )[ index ];
	return <>{ childToDisplay }</>;
};

Loader.Sequence = LoaderSequence; // eslint rule-of-hooks can't handle the compound component definition directly
