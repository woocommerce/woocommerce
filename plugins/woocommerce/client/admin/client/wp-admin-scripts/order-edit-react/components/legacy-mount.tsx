/**
 * Clones a server-rendered `<template>`'s content into a host div.
 *
 * Used to relocate third-party meta boxes (rendered server-side via
 * `do_meta_boxes()` inside hidden `<template>` elements) into the right slot
 * of the React layout — preserving inline script execution and event bindings.
 */

import { useEffect, useRef } from '@wordpress/element';

interface LegacyMountProps {
	templateId: string;
	className?: string;
}

export function LegacyMount( { templateId, className }: LegacyMountProps ) {
	const ref = useRef< HTMLDivElement >( null );

	useEffect( () => {
		const template = document.getElementById( templateId ) as HTMLTemplateElement | null;
		const host = ref.current;
		if ( ! template || ! host ) {
			return;
		}
		// Move (not clone) the content so wp-admin's own JS — already bound to the
		// elements inside the template's DocumentFragment when scripts ran — keeps
		// working after relocation.
		host.replaceChildren( template.content );
	}, [ templateId ] );

	return <div ref={ ref } className={ className } />;
}
