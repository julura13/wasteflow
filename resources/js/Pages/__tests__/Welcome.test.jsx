import React from 'react';
import { render, screen } from '@testing-library/react';
import Welcome from '../Welcome';

jest.mock('../../Layouts/AppLayout', () => {
    return function MockAppLayout({ children, title }) {
        return (
            <div data-testid="app-layout">
                <h1>{title}</h1>
                {children}
            </div>
        );
    };
});

describe('Welcome Page', () => {
    it('renders welcome page with correct title', () => {
        render(<Welcome />);
        
        expect(screen.getByText('Welcome')).toBeInTheDocument();
        expect(screen.getByTestId('app-layout')).toBeInTheDocument();
    });

    it('displays main heading', () => {
        render(<Welcome />);
        
        expect(screen.getByText('Welcome to WasteFlow')).toBeInTheDocument();
    });

    it('displays tagline', () => {
        render(<Welcome />);
        
        expect(screen.getByText('Transforming Waste, Nurturing the Planet')).toBeInTheDocument();
    });

    it('displays feature cards', () => {
        render(<Welcome />);
        
        expect(screen.getByText('Sustainable Solutions')).toBeInTheDocument();
        expect(screen.getByText('Industrial Focus')).toBeInTheDocument();
        expect(screen.getByText('Zero Waste Goals')).toBeInTheDocument();
    });

    it('displays feature descriptions', () => {
        render(<Welcome />);
        
        expect(screen.getByText(/Professional waste management services/)).toBeInTheDocument();
        expect(screen.getByText(/Specialized services for Property/)).toBeInTheDocument();
        expect(screen.getByText(/Optimizing waste solutions/)).toBeInTheDocument();
    });

    it('displays get started button', () => {
        render(<Welcome />);
        
        expect(screen.getByText('Get Started')).toBeInTheDocument();
    });

    it('renders emoji icons in feature cards', () => {
        render(<Welcome />);
        
        expect(screen.getByText('♻️')).toBeInTheDocument();
        expect(screen.getByText('🏭')).toBeInTheDocument();
        expect(screen.getByText('📊')).toBeInTheDocument();
    });
});
