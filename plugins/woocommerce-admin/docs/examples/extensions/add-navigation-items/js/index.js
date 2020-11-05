/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { registerPlugin } from "@wordpress/plugins";
import { WooNavigationItem } from "@woocommerce/navigation";

const MyPlugin = () => {
    const handleClick = () => {
        alert( 'Menu item clicked!' );
    }

    return (
        <WooNavigationItem item="example-category-child-2">
            <Button onClick={ handleClick }>
                { __( 'JavaScript Example', 'plugin-domain' ) }
            </Button>
        </WooNavigationItem>
    );
};

registerPlugin('my-plugin', { render: MyPlugin });
