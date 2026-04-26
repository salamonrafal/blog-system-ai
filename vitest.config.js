import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'jsdom',
    environmentOptions: {
      jsdom: {
        url: 'http://localhost/',
      },
    },
    include: ['tests/JavaScript/**/*.test.js'],
    setupFiles: ['tests/JavaScript/setup.js'],
    restoreMocks: true,
    clearMocks: true,
  },
});
