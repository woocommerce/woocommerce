/**
 * External dependencies
 */
import classNames from 'classnames';
import { useState, useEffect } from '@wordpress/element';
/**
 * Internal dependencies
 */
import './style.scss';
import { CoreProfilerStateMachineContext } from '../..';
import ProgressBar from '../progress-bar/progress-bar';
import { useStages } from './useStages';
import { Image } from './image';

export const Loader = ( {
	context,
}: {
	context: CoreProfilerStateMachineContext;
} ) => {
	const stages = useStages( context.loader.stagesFor );
	const currentStage = stages[ context.loader.currentStage ?? 0 ];
	const [ currentParagraph, setCurrentParagraph ] = useState( 0 );

	useEffect( () => {
		const interval = setInterval( () => {
			currentStage.paragraphs[ currentParagraph + 1 ]
				? setCurrentParagraph(
						( currentParagraph ) => currentParagraph + 1
				  )
				: setCurrentParagraph( 0 );
		}, currentStage.paragraphs[ currentParagraph ]?.duration ?? 3000 );

		return () => clearInterval( interval );
	}, [ currentParagraph, currentStage.paragraphs ] );

	return (
		<div
			className={ classNames(
				'woocommerce-profiler-loader',
				context.loader.className
			) }
		>
			<Image imageName={ currentStage.image } />

			<h1 className="woocommerce-profiler-loader__title">
				{ currentStage.title }
			</h1>
			<ProgressBar
				className={ 'progress-bar' }
				percent={ currentStage.progress }
				color={ 'var(--wp-admin-theme-color)' }
				bgcolor={ '#E0E0E0' }
			/>
			<p className="woocommerce-profiler-loader__paragraph">
				<b>{ currentStage.paragraphs[ currentParagraph ].label } </b>
				{ currentStage.paragraphs[ currentParagraph ].text }
			</p>
		</div>
	);
};
