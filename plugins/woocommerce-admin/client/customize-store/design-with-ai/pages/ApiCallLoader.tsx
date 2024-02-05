/**
 * External dependencies
 */
import { Loader } from '@woocommerce/onboarding';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import analyzingYourResponses from '../../assets/images/loader-analyzing-your-responses.svg';
import designingTheBestLook from '../../assets/images/loader-designing-the-best-look.svg';
import comparingTheTopPerformingStores from '../../assets/images/loader-comparing-top-performing-stores.svg';
import assemblingAiOptimizedStore from '../../assets/images/loader-assembling-ai-optimized-store.svg';
import applyingFinishingTouches from '../../assets/images/loader-applying-the-finishing-touches.svg';
import generatingContent from '../../assets/images/loader-generating-content.svg';
import openingTheDoors from '../../assets/images/loader-opening-the-doors.svg';
import { createAugmentedSteps } from '~/customize-store/utils';

const loaderSteps = [
	{
		title: __( 'Analyzing your responses', 'woocommerce' ),
		image: (
			<img
				src={ analyzingYourResponses }
				alt={ __( 'Analyzing your responses', 'woocommerce' ) }
			/>
		),
		progress: 14,
	},
	{
		title: __( 'Comparing the top performing stores', 'woocommerce' ),
		image: (
			<img
				src={ comparingTheTopPerformingStores }
				alt={ __(
					'Comparing the top performing stores',
					'woocommerce'
				) }
			/>
		),
		progress: 28,
	},
	{
		title: __( 'Designing the best look for your business', 'woocommerce' ),
		image: (
			<img
				src={ designingTheBestLook }
				alt={ __(
					'Designing the best look for your business',
					'woocommerce'
				) }
			/>
		),
		progress: 42,
	},
	{
		title: __( 'Generating content', 'woocommerce' ),
		image: (
			<img
				src={ generatingContent }
				alt={ __( 'Generating content', 'woocommerce' ) }
			/>
		),
		progress: 56,
	},
	{
		title: __( 'Assembling your AI-optimized store', 'woocommerce' ),
		image: (
			<img
				src={ assemblingAiOptimizedStore }
				alt={ __(
					'Assembling your AI-optimized store',
					'woocommerce'
				) }
			/>
		),
		progress: 70,
	},
	{
		title: __( 'Applying the finishing touches', 'woocommerce' ),
		image: (
			<img
				src={ applyingFinishingTouches }
				alt={ __( 'Applying the finishing touches', 'woocommerce' ) }
			/>
		),
		progress: 84,
	},
	{
		title: __( 'Opening the doors', 'woocommerce' ),
		image: (
			<img
				src={ openingTheDoors }
				alt={ __( 'Opening the doors', 'woocommerce' ) }
			/>
		),
		progress: 100,
	},
];

// Loader for the API call without the last frame.
export const ApiCallLoader = () => {
	const [ progress, setProgress ] = useState( 5 );

	useEffect( () => {
		const preload = ( src: string ) => {
			const img = new Image();

			img.src = src;
			img.onload = () => {};
		};

		// We preload the these images to avoid flickering. We only need to preload them because the others are small enough to be inlined in base64.
		preload( assemblingAiOptimizedStore );
		preload( openingTheDoors );
	}, [] );

	const augmentedSteps = createAugmentedSteps(
		loaderSteps.slice( 0, -1 ),
		10
	);

	return (
		<Loader>
			<Loader.Sequence
				interval={ ( 40 * 1000 ) / ( augmentedSteps.length - 1 ) }
				shouldLoop={ false }
				onChange={ ( index ) => {
					// to get around bad set state timing issue
					setTimeout( () => {
						setProgress( augmentedSteps[ index ].progress );
					}, 0 );
				} }
			>
				{ augmentedSteps.map( ( step, index ) => (
					<Loader.Layout key={ index }>
						<Loader.Illustration>
							{ step.image }
						</Loader.Illustration>
						<Loader.Title>{ step.title }</Loader.Title>
					</Loader.Layout>
				) ) }
			</Loader.Sequence>
			<Loader.ProgressBar
				className="smooth-transition"
				progress={ progress || 0 }
			/>
		</Loader>
	);
};

export const AssembleHubLoader = () => {
	// Show the last two steps of the loader so that the last frame is the shortest time possible
	const augmentedSteps = createAugmentedSteps( loaderSteps.slice( -2 ), 10 );

	const [ progress, setProgress ] = useState( augmentedSteps[ 0 ].progress );

	return (
		<Loader>
			<Loader.Sequence
				interval={ ( 10 * 1000 ) / ( augmentedSteps.length - 1 ) }
				shouldLoop={ false }
				onChange={ ( index ) => {
					// to get around bad set state timing issue
					setTimeout( () => {
						setProgress( augmentedSteps[ index ].progress );
					}, 0 );
				} }
			>
				{ augmentedSteps.map( ( step, index ) => (
					<Loader.Layout key={ index }>
						<Loader.Illustration>
							{ step.image }
						</Loader.Illustration>
						<Loader.Title>{ step.title }</Loader.Title>
					</Loader.Layout>
				) ) }
			</Loader.Sequence>
			<Loader.ProgressBar
				className="smooth-transition"
				progress={ progress || 0 }
			/>
		</Loader>
	);
};
