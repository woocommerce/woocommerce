/**
 * External dependencies
 */
import { Button, Flex, Icon } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { TourStepRendererProps } from '@automattic/tour-kit';

interface Props {
	onDismiss: TourStepRendererProps[ 'onDismiss' ];
}

const StepControls: React.FunctionComponent< Props > = ( { onDismiss } ) => {
	return (
		<Flex className="woocommerce-tour-kit-step-controls" justify="flex-end">
			<Button
				className="woocommerce-tour-kit-step-controls__close-btn"
				label={ __( 'Close Tour', 'woocommerce' ) }
				icon={ <Icon icon={ closeSmall } viewBox="6 4 12 14" /> }
				iconSize={ 16 }
				size={ 16 }
				onClick={ onDismiss( 'close-btn' ) }
			></Button>
		</Flex>
	);
};

export default StepControls;
