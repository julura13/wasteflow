import { Head, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Save, Building } from 'lucide-react';
import { Link } from '@inertiajs/react';
import AddressAutocomplete from '@/Components/AddressAutocomplete';

export default function Create({ companies }) {
    const { data, setData, post, processing, errors } = useForm({
        company_id: '',
        name: '',
        email: '',
        phone: '',
        address: '',
        contact_person: '',
        is_active: true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/branches');
    };

    return (
        <DashboardLayout title="Create Branch">
            <Head title="Create Branch" />

            <div className="mb-6">
                <Link
                    href="/branches"
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Branches
                </Link>
            </div>

            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 mb-6">
                        Create New Branch
                    </h3>

                    {companies.length === 0 && (
                        <div className="mb-6 rounded-lg bg-yellow-50 border border-yellow-200 p-4">
                            <div className="flex">
                                <Building className="h-5 w-5 text-yellow-400 mr-3" />
                                <div>
                                    <h3 className="text-sm font-medium text-yellow-800">
                                        No companies available
                                    </h3>
                                    <p className="mt-1 text-sm text-yellow-700">
                                        You need to create a company first before adding branches.
                                    </p>
                                    <Link
                                        href="/companies/create"
                                        className="mt-3 inline-flex items-center text-sm font-medium text-yellow-800 hover:text-yellow-900 underline"
                                    >
                                        Create a company now
                                    </Link>
                                </div>
                            </div>
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <label htmlFor="company_id" className="block text-sm font-medium text-gray-700">
                                    Company *
                                </label>
                                <select
                                    id="company_id"
                                    value={data.company_id}
                                    onChange={(e) => setData('company_id', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    required
                                    disabled={companies.length === 0}
                                >
                                    <option value="">Select a company</option>
                                    {companies.map((company) => (
                                        <option key={company.id} value={company.id}>
                                            {company.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.company_id && (
                                    <p className="mt-1 text-sm text-red-600">{errors.company_id}</p>
                                )}
                                <p className="mt-1 text-sm text-gray-500">
                                    Select the parent company for this branch. For small companies without branches, you can skip creating branches and directly create sites.
                                </p>
                            </div>

                            <div>
                                <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                    Branch Name *
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    placeholder="e.g., Cape Town Branch"
                                    required
                                />
                                {errors.name && (
                                    <p className="mt-1 text-sm text-red-600">{errors.name}</p>
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

                            <div className="sm:col-span-2">
                                <label htmlFor="address" className="block text-sm font-medium text-gray-700">
                                    Address
                                </label>
                                <AddressAutocomplete
                                    id="address"
                                    value={data.address}
                                    onChange={(value) => setData('address', value)}
                                    placeholder="Start typing an address..."
                                    includeCoordinates={false}
                                    textarea={true}
                                    rows={3}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                />
                                {errors.address && (
                                    <p className="mt-1 text-sm text-red-600">{errors.address}</p>
                                )}
                                <p className="mt-1 text-sm text-gray-500">
                                    Start typing to search for addresses using Mapbox.
                                </p>
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
                                href="/branches"
                                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing || companies.length === 0}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                            >
                                <Save className="h-4 w-4 mr-2" />
                                {processing ? 'Creating...' : 'Create Branch'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}
