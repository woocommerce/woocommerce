/**
 * External dependencies
 */
import { Button, __unstableMotion as motion } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, arrowLeft, wordpress } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { BackButton } from '../../private-apis';

const toggleHomeIconVariants = {
	edit: {
		opacity: 0,
		scale: 0.2,
	},
	hover: {
		opacity: 1,
		scale: 1,
		clipPath: 'inset( 22% round 2px )',
	},
};

const siteIconVariants = {
	edit: {
		clipPath: 'inset(0% round 0px)',
	},
	hover: {
		clipPath: 'inset( 22% round 2px )',
	},
	tap: {
		clipPath: 'inset(0% round 0px)',
	},
};

/**
 * TODO: Add on click event
 * TODO: validation on Save
 * TODO: remove the more menu if full screen settings is enabled
 * TODO: remove the save button if full screen settings is enabled
 * TODO: add the close button if full screen settings is disabled, but user toggled full screen
 */

/**
 * Back button content component with animation effects.
 */
export const BackButtonContent = () => {
	return (
		<BackButton>
			{ ( { length } ) =>
				length <= 1 && (
					<motion.div
						className="woocommerce-email-editor__view-mode-toggle"
						transition={ {
							duration: 0.2,
						} }
						animate="edit"
						initial="edit"
						whileHover="hover"
						whileTap="tap"
					>
						<Button
							label={ __( 'Close editor', 'woocommerce' ) }
							showTooltip
							tooltipPosition="middle right"
							onClick={ () => {
								// TODO add action to navigate away from here
							} }
						>
							<motion.div variants={ siteIconVariants }>
								<div className="woocommerce-email-editor__view-mode-toggle-icon">
									<Icon
										className="woocommerce-email-editor-icon__icon"
										icon={ wordpress }
										size={ 48 }
									/>
								</div>
							</motion.div>
						</Button>
						<motion.div
							className="woocommerce-email-editor-icon"
							variants={ toggleHomeIconVariants }
						>
							<Icon icon={ arrowLeft } />
						</motion.div>
					</motion.div>
				)
			}
		</BackButton>
	);
};
