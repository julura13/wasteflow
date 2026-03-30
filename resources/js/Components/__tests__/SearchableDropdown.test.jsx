import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SearchableDropdown from '../SearchableDropdown';

const companies = [
    { id: 1, name: 'Acme Corp' },
    { id: 2, name: 'Beta Industries' },
    { id: 3, name: 'Gamma Ltd' },
];

describe('SearchableDropdown', () => {
    it('renders placeholder when no value', () => {
        const onChange = jest.fn();
        render(
            <SearchableDropdown
                id="co"
                name="company_id"
                options={companies}
                value=""
                onChange={onChange}
                placeholder="Select Company"
            />
        );
        expect(screen.getByText('Select Company')).toBeInTheDocument();
    });

    it('filters options when searching', async () => {
        const user = userEvent.setup();
        const onChange = jest.fn();
        render(
            <SearchableDropdown
                id="co"
                name="company_id"
                options={companies}
                value=""
                onChange={onChange}
                placeholder="Select Company"
                menuMatchTriggerWidth
            />
        );

        await user.click(screen.getByText('Select Company'));
        const searchInput = screen.getByPlaceholderText('Search...');
        await user.type(searchInput, 'Beta');

        expect(await screen.findByText('Beta Industries')).toBeInTheDocument();
        expect(screen.queryByText('Acme Corp')).not.toBeInTheDocument();
        expect(screen.queryByText('Gamma Ltd')).not.toBeInTheDocument();
    });
});
