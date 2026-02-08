import '@testing-library/jest-dom';

// Mock Inertia.js
jest.mock('@inertiajs/react', () => ({
  Head: ({ children, title }) => <head><title>{title}</title>{children}</head>,
  Link: ({ href, children, ...props }) => <a href={href} {...props}>{children}</a>,
  router: {
    get: jest.fn(),
    post: jest.fn(),
    put: jest.fn(),
    patch: jest.fn(),
    delete: jest.fn(),
    reload: jest.fn(),
    visit: jest.fn(),
  },
}));

// Mock Ziggy routes
global.route = jest.fn((name, params = {}) => {
  const routes = {
    'welcome': '/',
    'clients.index': '/clients',
  };
  return routes[name] || '/';
});

// Mock window.matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: jest.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: jest.fn(), // deprecated
    removeListener: jest.fn(), // deprecated
    addEventListener: jest.fn(),
    removeEventListener: jest.fn(),
    dispatchEvent: jest.fn(),
  })),
});
