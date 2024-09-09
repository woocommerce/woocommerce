/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';
import '../style.scss';

import WooTourKit from '..';
import type { WooConfig, WooOptions } from '../types';

export default {
	title: 'WooCommerce Admin/components/TourKit',
	component: WooTourKit,
};

const References = () => {
	return (
		<div className={ 'storybook__tourkit-references' }>
			<div className={ 'storybook__tourkit-references-container' }>
				<div className={ 'storybook__tourkit-references-a' }>
					<p>Reference A</p>
				</div>
				<div className={ 'storybook__tourkit-references-b' }>
					<p>Reference B</p>
					<div style={ { display: 'grid', placeItems: 'center' } }>
						<input
							style={ { margin: 'auto', display: 'block' } }
						></input>
					</div>
				</div>
				<div className={ 'storybook__tourkit-references-c' }>
					<p>Reference C</p>
				</div>
				<div className={ 'storybook__tourkit-references-d' }>
					<p>Reference D</p>
				</div>
			</div>
		</div>
	);
};

const Tour = ( {
	onClose,
	options,
	placement,
}: {
	onClose: () => void;
	options?: WooOptions;
	placement?: WooConfig[ 'placement' ];
} ) => {
	const config: WooConfig = {
		placement,
		steps: [
			{
				referenceElements: {
					desktop: '.storybook__tourkit-references-a',
					mobile: '.storybook__tourkit-references-a',
				},
				meta: {
					heading: 'Change content',
					descriptions: {
						desktop:
							'You can change the content and add any relevant links.',
						mobile: 'You can change the content and add any relevant links.',
					},
				},
			},
			{
				referenceElements: {
					desktop: '.storybook__tourkit-references-b',
					mobile: '.storybook__tourkit-references-b',
				},
				focusElement: {
					desktop: '.storybook__tourkit-references-b input',
				},
				meta: {
					heading: 'Shipping zones',
					descriptions: {
						desktop:
							'We added a few shipping zones for you based on your location, but you can manage them at any time.',
						mobile: 'A shipping zone is a geographic area where a certain set of shipping methods are offered.',
					},
				},
			},
			{
				referenceElements: {
					desktop: '.storybook__tourkit-references-c',
					mobile: '.storybook__tourkit-references-c',
				},
				meta: {
					heading: 'Shipping methods',
					descriptions: {
						desktop:
							'We defaulted to some recommended shipping methods based on your store location, but you can manage them at any time within each shipping zone settings.   ',
						mobile: 'We defaulted to some recommended shipping methods based on your store location, but you can manage them at any time within each shipping zone settings.   ',
					},
				},
			},
			{
				referenceElements: {
					desktop: '.storybook__tourkit-references-d',
					mobile: '.storybook__tourkit-references-d',
				},
				meta: {
					heading: 'Laura 4',
					descriptions: {
						desktop: 'Lorem ipsum dolor sit amet.',
						mobile: 'Lorem ipsum dolor sit amet.',
					},
					primaryButton: {
						isDisabled: true,
						text: 'Keep editing',
					},
				},
			},
		],
		closeHandler: onClose,
		options: {
			classNames: [ 'mytour' ],
			...options,
		},
	};

	return <WooTourKit config={ config } />;
};

const StoryTour = ( {
	options = {},
	placement,
}: {
	options?: WooConfig[ 'options' ];
	placement?: WooConfig[ 'placement' ];
} ) => {
	const [ showTour, setShowTour ] = useState( false );

	return (
		<div className="storybook__tourkit">
			<References />
			{ ! showTour && (
				<button onClick={ () => setShowTour( true ) }>
					Start Tour
				</button>
			) }
			{ showTour && (
				<Tour
					placement={ placement }
					onClose={ () => setShowTour( false ) }
					options={ options }
				/>
			) }
		</div>
	);
};

export const NoEffects = () => (
	<StoryTour
		options={ {
			effects: {},
		} }
	/>
);
export const Spotlight = () => (
	<StoryTour
		options={ {
			effects: { arrowIndicator: true, spotlight: {} },
		} }
	/>
);
export const Overlay = () => (
	<StoryTour
		options={ {
			effects: { arrowIndicator: true, overlay: true },
		} }
	/>
);
export const SpotlightInteractivity = () => (
	<StoryTour
		options={ {
			effects: {
				spotlight: {
					interactivity: {
						rootElementSelector: '#root',
						enabled: true,
					},
				},
			},
		} }
	/>
);
export const AutoScroll = () => (
	<>
		<div style={ { height: '10vh' } }></div>
		<StoryTour
			options={ {
				effects: {
					autoScroll: {
						behavior: 'smooth',
					},
				},
			} }
		/>
	</>
);

export const Placement = () => <StoryTour placement={ 'left' } />;
