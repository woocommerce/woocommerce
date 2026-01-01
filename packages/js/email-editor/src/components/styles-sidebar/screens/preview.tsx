/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useState, useMemo } from '@wordpress/element';
import {
	__experimentalHStack as HStack, // eslint-disable-line
	__experimentalVStack as VStack, // eslint-disable-line
	__unstableMotion as motion, // eslint-disable-line
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { storeName } from '../../../store';
import { useEmailStyles } from '../../../hooks';
import { getCompressedVariableValue } from '../../../style-variables';

const EMPTY_ARRAY = [];

const firstFrame = {
	start: {
		scale: 1,
		opacity: 1,
	},
	hover: {
		scale: 0,
		opacity: 0,
	},
};

const midFrame = {
	hover: {
		opacity: 1,
	},
	start: {
		opacity: 0.5,
	},
};

const secondFrame = {
	hover: {
		scale: 1,
		opacity: 1,
	},
	start: {
		scale: 0,
		opacity: 0,
	},
};

const normalizedHeight = 152;
const normalizedColorSwatchSize = 32;

type Props = {
	label?: string;
	isFocused?: boolean;
	withHoverView?: boolean;
};

/**
 * Component to render the styles preview based on the component from the site editor:
 * https://github.com/WordPress/gutenberg/blob/5c7c4e7751df5e05fc70a354cd0d81414ac9c7e7/packages/edit-site/src/components/global-styles/preview-styles.js
 *
 * @param root0
 * @param root0.label
 * @param root0.isFocused
 * @param root0.withHoverView
 */
export function Preview( {
	label,
	isFocused,
	withHoverView,
}: Props ): JSX.Element {
	const { colors } = useSelect(
		( select ) => ( {
			colors: select( storeName ).getPaletteColors(),
		} ),
		[]
	);
	const paletteColors = useMemo(
		() =>
			( colors?.theme || EMPTY_ARRAY ).concat(
				colors?.default || EMPTY_ARRAY
			),
		[ colors ]
	);
	const { styles } = useEmailStyles();

	const { backgroundColor, headingColor, highlightedColors } =
		useMemo( () => {
			const backgroundCol =
				getCompressedVariableValue( styles?.color?.background ) ||
				'white';
			const textCol =
				getCompressedVariableValue( styles?.color?.text ) || 'black';
			const headingCol =
				getCompressedVariableValue(
					styles?.elements?.h1?.color?.text
				) || textCol;
			const linkColor =
				getCompressedVariableValue(
					styles?.elements?.link?.color?.text
				) || headingCol;
			const buttonBackgroundCol =
				getCompressedVariableValue(
					styles?.elements?.button?.color?.background
				) || linkColor;

			const textColorPaletteObj = paletteColors.find(
				( { color } ) => color.toLowerCase() === textCol.toLowerCase()
			);
			const buttonBackgroundColorPaletteObj = paletteColors.find(
				( { color } ) =>
					color.toLowerCase() === buttonBackgroundCol.toLowerCase()
			);

			// We pick the colors for the highlighted colors the same way as the site editor
			// https://github.com/WordPress/gutenberg/blob/7b3850b6a39ce45948f09efe750451c6323a4613/packages/edit-site/src/components/global-styles/hooks.js#L83-L95
			const highlightedColorsObj = [
				...( textColorPaletteObj
					? [ textColorPaletteObj ]
					: EMPTY_ARRAY ),
				...( buttonBackgroundColorPaletteObj
					? [ buttonBackgroundColorPaletteObj ]
					: EMPTY_ARRAY ),
				...paletteColors,
			]
				.filter(
					( { color }, index, self ) =>
						color.toLowerCase() !== backgroundCol.toLowerCase() &&
						index ===
							self.findIndex(
								( item ) =>
									item.color.toLowerCase() ===
									color.toLowerCase() // remove duplicates
							)
				)
				.slice( 0, 2 );

			return {
				backgroundColor: backgroundCol,
				headingColor: headingCol,
				highlightedColors: highlightedColorsObj,
			};
		}, [ styles, paletteColors ] );

	const headingFontWeight =
		styles?.elements?.heading?.typography?.fontWeight || 'inherit';
	const headingFontFamily =
		styles?.elements?.heading?.typography?.fontFamily || 'inherit';

	const ratio = 1;
	// When is set label, the preview animates the hover state and displays the label
	const [ isHovered, setIsHovered ] = useState( false );

	return (
		<div
			onMouseEnter={ () => setIsHovered( true ) }
			onMouseLeave={ () => setIsHovered( false ) }
		>
			<motion.div
				style={ {
					height: normalizedHeight * ratio,
					width: '100%',
					background: backgroundColor,
					cursor: withHoverView ? 'pointer' : undefined,
				} }
				initial="start"
				animate={
					( isHovered || isFocused ) && label ? 'hover' : 'start'
				}
			>
				<motion.div
					variants={ firstFrame }
					style={ {
						height: '100%',
						overflow: 'hidden',
					} }
				>
					<HStack
						spacing={ 10 * ratio }
						justify="center"
						style={ {
							height: '100%',
							overflow: 'hidden',
						} }
					>
						<motion.div
							style={ {
								fontFamily: headingFontFamily,
								fontSize: 65 * ratio,
								color: headingColor,
								fontWeight: headingFontWeight,
							} }
							animate={ { scale: 1, opacity: 1 } }
							initial={ { scale: 0.1, opacity: 0 } }
							transition={ { delay: 0.3, type: 'tween' } }
						>
							Aa
						</motion.div>
						<VStack spacing={ 4 * ratio }>
							{ highlightedColors.map(
								( { slug, color }, index ) => (
									<motion.div
										key={ slug }
										style={ {
											height:
												normalizedColorSwatchSize *
												ratio,
											width:
												normalizedColorSwatchSize *
												ratio,
											background: color,
											borderRadius:
												( normalizedColorSwatchSize *
													ratio ) /
												2,
										} }
										animate={ {
											scale: 1,
											opacity: 1,
										} }
										initial={ {
											scale: 0.1,
											opacity: 0,
										} }
										transition={ {
											delay: index === 1 ? 0.2 : 0.1,
										} }
									/>
								)
							) }
						</VStack>
					</HStack>
				</motion.div>
				<motion.div
					variants={ withHoverView && midFrame }
					style={ {
						height: '100%',
						width: '100%',
						position: 'absolute',
						top: 0,
						overflow: 'hidden',
						filter: 'blur(60px)',
						opacity: 0.1,
					} }
				>
					<HStack
						spacing={ 0 }
						justify="flex-start"
						style={ {
							height: '100%',
							overflow: 'hidden',
						} }
					>
						{ paletteColors.slice( 0, 4 ).map( ( { color } ) => (
							<div
								key={ color }
								style={ {
									height: '100%',
									background: color,
									flexGrow: 1,
								} }
							/>
						) ) }
					</HStack>
				</motion.div>
				<motion.div
					variants={ secondFrame }
					style={ {
						height: '100%',
						width: '100%',
						overflow: 'hidden',
						position: 'absolute',
						top: 0,
					} }
				>
					<VStack
						spacing={ 3 * ratio }
						justify="center"
						style={ {
							height: '100%',
							overflow: 'hidden',
							padding: 10 * ratio,
							boxSizing: 'border-box',
						} }
					>
						{ label && (
							<div
								style={ {
									fontSize: 40 * ratio,
									fontFamily: headingFontFamily,
									color: headingColor,
									fontWeight: headingFontWeight,
									lineHeight: '1em',
									textAlign: 'center',
								} }
							>
								{ label }
							</div>
						) }
					</VStack>
				</motion.div>
			</motion.div>
		</div>
	);
}
