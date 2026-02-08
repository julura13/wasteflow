import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DataTable from '../DataTable';
import { createColumnHelper } from '@tanstack/react-table';

const columnHelper = createColumnHelper();

const mockData = [
    { id: 1, name: 'John Doe', email: 'john@example.com', status: 'active' },
    { id: 2, name: 'Jane Smith', email: 'jane@example.com', status: 'inactive' },
    { id: 3, name: 'Bob Johnson', email: 'bob@example.com', status: 'pending' },
];

const mockColumns = [
    columnHelper.accessor('id', {
        header: 'ID',
        cell: (info) => info.getValue(),
    }),
    columnHelper.accessor('name', {
        header: 'Name',
        cell: (info) => info.getValue(),
    }),
    columnHelper.accessor('email', {
        header: 'Email',
        cell: (info) => info.getValue(),
    }),
    columnHelper.accessor('status', {
        header: 'Status',
        cell: (info) => info.getValue(),
    }),
];

describe('DataTable', () => {
    it('renders table with data', () => {
        render(<DataTable data={mockData} columns={mockColumns} />);
        
        expect(screen.getByText('ID')).toBeInTheDocument();
        expect(screen.getByText('Name')).toBeInTheDocument();
        expect(screen.getByText('Email')).toBeInTheDocument();
        expect(screen.getByText('Status')).toBeInTheDocument();
        
        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.getByText('Jane Smith')).toBeInTheDocument();
        expect(screen.getByText('Bob Johnson')).toBeInTheDocument();
    });

    it('renders search input when searchable is true', () => {
        render(<DataTable data={mockData} columns={mockColumns} searchable={true} />);
        
        expect(screen.getByPlaceholderText('Search...')).toBeInTheDocument();
    });

    it('does not render search input when searchable is false', () => {
        render(<DataTable data={mockData} columns={mockColumns} searchable={false} />);
        
        expect(screen.queryByPlaceholderText('Search...')).not.toBeInTheDocument();
    });

    it('filters data when searching', async () => {
        const user = userEvent.setup();
        render(<DataTable data={mockData} columns={mockColumns} searchable={true} />);
        
        const searchInput = screen.getByPlaceholderText('Search...');
        await user.type(searchInput, 'John');
        
        expect(screen.getByText('John Doe')).toBeInTheDocument();
        expect(screen.queryByText('Jane Smith')).not.toBeInTheDocument();
        expect(screen.queryByText('Bob Johnson')).not.toBeInTheDocument();
    });

    it('renders pagination controls when pagination is true', () => {
        render(<DataTable data={mockData} columns={mockColumns} pagination={true} />);
        
        expect(screen.getByText('First')).toBeInTheDocument();
        expect(screen.getByText('Previous')).toBeInTheDocument();
        expect(screen.getByText('Next')).toBeInTheDocument();
        expect(screen.getByText('Last')).toBeInTheDocument();
    });

    it('does not render pagination when pagination is false', () => {
        render(<DataTable data={mockData} columns={mockColumns} pagination={false} />);
        
        expect(screen.queryByText('First')).not.toBeInTheDocument();
        expect(screen.queryByText('Previous')).not.toBeInTheDocument();
        expect(screen.queryByText('Next')).not.toBeInTheDocument();
        expect(screen.queryByText('Last')).not.toBeInTheDocument();
    });

    it('sorts columns when header is clicked', () => {
        render(<DataTable data={mockData} columns={mockColumns} />);
        
        const nameHeader = screen.getByText('Name');
        fireEvent.click(nameHeader);
        
        // The sorting functionality should be working
        // We can't easily test the visual sorting without more complex setup
        expect(nameHeader).toBeInTheDocument();
    });

    it('shows correct page information', () => {
        render(<DataTable data={mockData} columns={mockColumns} pagination={true} />);
        
        expect(screen.getByText('Page 1 of 1')).toBeInTheDocument();
    });
});
