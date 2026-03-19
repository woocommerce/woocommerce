/**
 * External dependencies
 */
import { Button, __unstableMotion as motion } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, arrowLeft, wordpress } from '@wordpress/icons';
import { applyFilters } from '@wordpress/hooks';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { BackButton } from '../../private-apis';
import { recordEvent } from '../../events';
import { storeName } from '../../store';

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
 * Back button content component with animation effects.
 */
const DefaultBackButtonContent = () => {
	const { urls } = useSelect(
		( select ) => ( {
			urls: select( storeName ).getUrls(),
		} ),
		[]
	);

	function backAction() {
		if ( urls.listings ) {
			window.location.href = urls.back;
		}
	}

	return (
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
					recordEvent( 'header_close_button_clicked' );
					const action = applyFilters(
						'woocommerce_email_editor_close_action_callback',
						backAction
					) as () => void;
					action();
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
	);
};

export const BackButtonContent = () => {
	const BackButtonUsedContent = applyFilters(
		'woocommerce_email_editor_close_content',
		DefaultBackButtonContent
	) as React.ComponentType;

	return (
		<BackButton>
			{ ( { length } ) => length <= 1 && <BackButtonUsedContent /> }
		</BackButton>
	);
};
