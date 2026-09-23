export function presetToCssVariable( preset: string ): string {
	if ( ! preset.includes( ':' ) || ! preset.includes( '|' ) ) {
		return preset;
	}
	return `var(--wp--${ preset
		.replace( 'var:', '' )
		.replaceAll( '|', '--' ) })`;
}
