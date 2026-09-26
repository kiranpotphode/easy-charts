import { defineConfig } from 'vitest/config';

// Unit tests only; the Playwright specs in tests/e2e run with `npm run test:e2e`.
export default defineConfig( {
	test: {
		include: [ 'src/**/*.test.js' ],
	},
} );
