/**
 * External dependencies
 */
 import React from 'react'

 /**
  * Internal dependencies
  */
 import { RichTextEditor } from '../';
 
 export const Basic: React.FC = () => {
     return (
         <RichTextEditor blocks={ [] } onChange={ () => console.log('changed')  } />
     );
 };
 
 export default {
     title: 'WooCommerce Admin/components/RichTextEditor',
     component: RichTextEditor,
 };
 