/**
 * External dependencies
 */
import { Button, __unstableMotion as motion } from '@wordpress/components';
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import { __, isRTL } from '@wordpress/i18n';
import {
	Icon,
	arrowLeft,
	chevronLeft,
	chevronRight,
	wordpress,
} from '@wordpress/icons';
import { applyFilters } from '@wordpress/hooks';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { BackButton } from '../../private-apis';
import { recordEvent } from '../../events';
import { storeName } from '../../store';

// The WordPress 7.1+ header reserves a compact 32px slot for the back button
// and renders it as a plain chevron; older versions reserve a 64px slot filled
// with a fullscreen-style logo button. The slot width is what our button must
// fit into, so detect it instead of the WordPress version.
const COMPACT_SLOT_MAX_WIDTH = 48;

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

function useCloseAction() {
	const { urls } = useSelect(
		( select ) => ( {
			urls: select( storeName ).getUrls(),
		} ),
		[]
	);

	return () => {
		recordEvent( 'header_close_button_clicked' );
		const defaultAction = () => {
			if ( ! urls.back ) {
				return;
			}
			try {
				// Resolve against the full current URL so relative paths
				// keep the same meaning as a direct location assignment.
				const backUrl = new URL( urls.back, window.location.href );
				// Only navigate to web URLs so schemes like javascript:
				// cannot reach window.location.
				if ( [ 'http:', 'https:' ].includes( backUrl.protocol ) ) {
					window.location.href = backUrl.href;
				}
			} catch {
				// Do not navigate to an invalid URL.
			}
		};
		const action = applyFilters(
			'woocommerce_email_editor_close_action_callback',
			defaultAction
		);
		( typeof action === 'function' ? action : defaultAction )();
	};
}

/**
 * Compact back button fitting the narrow slot of the WordPress 7.1+ header,
 * rendered as a plain chevron matching core.
 */
const CompactBackButtonContent = () => {
	const onClose = useCloseAction();

	return (
		<Button
			size="compact"
			icon={ isRTL() ? chevronRight : chevronLeft }
			label={ __( 'Close editor', __i18n_text_domain__ ) }
			showTooltip
			tooltipPosition="middle right"
			onClick={ onClose }
		/>
	);
};

/**
 * Fullscreen-style back button filling the 64px slot of the WordPress ≤ 7.0
 * header, rendered as the WordPress logo with a hover arrow. This button will
 * be dropped after we drop support for WordPress 7.0.
 */
const FullscreenBackButtonContent = () => {
	const onClose = useCloseAction();

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
				label={ __( 'Close editor', __i18n_text_domain__ ) }
				showTooltip
				tooltipPosition="middle right"
				onClick={ onClose }
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

/**
 * Back button content component. Picks the variant fitting the width the
 * editor header reserves for the back button. The detection is temporary and
 * will be dropped along with the fullscreen-style button after we drop
 * support for WordPress 7.0.
 */
const DefaultBackButtonContent = () => {
	const measureRef = useRef< HTMLDivElement >( null );
	const [ isCompactSlot, setIsCompactSlot ] = useState< boolean | null >(
		null
	);

	useLayoutEffect( () => {
		const slot = measureRef.current?.closest< HTMLElement >(
			'.editor-header__back-button'
		);
		const slotWidth = slot?.getBoundingClientRect().width ?? 0;
		setIsCompactSlot(
			slotWidth > 0 && slotWidth <= COMPACT_SLOT_MAX_WIDTH
		);
	}, [] );

	if ( isCompactSlot === null ) {
		return <div ref={ measureRef } />;
	}

	return isCompactSlot ? (
		<CompactBackButtonContent />
	) : (
		<FullscreenBackButtonContent />
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
