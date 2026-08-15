export const config = {
	baseUrl: process.env.EP_BASE_URL ?? 'http://localhost:8086',
	username: process.env.EP_USERNAME ?? 'admin',
	password: process.env.EP_PASSWORD ?? 'password',

	// Pixel diff sensitivity (0..1, higher = more tolerant per pixel).
	pixelThreshold: 0.1,

	// Geometry tolerances in px. Horizontal metrics are strict because
	// widths/paddings/borders are what we test. Vertical metrics are loose
	// because line-height and font rendering shift things a bit.
	tolerances: {
		horizontal: 2,
		vertical: 8,
	},

	headless: process.env.EP_HEADED !== '1',
};
