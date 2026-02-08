import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AddressAutocomplete from '@/Components/AddressAutocomplete';
import { ArrowLeft, Edit, Plus, MapPin, Phone, Mail, User, Building, X, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Show({ branch }) {
    // Collection Point modal state
    const [collectionPointModalOpen, setCollectionPointModalOpen] = useState(false);
    const [collectionPointMode, setCollectionPointMode] = useState('create');
    const [editingCollectionPointId, setEditingCollectionPointId] = useState(null);

    // Collection Point form
    const collectionPointForm = useForm({
        branch_id: branch.id.toString(),
        name: '',
        address: '',
        contact_person: '',
        phone: '',
        email: '',
        latitude: '',
        longitude: '',
        is_active: true,
    });

    // Reset collection point form
    const resetCollectionPointForm = () => {
        collectionPointForm.reset();
        collectionPointForm.clearErrors();
        collectionPointForm.setData({
            branch_id: branch.id.toString(),
            name: '',
            address: '',
            contact_person: '',
            phone: '',
            email: '',
            latitude: '',
            longitude: '',
            is_active: true,
        });
        setEditingCollectionPointId(null);
    };

    // Collection Point modal handlers
    const openCreateCollectionPointModal = () => {
        resetCollectionPointForm();
        setCollectionPointMode('create');
        setCollectionPointModalOpen(true);
    };

    const openEditCollectionPointModal = async (collectionPointId) => {
        resetCollectionPointForm();
        setEditingCollectionPointId(collectionPointId);
        setCollectionPointMode('edit');
        
        // Fetch collection point data
        try {
            const response = await fetch(`/collection-points/${collectionPointId}/edit`, {
                headers: {
                    'Accept': 'application/json',
                },
            });
            const data = await response.json();
            if (data.site) {
                const collectionPoint = data.site;
                collectionPointForm.setData({
                    branch_id: collectionPoint.branch_id?.toString() || branch.id.toString(),
                    name: collectionPoint.name || '',
                    address: collectionPoint.address || '',
                    contact_person: collectionPoint.contact_person || '',
                    phone: collectionPoint.phone || '',
                    email: collectionPoint.email || '',
                    latitude: collectionPoint.latitude?.toString() || '',
                    longitude: collectionPoint.longitude?.toString() || '',
                    is_active: collectionPoint.is_active !== undefined ? collectionPoint.is_active : true,
                });
            }
        } catch (error) {
            console.error('Error fetching collection point:', error);
        }
        
        setCollectionPointModalOpen(true);
    };

    const closeCollectionPointModal = () => {
        setCollectionPointModalOpen(false);
        resetCollectionPointForm();
    };

    const handleCollectionPointSubmit = (e) => {
        e.preventDefault();
        
        if (collectionPointMode === 'create') {
            collectionPointForm.post('/collection-points', {
                preserveScroll: true,
                onSuccess: () => {
                    closeCollectionPointModal();
                    router.reload({ only: ['branch'] });
                },
            });
        } else if (editingCollectionPointId) {
            collectionPointForm.put(`/collection-points/${editingCollectionPointId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    closeCollectionPointModal();
                    router.reload({ only: ['branch'] });
                },
            });
        }
    };

    const handleDeleteCollectionPoint = (siteId) => {
        if (confirm('Are you sure you want to delete this collection point? This action cannot be undone.')) {
            router.delete(`/collection-points/${siteId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['branch'] });
                },
            });
        }
    };

    const detailRow = (label, value) => (
        <div className="flex flex-col">
            <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{label}</span>
            <span className="text-sm text-gray-900 dark:text-gray-100 font-medium">{value ?? '—'}</span>
        </div>
    );

    return (
        <DashboardLayout title={branch.name}>
            <Head title={`Branch: ${branch.name}`} />

            <div className="mb-6">
                <Link
                    href={`/companies/${branch.company.id}`}
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Company
                </Link>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
                {/* Branch Details */}
                <div className="lg:col-span-2">
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                        <div className="px-4 py-4 sm:p-5">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-base leading-6 font-medium text-gray-900 dark:text-gray-100">
                                    Branch Information
                                </h3>
                                <Link
                                    href={`/branches/${branch.id}/edit`}
                                    className="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded-md text-primary-700 bg-primary-100 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                >
                                    <Edit className="h-3 w-3 mr-1" />
                                    Edit
                                </Link>
                            </div>

                            <div className="grid grid-cols-1 gap-4">
                                <div>
                                    <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Company</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        <Link
                                            href={`/companies/${branch.company.id}`}
                                            className="text-primary-600 hover:text-primary-800"
                                        >
                                            {branch.company.name}
                                        </Link>
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Branch Name</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{branch.name}</dd>
                                </div>

                                <div>
                                    <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Contact Person</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-center">
                                        <User className="h-3 w-3 mr-1 text-gray-400 dark:text-gray-500" />
                                        {branch.contact_person || 'N/A'}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-center">
                                        <Phone className="h-3 w-3 mr-1 text-gray-400 dark:text-gray-500" />
                                        {branch.phone || 'N/A'}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-center">
                                        <Mail className="h-3 w-3 mr-1 text-gray-400 dark:text-gray-500" />
                                        {branch.email || 'N/A'}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd className="mt-1">
                                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                            branch.is_active 
                                                ? 'bg-green-100 text-green-800' 
                                                : 'bg-red-100 text-red-800'
                                        }`}>
                                            {branch.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </dd>
                                </div>

                                {branch.address && (
                                    <div>
                                        <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Address</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-start">
                                            <MapPin className="h-3 w-3 mr-1 text-gray-400 dark:text-gray-500 mt-0.5" />
                                            <span className="text-xs">{branch.address}</span>
                                        </dd>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Collection Points */}
                <div className="lg:col-span-3">
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                        <div className="px-4 py-5 sm:p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                    Collection Points
                                </h3>
                                <div className="flex items-center space-x-2">
                                    <span className="text-sm text-gray-500 dark:text-gray-400">
                                        {branch.sites?.length || 0} {branch.sites?.length === 1 ? 'point' : 'points'}
                                    </span>
                                    <button
                                        onClick={openCreateCollectionPointModal}
                                        className="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-primary-700 bg-primary-100 hover:bg-primary-200"
                                    >
                                        <Plus className="h-3 w-3 mr-1" />
                                        Add
                                    </button>
                                </div>
                            </div>

                            {branch.sites && branch.sites.length > 0 ? (
                                <div className="space-y-4">
                                    {branch.sites.map((site) => (
                                        <div key={site.id} className="border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <div className="flex items-start justify-between mb-3">
                                                <div className="flex-1">
                                                    <div className="flex items-center space-x-2 mb-2">
                                                        <MapPin className="h-4 w-4 text-gray-400" />
                                                        <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {site.name}
                                                        </h4>
                                                    </div>
                                                    
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                                                        {site.contact_person && (
                                                            <div>
                                                                <span className="text-xs text-gray-500 dark:text-gray-400">Contact Person</span>
                                                                <p className="text-sm text-gray-900 dark:text-gray-100 flex items-center">
                                                                    <User className="h-3 w-3 mr-1 text-gray-400" />
                                                                    {site.contact_person}
                                                                </p>
                                                            </div>
                                                        )}
                                                        {site.phone && (
                                                            <div>
                                                                <span className="text-xs text-gray-500 dark:text-gray-400">Phone</span>
                                                                <p className="text-sm text-gray-900 dark:text-gray-100 flex items-center">
                                                                    <Phone className="h-3 w-3 mr-1 text-gray-400" />
                                                                    {site.phone}
                                                                </p>
                                                            </div>
                                                        )}
                                                        {site.email && (
                                                            <div>
                                                                <span className="text-xs text-gray-500 dark:text-gray-400">Email</span>
                                                                <p className="text-sm text-gray-900 dark:text-gray-100 flex items-center">
                                                                    <Mail className="h-3 w-3 mr-1 text-gray-400" />
                                                                    {site.email}
                                                                </p>
                                                            </div>
                                                        )}
                                                        {site.address && (
                                                            <div className="md:col-span-2">
                                                                <span className="text-xs text-gray-500 dark:text-gray-400">Address</span>
                                                                <p className="text-sm text-gray-900 dark:text-gray-100 flex items-start">
                                                                    <MapPin className="h-3 w-3 mr-1 text-gray-400 mt-0.5 flex-shrink-0" />
                                                                    <span>{site.address}</span>
                                                                </p>
                                                            </div>
                                                        )}
                                                        {(site.latitude && site.longitude) && (
                                                            <div className="md:col-span-2">
                                                                <span className="text-xs text-gray-500 dark:text-gray-400">Coordinates</span>
                                                                <p className="text-sm text-gray-900 dark:text-gray-100">
                                                                    {site.latitude}, {site.longitude}
                                                                </p>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="flex items-center space-x-2 ml-4">
                                                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                        site.is_active 
                                                            ? 'bg-green-100 text-green-800' 
                                                            : 'bg-red-100 text-red-800'
                                                    }`}>
                                                        {site.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="flex items-center justify-end space-x-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                                                <button
                                                    onClick={() => openEditCollectionPointModal(site.id)}
                                                    className="text-amber-600 hover:text-amber-800 text-xs font-medium"
                                                    title="Edit Collection Point"
                                                >
                                                    <Edit className="h-3 w-3 inline mr-1" />
                                                    Edit
                                                </button>
                                                {(!site.orders || site.orders.length === 0) && (
                                                    <button
                                                        onClick={() => handleDeleteCollectionPoint(site.id)}
                                                        className="text-red-600 hover:text-red-800 text-xs font-medium"
                                                        title="Delete Collection Point"
                                                    >
                                                        <Trash2 className="h-3 w-3 inline mr-1" />
                                                        Delete
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div>
                                    <p className="text-sm text-gray-500 mb-2">No collection points found.</p>
                                    <button
                                        onClick={openCreateCollectionPointModal}
                                        className="text-sm text-primary-600 hover:text-primary-800"
                                    >
                                        Create first collection point
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Collection Point Modal */}
            <Modal show={collectionPointModalOpen} onClose={closeCollectionPointModal} maxWidth="2xl">
                <form onSubmit={handleCollectionPointSubmit} className="p-6 space-y-6">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">
                            {collectionPointMode === 'create' ? 'Create Collection Point' : 'Edit Collection Point'}
                        </h2>
                        <p className="text-sm text-gray-600">
                            {collectionPointMode === 'create' ? 'Create a new collection point for this branch.' : 'Update collection point information.'}
                        </p>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="cp-name" value="Collection Point Name *" />
                            <TextInput
                                id="cp-name"
                                value={collectionPointForm.data.name}
                                onChange={(e) => collectionPointForm.setData('name', e.target.value)}
                                className="mt-1 block w-full"
                                required
                                autoFocus
                            />
                            <InputError message={collectionPointForm.errors.name} className="mt-2" />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="cp-contact" value="Contact Person" />
                                <TextInput
                                    id="cp-contact"
                                    value={collectionPointForm.data.contact_person}
                                    onChange={(e) => collectionPointForm.setData('contact_person', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={collectionPointForm.errors.contact_person} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="cp-phone" value="Phone" />
                                <TextInput
                                    id="cp-phone"
                                    type="tel"
                                    value={collectionPointForm.data.phone}
                                    onChange={(e) => collectionPointForm.setData('phone', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={collectionPointForm.errors.phone} className="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="cp-email" value="Email" />
                            <TextInput
                                id="cp-email"
                                type="email"
                                value={collectionPointForm.data.email}
                                onChange={(e) => collectionPointForm.setData('email', e.target.value)}
                                className="mt-1 block w-full"
                            />
                            <InputError message={collectionPointForm.errors.email} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="cp-address" value="Address" />
                            <AddressAutocomplete
                                id="cp-address"
                                value={collectionPointForm.data.address}
                                onChange={(value) => collectionPointForm.setData('address', value)}
                                onSelect={({ address, lat, lon }) => {
                                    collectionPointForm.setData({
                                        ...collectionPointForm.data,
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
                            <InputError message={collectionPointForm.errors.address} className="mt-2" />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="cp-latitude" value="Latitude" />
                                <TextInput
                                    id="cp-latitude"
                                    type="number"
                                    step="0.00000001"
                                    value={collectionPointForm.data.latitude}
                                    onChange={(e) => collectionPointForm.setData('latitude', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="-33.9249"
                                />
                                <InputError message={collectionPointForm.errors.latitude} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="cp-longitude" value="Longitude" />
                                <TextInput
                                    id="cp-longitude"
                                    type="number"
                                    step="0.00000001"
                                    value={collectionPointForm.data.longitude}
                                    onChange={(e) => collectionPointForm.setData('longitude', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="18.4241"
                                />
                                <InputError message={collectionPointForm.errors.longitude} className="mt-2" />
                            </div>
                        </div>

                        <div className="flex items-center">
                            <input
                                id="cp-active"
                                type="checkbox"
                                checked={collectionPointForm.data.is_active}
                                onChange={(e) => collectionPointForm.setData('is_active', e.target.checked)}
                                className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                            />
                            <label htmlFor="cp-active" className="ml-2 block text-sm text-gray-900">
                                Active
                            </label>
                        </div>
                    </div>

                    <div className="flex justify-end space-x-3">
                        <SecondaryButton type="button" onClick={closeCollectionPointModal}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" disabled={collectionPointForm.processing}>
                            <Save className="h-4 w-4 mr-2" />
                            {collectionPointForm.processing ? (collectionPointMode === 'create' ? 'Creating...' : 'Updating...') : (collectionPointMode === 'create' ? 'Create Collection Point' : 'Update Collection Point')}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </DashboardLayout>
    );
}

