/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	__experimentalToggleGroupControl as ToggleGroupControl, // eslint-disable-line
	__experimentalToggleGroupControlOption as ToggleGroupControlOption, // eslint-disable-line
	__experimentalSpacer as Spacer, // eslint-disable-line
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import TypographyElementPanel, {
	DEFAULT_CONTROLS,
} from '../panels/typography-element-panel';
import TypographyPreview from '../previews/typography-preview';
import ScreenHeader from './screen-header';
import { recordEvent, recordEventOnce } from '../../../events';

const PANELS = {
	text: {
		title: __( 'Text', __i18n_text_domain__ ),
		description: __(
			'Manage the fonts and typography used on text.',
			__i18n_text_domain__
		),
		defaultControls: DEFAULT_CONTROLS,
	},
	link: {
		title: __( 'Links', __i18n_text_domain__ ),
		description: __(
			'Manage the fonts and typography used on links.',
			__i18n_text_domain__
		),
		defaultControls: {
			...DEFAULT_CONTROLS,
			textDecoration: true,
		},
	},
	heading: {
		title: __( 'Headings', __i18n_text_domain__ ),
		description: __(
			'Manage the fonts and typography used on headings.',
			__i18n_text_domain__
		),
		defaultControls: {
			...DEFAULT_CONTROLS,
			textTransform: true,
		},
	},
	button: {
		title: __( 'Buttons', __i18n_text_domain__ ),
		description: __(
			'Manage the fonts and typography used on buttons.',
			__i18n_text_domain__
		),
		defaultControls: DEFAULT_CONTROLS,
	},
};

export function ScreenTypographyElement( {
	element,
}: {
	element: string;
} ): JSX.Element {
	recordEventOnce( 'styles_sidebar_screen_typography_element_opened', {
		element,
	} );
	const [ headingLevel, setHeadingLevel ] = useState( 'heading' );
	return (
		<>
			<ScreenHeader
				title={ PANELS[ element ].title }
				description={ PANELS[ element ].description }
			/>
			<Spacer marginX={ 4 }>
				<TypographyPreview
					element={ element }
					headingLevel={ headingLevel }
				/>
			</Spacer>
			{ element === 'heading' && (
				<Spacer marginX={ 4 } marginBottom="1em">
					<ToggleGroupControl
						label={ __(
							'Select heading level',
							__i18n_text_domain__
						) }
						hideLabelFromVision
						value={ headingLevel }
						onChange={ ( value ) => {
							setHeadingLevel( value.toString() );
							recordEvent(
								'styles_sidebar_screen_typography_element_heading_level_selected',
								{ value }
							);
						} }
						isBlock
						size="__unstable-large"
						__nextHasNoMarginBottom
					>
						<ToggleGroupControlOption
							value="heading"
							label={ _x(
								'All',
								'heading levels',
								__i18n_text_domain__
							) }
						/>
						<ToggleGroupControlOption
							value="h1"
							label={ _x(
								'H1',
								'Heading Level',
								__i18n_text_domain__
							) }
						/>
						<ToggleGroupControlOption
							value="h2"
							label={ _x(
								'H2',
								'Heading Level',
								__i18n_text_domain__
							) }
						/>
						<ToggleGroupControlOption
							value="h3"
							label={ _x(
								'H3',
								'Heading Level',
								__i18n_text_domain__
							) }
						/>
						<ToggleGroupControlOption
							value="h4"
							label={ _x(
								'H4',
								'Heading Level',
								__i18n_text_domain__
							) }
						/>
						<ToggleGroupControlOption
							value="h5"
							label={ _x(
								'H5',
								'Heading Level',
								__i18n_text_domain__
							) }
						/>
						<ToggleGroupControlOption
							value="h6"
							label={ _x(
								'H6',
								'Heading Level',
								__i18n_text_domain__
							) }
						/>
					</ToggleGroupControl>
				</Spacer>
			) }
			<TypographyElementPanel
				element={ element }
				headingLevel={ headingLevel }
				defaultControls={ PANELS[ element ].defaultControls }
			/>
		</>
	);
}
