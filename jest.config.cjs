/** @type {import('jest').Config} */
module.exports = {
  testEnvironment: 'jsdom',
  setupFilesAfterEnv: ['<rootDir>/tests/setup.js'],
  moduleNameMapping: {
    '^@/(.*)$': '<rootDir>/resources/js/$1',
    '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
  },
  transform: {
    '^.+\\.(js|jsx)$': 'babel-jest',
  },
  testMatch: [
    '<rootDir>/resources/js/**/__tests__/**/*.(js|jsx)',
    '<rootDir>/resources/js/**/*.(test|spec).(js|jsx)',
  ],
  collectCoverageFrom: [
    'resources/js/**/*.{js,jsx}',
    '!resources/js/**/*.d.ts',
    '!resources/js/app.jsx',
    '!resources/js/bootstrap.js',
  ],
  coverageDirectory: 'coverage',
  coverageReporters: ['text', 'lcov', 'html'],
};