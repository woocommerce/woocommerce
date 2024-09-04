/**
 * External dependencies
 */
import { Loader } from '@woocommerce/onboarding';
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import loaderAssemblingStore from '../../assets/images/loader-assembling-ai-optimized-store.svg';
import loaderTurningLights from '../../assets/images/loader-turning-lights.svg';
import openingTheDoors from '../../assets/images/loader-opening-the-doors.svg';
import {
	attachIframeListeners,
	createAugmentedSteps,
	onIframeLoad,
} from '~/customize-store/utils';
import { DesignWithoutAIStateMachineEvents } from '../state-machine';

const loaderSteps = [
	{
		title: __( 'Setting up the foundations', 'woocommerce' ),
		image: (
			<img
				src={ loaderAssemblingStore }
				alt={ __( 'Setting up the foundations', 'woocommerce' ) }
			/>
		),
		progress: 25,
	},
	{
		title: __( 'Turning on the lights', 'woocommerce' ),
		image: (
			<img
				src={ loaderTurningLights }
				alt={ __( 'Turning on the lights', 'woocommerce' ) }
			/>
		),
		progress: 50,
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
		preload( loaderAssemblingStore );
		preload( loaderTurningLights );
		preload( openingTheDoors );
	}, [] );

	const augmentedSteps = createAugmentedSteps(
		loaderSteps.slice( 0, -1 ),
		10
	);

	return (
		<Loader>
			<Loader.Sequence
				interval={ ( 5 * 1000 ) / ( augmentedSteps.length - 1 ) }
				shouldLoop={ false }
				onChange={ ( index ) => {
					// to get around bad set state timing issue
					setTimeout( () => {
						setProgress( augmentedSteps[ index ].progress );
					}, 0 );
				} }
			>
				{ augmentedSteps.map( ( step ) => (
					<Loader.Layout key={ step.title }>
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

type SendEventFn = ( event: DesignWithoutAIStateMachineEvents ) => void;

const AssemblerHub = ( { sendEvent }: { sendEvent: SendEventFn } ) => {
	const assemblerUrl = getNewPath( {}, '/customize-store/assembler-hub', {} );
	const iframe = useRef< HTMLIFrameElement >( null );
	const [ isVisible, setIsVisible ] = useState( false );

	useEffect( () => {
		window.addEventListener( 'message', ( event ) => {
			if ( event.data?.type === 'INSTALL_FONTS' ) {
				sendEvent( { type: 'INSTALL_FONTS' } );
			}
		} );
	}, [ sendEvent ] );

	return (
		<iframe
			ref={ iframe }
			onLoad={ ( frame ) => {
				const showIframe = () => setIsVisible( true );
				attachIframeListeners( frame.currentTarget );
				onIframeLoad( showIframe );
				// Ceiling wait time set to 60 seconds
				setTimeout( showIframe, 60 * 1000 );
				window.parent.history?.pushState( {}, '', assemblerUrl );
			} }
			style={ { opacity: isVisible ? 1 : 0 } }
			src={ assemblerUrl }
			title="assembler-hub"
			className="cys-fullscreen-iframe"
		/>
	);
};

export const AssembleHubLoader = ( {
	sendEvent,
}: {
	sendEvent: SendEventFn;
} ) => {
	// Show the last two steps of the loader so that the last frame is the shortest time possible
	const augmentedSteps = createAugmentedSteps( loaderSteps.slice( -2 ), 10 );

	const [ progress, setProgress ] = useState( augmentedSteps[ 0 ].progress );

	return (
		<>
			<Loader>
				<Loader.Sequence
					interval={ ( 2 * 1000 ) / ( augmentedSteps.length - 1 ) }
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
			<AssemblerHub sendEvent={ sendEvent } />
		</>
	);
};
