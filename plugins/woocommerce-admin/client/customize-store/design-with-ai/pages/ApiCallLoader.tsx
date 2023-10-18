/**
 * External dependencies
 */
import { Loader } from '@woocommerce/onboarding';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import analyzingYourResponses from '../../assets/images/loader-analyzing-your-responses.svg';
import designingTheBestLook from '../../assets/images/loader-designing-the-best-look.svg';
import comparingTheTopPerformingStores from '../../assets/images/loader-comparing-top-performing-stores.svg';
import assemblingAiOptimizedStore from '../../assets/images/loader-assembling-ai-optimized-store.svg';
import applyingFinishingTouches from '../../assets/images/loader-applying-the-finishing-touches.svg';

const loaderSteps = [
	{
		title: __( 'Analyzing your responses', 'woocommerce' ),
		image: (
			<img
				src={ analyzingYourResponses }
				alt={ __( 'Analyzing your responses', 'woocommerce' ) }
			/>
		),
		progress: 17,
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
		progress: 33,
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
		progress: 50,
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
		progress: 66,
	},
	{
		title: __( 'Applying the finishing touches', 'woocommerce' ),
		image: (
			<img
				src={ applyingFinishingTouches }
				alt={ __( 'Applying the finishing touches', 'woocommerce' ) }
			/>
		),
		progress: 83,
	},
];

export const ApiCallLoader = () => {
	useEffect( () => {
		const preload = ( src: string ) => {
			const img = new Image();

			img.src = src;
			img.onload = () => {};
		};

		// We preload the assemblingAiOptimizedStore to avoid flickering. We only need to preload it because the others are small enough to be inlined in base64.
		preload( assemblingAiOptimizedStore );
	}, [] );

	return (
		<Loader>
			<Loader.Sequence interval={ 3500 } shouldLoop={ false }>
				{ loaderSteps.map( ( step, index ) => (
					<Loader.Layout key={ index }>
						<Loader.Illustration>
							{ step.image }
						</Loader.Illustration>
						<Loader.Title>{ step.title }</Loader.Title>
						<Loader.ProgressBar progress={ step.progress || 0 } />
					</Loader.Layout>
				) ) }
			</Loader.Sequence>
		</Loader>
	);
};
