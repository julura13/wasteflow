import { useState, useEffect, useMemo } from 'react';
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

export default function Show({ company, companies = [], assignedUsers = [] }) {
    const [branchModalOpen, setBranchModalOpen] = useState(false);
    const [branchMode, setBranchMode] = useState('create');
    const [editingBranchId, setEditingBranchId] = useState(null);

    const [collectionPointModalOpen, setCollectionPointModalOpen] = useState(false);
    const [collectionPointMode, setCollectionPointMode] = useState('create');
    const [editingCollectionPointId, setEditingCollectionPointId] = useState(null);
    const [selectedCompanyForCollectionPoint, setSelectedCompanyForCollectionPoint] = useState(company.id.toString());

    const branchForm = useForm({
        company_id: company.id.toString(),
        name: '',
        email: '',
        phone: '',
        address: '',
        contact_person: '',
        is_active: true,
    });

    const collectionPointForm = useForm({
        branch_id: '',
        name: '',
        address: '',
        contact_person: '',
        phone: '',
        email: '',
        latitude: '',
        longitude: '',
        is_active: true,
    });

    const availableBranches = useMemo(() => {
        if (!selectedCompanyForCollectionPoint) return [];
        
        if (parseInt(selectedCompanyForCollectionPoint) === company.id) {
            return company.branches || [];
        }
        
        const selectedCompany = companies.find(c => c.id === parseInt(selectedCompanyForCollectionPoint));
        return selectedCompany?.branches || [];
    }, [selectedCompanyForCollectionPoint, companies, company]);

    const resetBranchForm = () => {
        branchForm.reset();
        branchForm.clearErrors();
        branchForm.setData({
            company_id: company.id.toString(),
            name: '',
            email: '',
            phone: '',
            address: '',
            contact_person: '',
            is_active: true,
        });
        setEditingBranchId(null);
    };

    const resetCollectionPointForm = () => {
        collectionPointForm.reset();
        collectionPointForm.clearErrors();
        setSelectedCompanyForCollectionPoint(company.id.toString());
        collectionPointForm.setData({
            branch_id: '',
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

    const openCreateBranchModal = () => {
        resetBranchForm();
        setBranchMode('create');
        setBranchModalOpen(true);
    };

    const openEditBranchModal = async (branchId) => {
        resetBranchForm();
        setEditingBranchId(branchId);
        setBranchMode('edit');
        
        try {
            const response = await fetch(`/branches/${branchId}/edit`, {
                headers: {
                    'Accept': 'application/json',
                },
            });
            const data = await response.json();
            if (data.branch) {
                branchForm.setData({
                    company_id: data.branch.company_id?.toString() || company.id.toString(),
                    name: data.branch.name || '',
                    email: data.branch.email || '',
                    phone: data.branch.phone || '',
                    address: data.branch.address || '',
                    contact_person: data.branch.contact_person || '',
                    is_active: data.branch.is_active !== undefined ? data.branch.is_active : true,
                });
            }
        } catch (error) {
            console.error('Error fetching branch:', error);
        }
        
        setBranchModalOpen(true);
    };

    const closeBranchModal = () => {
        setBranchModalOpen(false);
        resetBranchForm();
    };

    const handleBranchSubmit = (e) => {
        e.preventDefault();
        if (branchMode === 'create') {
            branchForm.post('/branches', {
                preserveScroll: true,
                onSuccess: () => {
                    closeBranchModal();
                    router.reload({ only: ['company'] });
                },
            });
        } else if (editingBranchId) {
            branchForm.put(`/branches/${editingBranchId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    closeBranchModal();
                    router.reload({ only: ['company'] });
                },
            });
        }
    };

    const openCreateCollectionPointModal = (branchId = null) => {
        resetCollectionPointForm();
        setCollectionPointMode('create');
        if (branchId) {
            collectionPointForm.setData('branch_id', branchId.toString());
        }
        setCollectionPointModalOpen(true);
    };

    const openEditCollectionPointModal = async (collectionPointId) => {
        resetCollectionPointForm();
        setEditingCollectionPointId(collectionPointId);
        setCollectionPointMode('edit');
        
        try {
            const response = await fetch(`/collection-points/${collectionPointId}/edit`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            if (data.site) {
                const collectionPoint = data.site;
                const initialCompany = collectionPoint.branch?.company_id ? collectionPoint.branch.company_id.toString() : company.id.toString();
                
                setSelectedCompanyForCollectionPoint(initialCompany);
                
                collectionPointForm.setData({
                    branch_id: collectionPoint.branch_id?.toString() || '',
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
            alert('Failed to load collection point data. Please try again.');
            return;
        }
        
        setCollectionPointModalOpen(true);
    };

    const closeCollectionPointModal = () => {
        setCollectionPointModalOpen(false);
        resetCollectionPointForm();
    };

    const handleCompanyChangeForCollectionPoint = (companyId) => {
        setSelectedCompanyForCollectionPoint(companyId);
        collectionPointForm.setData('branch_id', '');
    };

    const handleCollectionPointSubmit = (e) => {
        e.preventDefault();
        
        if (!collectionPointForm.data.branch_id) {
            collectionPointForm.setError('branch_id', 'A branch must be selected.');
            return;
        }

        if (collectionPointMode === 'create') {
            collectionPointForm.post('/collection-points', {
                preserveScroll: true,
                onSuccess: () => {
                    closeCollectionPointModal();
                    router.reload({ only: ['company'] });
                },
            });
        } else if (editingCollectionPointId) {
            collectionPointForm.put(`/collection-points/${editingCollectionPointId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    closeCollectionPointModal();
                    router.reload({ only: ['company'] });
                },
                onError: (errors) => {
                    console.error('Update errors:', errors);
                },
            });
        }
    };

    const handleDeleteBranch = (branchId) => {
        const branch = company.branches?.find(b => b.id === branchId);
        const hasOrders = branch?.sites?.some(site => site.orders && site.orders.length > 0);
        
        if (hasOrders) {
            alert('This branch cannot be deleted because it has collection points with associated orders.');
            return;
        }
        
        if (confirm('Are you sure you want to delete this branch? This action cannot be undone.')) {
            router.delete(`/branches/${branchId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['company'] });
                },
            });
        }
    };

    const handleDeleteCollectionPoint = (siteId) => {
        const branch = company.branches?.find(b => b.sites?.some(s => s.id === siteId));
        const site = branch?.sites?.find(s => s.id === siteId);
        const hasOrders = site?.orders && site.orders.length > 0;
        
        if (hasOrders) {
            alert('This collection point cannot be deleted because it has associated orders.');
            return;
        }
        
        if (confirm('Are you sure you want to delete this collection point? This action cannot be undone.')) {
            router.delete(`/collection-points/${siteId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['company'] });
                },
            });
        }
    };

    return (
        <DashboardLayout title={company.name}>
            <Head title={`Company: ${company.name}`} />

            <div className="mb-6">
                <Link
                    href="/companies"
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Companies
                </Link>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
                {/* Company Details - Made smaller */}
                <div className="lg:col-span-2">
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                        <div className="px-4 py-4 sm:p-5">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-base leading-6 font-medium text-gray-900 dark:text-gray-100">
                                    Company Information
                                </h3>
                                <Link
                                    href={`/companies/${company.id}/edit`}
                                    className="inline-flex items-center px-2 py-1 border border-transparent text-xs leading-4 font-medium rounded-md text-primary-700 bg-primary-100 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                >
                                    <Edit className="h-3 w-3 mr-1" />
                                    Edit
                                </Link>
                            </div>

                            <div className="grid grid-cols-1 gap-4">
                                <div>
                                    <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Company Name</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{company.name}</dd>
                                </div>

                                {company.registration_number && (
                                    <div>
                                        <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Registration Number</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100">{company.registration_number}</dd>
                                    </div>
                                )}

                                <div>
                                    <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Contact Person</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-center">
                                        <User className="h-3 w-3 mr-1 text-gray-400 dark:text-gray-500" />
                                        {company.contact_person || 'N/A'}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-center">
                                        <Phone className="h-3 w-3 mr-1 text-gray-400 dark:text-gray-500" />
                                        {company.phone || 'N/A'}
                                    </dd>
                                </div>

                                <div>
                                    <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-center">
                                        <Mail className="h-3 w-3 mr-1 text-gray-400 dark:text-gray-500" />
                                        {company.email || 'N/A'}
                                    </dd>
                                </div>

                                <div>
                                        <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd className="mt-1">
                                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                            company.is_active 
                                                ? 'bg-green-100 text-green-800' 
                                                : 'bg-red-100 text-red-800'
                                        }`}>
                                            {company.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </dd>
                                </div>

                                {company.address && (
                                    <div>
                                        <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Address</dt>
                                        <dd className="mt-1 text-sm text-gray-900 dark:text-gray-100 flex items-start">
                                            <MapPin className="h-3 w-3 mr-1 text-gray-400 dark:text-gray-500 mt-0.5" />
                                            <span className="text-xs">{company.address}</span>
                                        </dd>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Branches and Collection Points */}
                <div className="lg:col-span-3 space-y-6">
                    {/* Branches */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                        <div className="px-4 py-5 sm:p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                    Branches
                                </h3>
                                <div className="flex items-center space-x-2">
                                    <span className="text-sm text-gray-500 dark:text-gray-400">
                                        {company.branches?.length || 0} branches
                                    </span>
                                    <button
                                        onClick={openCreateBranchModal}
                                        className="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md text-primary-700 bg-primary-100 hover:bg-primary-200"
                                    >
                                        <Plus className="h-3 w-3 mr-1" />
                                        Add
                                    </button>
                                </div>
                            </div>

                            {company.branches && company.branches.length > 0 ? (
                                <div className="space-y-3">
                                    {company.branches.map((branch) => (
                                        <div key={branch.id} className="border rounded-lg p-3 hover:bg-gray-50 transition-colors">
                                            <div className="flex items-center justify-between mb-2">
                                                <div className="flex-1">
                                                    <div className="flex items-center space-x-2">
                                                        <Link
                                                            href={`/branches/${branch.id}`}
                                                            className="text-sm font-medium text-primary-600 hover:text-primary-800"
                                                        >
                                                            {branch.name}
                                                        </Link>
                                                        <button
                                                            onClick={() => openEditBranchModal(branch.id)}
                                                            className="text-gray-400 hover:text-amber-600"
                                                            title="Edit Branch"
                                                        >
                                                            <Edit className="h-3 w-3" />
                                                        </button>
                                                        {!branch.sites?.some(site => site.orders && site.orders.length > 0) && (
                                                            <button
                                                                onClick={() => handleDeleteBranch(branch.id)}
                                                                className="text-gray-400 hover:text-red-600"
                                                                title="Delete Branch"
                                                            >
                                                                <Trash2 className="h-3 w-3" />
                                                            </button>
                                                        )}
                                                    </div>
                                                    <p className="text-xs text-gray-500 mt-1">
                                                        {branch.sites?.length || 0} {branch.sites?.length === 1 ? 'collection point' : 'collection points'}
                                                    </p>
                                                </div>
                                                <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                    branch.is_active 
                                                        ? 'bg-green-100 text-green-800' 
                                                        : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {branch.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </div>
                                            {branch.sites && branch.sites.length > 0 && (
                                                <div className="mt-2 pt-2 border-t border-gray-100">
                                                    <div className="space-y-1">
                                                        {branch.sites.map((site) => (
                                                            <div
                                                                key={site.id}
                                                                className="flex items-center justify-between text-xs text-gray-600 py-1 px-2 rounded hover:bg-gray-50"
                                                            >
                                                                <div className="flex items-center space-x-1">
                                                                    <MapPin className="h-3 w-3" />
                                                                    <span>{site.name}</span>
                                                                </div>
                                                                <div className="flex items-center space-x-1">
                                                                    <button
                                                                        onClick={() => openEditCollectionPointModal(site.id)}
                                                                        className="text-gray-400 hover:text-amber-600"
                                                                        title="Edit Collection Point"
                                                                    >
                                                                        <Edit className="h-3 w-3" />
                                                                    </button>
                                                                    {(!site.orders || site.orders.length === 0) && (
                                                                        <button
                                                                            onClick={() => handleDeleteCollectionPoint(site.id)}
                                                                            className="text-gray-400 hover:text-red-600"
                                                                            title="Delete Collection Point"
                                                                        >
                                                                            <Trash2 className="h-3 w-3" />
                                                                        </button>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                    <button
                                                        onClick={() => openCreateCollectionPointModal(branch.id)}
                                                        className="mt-2 text-xs text-primary-600 hover:text-primary-800 flex items-center"
                                                    >
                                                        <Plus className="h-3 w-3 mr-1" />
                                                        Add Collection Point
                                                    </button>
                                                </div>
                                            )}
                                            {(!branch.sites || branch.sites.length === 0) && (
                                                <button
                                                    onClick={() => openCreateCollectionPointModal(branch.id)}
                                                    className="mt-2 text-xs text-primary-600 hover:text-primary-800 flex items-center"
                                                >
                                                    <Plus className="h-3 w-3 mr-1" />
                                                    Add Collection Point
                                                </button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div>
                                    <p className="text-sm text-gray-500 mb-2">No branches found.</p>
                                    <button
                                        onClick={openCreateBranchModal}
                                        className="text-sm text-primary-600 hover:text-primary-800"
                                    >
                                        Create first branch
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Assigned Users */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                        <div className="px-4 py-5 sm:p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                    Assigned Users
                                </h3>
                                <span className="text-sm text-gray-500 dark:text-gray-400">
                                    {assignedUsers?.length || 0} user{assignedUsers?.length !== 1 ? 's' : ''}
                                </span>
                            </div>

                            {assignedUsers && assignedUsers.length > 0 ? (
                                <div className="space-y-3">
                                    {assignedUsers.map((user) => (
                                        <div key={user.id} className="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <div className="flex items-center">
                                                {user.avatar ? (
                                                    <img
                                                        src={user.avatar}
                                                        alt={user.name}
                                                        className="h-10 w-10 rounded-full mr-3"
                                                    />
                                                ) : (
                                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary-600 text-white text-sm font-medium mr-3">
                                                        {user.name.charAt(0).toUpperCase()}
                                                    </div>
                                                )}
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900 dark:text-gray-100">{user.name}</p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">{user.email}</p>
                                                    <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1 ${
                                                        user.is_active 
                                                            ? 'bg-green-100 text-green-800' 
                                                            : 'bg-yellow-100 text-yellow-800'
                                                    }`}>
                                                        {user.is_active ? 'Active' : 'Pending'}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <span className={`px-2 py-1 text-xs font-medium rounded ${
                                                    user.role === 'manager' 
                                                        ? 'bg-blue-100 text-blue-800' 
                                                        : 'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {user.role === 'manager' ? 'Manager' : 'Viewer'}
                                                </span>
                                                <Link
                                                    href={route('users.show', user.id)}
                                                    className="text-primary-600 hover:text-primary-900 text-sm"
                                                >
                                                    View
                                                </Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">No users assigned to this company.</p>
                                    <Link
                                        href={route('users.index')}
                                        className="text-sm text-primary-600 hover:text-primary-800"
                                    >
                                        Assign users from User Management
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Branch Modal */}
            <Modal show={branchModalOpen} onClose={closeBranchModal} maxWidth="2xl">
                <form onSubmit={handleBranchSubmit} className="p-6 space-y-6">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {branchMode === 'create' ? 'Create Branch' : 'Edit Branch'}
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            {branchMode === 'create' ? 'Create a new branch for this company.' : 'Update branch information.'}
                        </p>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="branch-name" value="Branch Name *" />
                            <TextInput
                                id="branch-name"
                                value={branchForm.data.name}
                                onChange={(e) => branchForm.setData('name', e.target.value)}
                                className="mt-1 block w-full"
                                required
                                autoFocus
                            />
                            <InputError message={branchForm.errors.name} className="mt-2" />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="branch-email" value="Email" />
                                <TextInput
                                    id="branch-email"
                                    type="email"
                                    value={branchForm.data.email}
                                    onChange={(e) => branchForm.setData('email', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={branchForm.errors.email} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="branch-phone" value="Phone" />
                                <TextInput
                                    id="branch-phone"
                                    type="tel"
                                    value={branchForm.data.phone}
                                    onChange={(e) => branchForm.setData('phone', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={branchForm.errors.phone} className="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="branch-contact" value="Contact Person" />
                            <TextInput
                                id="branch-contact"
                                value={branchForm.data.contact_person}
                                onChange={(e) => branchForm.setData('contact_person', e.target.value)}
                                className="mt-1 block w-full"
                            />
                            <InputError message={branchForm.errors.contact_person} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="branch-address" value="Address" />
                            <AddressAutocomplete
                                id="branch-address"
                                value={branchForm.data.address}
                                onChange={(value) => branchForm.setData('address', value)}
                                placeholder="Start typing an address..."
                                includeCoordinates={false}
                                textarea={true}
                                rows={3}
                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                            />
                            <InputError message={branchForm.errors.address} className="mt-2" />
                        </div>

                        <div className="flex items-center">
                            <input
                                id="branch-active"
                                type="checkbox"
                                checked={branchForm.data.is_active}
                                onChange={(e) => branchForm.setData('is_active', e.target.checked)}
                                className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded"
                            />
                            <label htmlFor="branch-active" className="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                Active
                            </label>
                        </div>
                    </div>

                    <div className="flex justify-end space-x-3">
                        <SecondaryButton type="button" onClick={closeBranchModal}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" disabled={branchForm.processing}>
                            <Save className="h-4 w-4 mr-2" />
                            {branchForm.processing ? (branchMode === 'create' ? 'Creating...' : 'Updating...') : (branchMode === 'create' ? 'Create Branch' : 'Update Branch')}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Collection Point Modal */}
            <Modal show={collectionPointModalOpen} onClose={closeCollectionPointModal} maxWidth="2xl">
                <form onSubmit={handleCollectionPointSubmit} className="p-6 space-y-6">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {collectionPointMode === 'create' ? 'Create Collection Point' : 'Edit Collection Point'}
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            {collectionPointMode === 'create' ? 'Create a new collection point for a branch.' : 'Update collection point information.'}
                        </p>
                    </div>

                    <div className="space-y-4">
                        {/* Company and Branch Selection */}
                        <div className="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Company & Branch Association</h4>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="cp-company" value="Select Company *" />
                                    <select
                                        id="cp-company"
                                        value={selectedCompanyForCollectionPoint}
                                        onChange={(e) => handleCompanyChangeForCollectionPoint(e.target.value)}
                                        className="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                        required
                                    >
                                        {(companies.length > 0 ? companies : [company]).map((comp) => (
                                            <option key={comp.id} value={comp.id}>
                                                {comp.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <InputLabel htmlFor="cp-branch" value="Select Branch *" />
                                    <select
                                        id="cp-branch"
                                        value={collectionPointForm.data.branch_id}
                                        onChange={(e) => collectionPointForm.setData('branch_id', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 disabled:opacity-50 dark:disabled:bg-gray-600 sm:text-sm"
                                        required
                                        disabled={!selectedCompanyForCollectionPoint || availableBranches.length === 0}
                                    >
                                        <option value="">
                                            {!selectedCompanyForCollectionPoint 
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
                                    <InputError message={collectionPointForm.errors.branch_id} className="mt-2" />
                                    {selectedCompanyForCollectionPoint && availableBranches.length === 0 && (
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            This company has no branches. Please create a branch first.
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Collection Point Details */}
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
                                className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded"
                            />
                            <label htmlFor="cp-active" className="ml-2 block text-sm text-gray-900 dark:text-gray-300">
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
