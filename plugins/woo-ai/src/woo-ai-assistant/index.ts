/**
 * Internal dependencies
 */
import { renderWrappedComponent } from '../utils';
import WooAIAssistant from './woo-ai-assistant';

const root = document.createElement( 'div' );
root.id = 'woocommerce-ai-assistant';

renderWrappedComponent( WooAIAssistant, root );

// Insert the category suggestions node in the product category meta box.
document.body.append( root );
