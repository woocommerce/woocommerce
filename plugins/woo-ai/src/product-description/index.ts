/**
 * Internal dependencies
 */
import { renderWrappedComponent } from '../utils';
import { WriteItForMeButtonContainer } from './product-description-button-container';

if ( window.JP_CONNECTION_INITIAL_STATE?.connectionStatus?.isActive ) {
	renderWrappedComponent(
		WriteItForMeButtonContainer,
		document.getElementById( 'woocommerce-ai-app-product-gpt-button' )
	);
}
