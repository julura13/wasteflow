import { Head, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Save, Info } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import AddressAutocomplete from '@/Components/AddressAutocomplete';

export default function Create({ companies }) {
    const [selectedCompany, setSelectedCompany] = useState('');
    const [useDirectCompany, setUseDirectCompany] = useState(false);
    
    const { data, setData, post, processing, errors } = useForm({
        branch_id: '',
        company_id: '',
        name: '',
        address: '',
        contact_person: '',
        phone: '',
        email: '',
        latitude: '',
        longitude: '',
        is_active: true,
    });

    // Get branches for the selected company
    const availableBranches = useMemo(() => {
        if (!selectedCompany) return [];
        const company = companies.find(c => c.id === parseInt(selectedCompany));
        return company?.branches || [];
    }, [selectedCompany, companies]);

    const handleCompanyChange = (companyId) => {
        setSelectedCompany(companyId);
        setData({
            ...data,
            branch_id: '',
            company_id: useDirectCompany ? companyId : '',
        });
    };

    const handleUseDirectCompany = (checked) => {
        setUseDirectCompany(checked);
        if (checked) {
            setData({
                ...data,
                branch_id: '',
                company_id: selectedCompany,
            });
        } else {
            setData({
                ...data,
                company_id: '',
            });
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/sites');
    };

    return (
        <DashboardLayout title="Create Site">
            <Head title="Create Site" />

            <div className="mb-6">
                <Link
                    href="/sites"
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Sites
                </Link>
            </div>

            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 mb-6">
                        Create New Site
                    </h3>

                    {/* Info for small companies */}
                    <div className="mb-6 rounded-lg bg-primary-50 border border-primary-200 p-4">
                        <div className="flex">
                            <Info className="h-5 w-5 text-primary-600 mr-3 flex-shrink-0" />
                            <div className="text-sm text-primary-800">
                                <p className="font-medium mb-1">For Small Companies</p>
                                <p>If a company doesn't have branches, you'll need to create a default branch first (e.g., "Main Branch"), then create the site under that branch. This ensures proper data organization.</p>
                            </div>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            {/* Company and Branch Selection */}
                            <div className="sm:col-span-2 border-b pb-6">
                                <h4 className="text-sm font-medium text-gray-900 mb-4">Company & Branch Association</h4>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label htmlFor="company" className="block text-sm font-medium text-gray-700">
                                            Select Company *
                                        </label>
                                        <select
                                            id="company"
                                            value={selectedCompany}
                                            onChange={(e) => handleCompanyChange(e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                            required
                                        >
                                            <option value="">Choose a company</option>
                                            {companies.map((company) => (
                                                <option key={company.id} value={company.id}>
                                                    {company.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div>
                                        <label htmlFor="branch_id" className="block text-sm font-medium text-gray-700">
                                            Select Branch {!useDirectCompany && '*'}
                                        </label>
                                        <select
                                            id="branch_id"
                                            value={data.branch_id}
                                            onChange={(e) => setData('branch_id', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                            required={!useDirectCompany}
                                            disabled={useDirectCompany || !selectedCompany || availableBranches.length === 0}
                                        >
                                            <option value="">
                                                {!selectedCompany 
                                                    ? 'Select company first' 
                                                    : availableBranches.length === 0 
                                                    ? 'No branches available'
                                                    : 'Choose a branch'}
                                            </option>
                                            {availableBranches.map((branch) => (
                                                <option key={branch.id} value={branch.id}>
                                                    {branch.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.branch_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.branch_id}</p>
                                        )}
                                        
                                        {/* Option for small companies without branches */}
                                        {selectedCompany && (
                                            <div className="mt-3">
                                                <label className="flex items-start">
                                                    <input
                                                        type="checkbox"
                                                        checked={useDirectCompany}
                                                        onChange={(e) => handleUseDirectCompany(e.target.checked)}
                                                        className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded mt-0.5"
                                                    />
                                                    <span className="ml-2 text-sm text-gray-700">
                                                        <strong>Small company without branches</strong>
                                                        <span className="block text-gray-500">
                                                            Check this if the company doesn't have branches. The site will be directly under the company.
                                                        </span>
                                                    </span>
                                                </label>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Site Details */}
                            <div>
                                <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                    Site Name *
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    placeholder="e.g., Waterfront Dock Site"
                                    required
                                />
                                {errors.name && (
                                    <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="contact_person" className="block text-sm font-medium text-gray-700">
                                    Contact Person
                                </label>
                                <input
                                    type="text"
                                    id="contact_person"
                                    value={data.contact_person}
                                    onChange={(e) => setData('contact_person', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                />
                                {errors.contact_person && (
                                    <p className="mt-1 text-sm text-red-600">{errors.contact_person}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="phone" className="block text-sm font-medium text-gray-700">
                                    Phone
                                </label>
                                <input
                                    type="tel"
                                    id="phone"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                />
                                {errors.phone && (
                                    <p className="mt-1 text-sm text-red-600">{errors.phone}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                    Email
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                />
                                {errors.email && (
                                    <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                )}
                            </div>

                            <div className="sm:col-span-2">
                                <label htmlFor="address" className="block text-sm font-medium text-gray-700">
                                    Address
                                </label>
                                <AddressAutocomplete
                                    id="address"
                                    value={data.address}
                                    onChange={(value) => setData('address', value)}
                                    onSelect={({ address, lat, lon }) => {
                                        setData({
                                            ...data,
                                            address,
                                            latitude: lat || '',
                                            longitude: lon || '',
                                        });
                                    }}
                                    placeholder="Start typing an address..."
                                    includeCoordinates={true}
                                    textarea={true}
                                    rows={2}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                />
                                {errors.address && (
                                    <p className="mt-1 text-sm text-red-600">{errors.address}</p>
                                )}
                                <p className="mt-1 text-sm text-gray-500">
                                    Start typing to search for addresses. Latitude and longitude will be automatically filled when you select an address.
                                </p>
                            </div>

                            {/* GPS Coordinates */}
                            <div>
                                <label htmlFor="latitude" className="block text-sm font-medium text-gray-700">
                                    Latitude
                                </label>
                                <input
                                    type="number"
                                    step="0.00000001"
                                    id="latitude"
                                    value={data.latitude}
                                    onChange={(e) => setData('latitude', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    placeholder="-33.9249"
                                />
                                {errors.latitude && (
                                    <p className="mt-1 text-sm text-red-600">{errors.latitude}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="longitude" className="block text-sm font-medium text-gray-700">
                                    Longitude
                                </label>
                                <input
                                    type="number"
                                    step="0.00000001"
                                    id="longitude"
                                    value={data.longitude}
                                    onChange={(e) => setData('longitude', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    placeholder="18.4241"
                                />
                                {errors.longitude && (
                                    <p className="mt-1 text-sm text-red-600">{errors.longitude}</p>
                                )}
                            </div>

                            <div className="flex items-center">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                />
                                <label htmlFor="is_active" className="ml-2 block text-sm text-gray-900">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div className="flex justify-end space-x-3">
                            <Link
                                href="/sites"
                                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                            >
                                <Save className="h-4 w-4 mr-2" />
                                {processing ? 'Creating...' : 'Create Site'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}
