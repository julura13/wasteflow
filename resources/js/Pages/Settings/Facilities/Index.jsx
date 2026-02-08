import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DataTable from '@/Components/Dashboard/DataTable';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Plus, Edit2, Trash2, Search, RefreshCw } from 'lucide-react';

export default function FacilitiesIndex({ facilities, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [modalOpen, setModalOpen] = useState(false);
    const [mode, setMode] = useState('create');
    const [editingId, setEditingId] = useState(null);

    const form = useForm({
        name: '',
        description: '',
        facility_type: '',
        requires_weight: true,
        is_active: true,
    });

    const columns = [
        {
            accessorKey: 'name',
            header: 'Facility',
            cell: ({ getValue }) => <span className="font-medium text-gray-900">{getValue()}</span>,
        },
        {
            accessorKey: 'facility_type',
            header: 'Type',
            cell: ({ getValue }) => <span className="text-sm text-gray-600 capitalize">{getValue() ?? 'general'}</span>,
        },
        {
            accessorKey: 'requires_weight',
            header: 'Requires Weight',
            cell: ({ getValue }) => (
                <span className={`px-2 py-1 text-xs font-medium rounded-full ${getValue() ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500'}`}>
                    {getValue() ? 'Yes' : 'No'}
                </span>
            ),
        },
        {
            id: 'is_active',
            header: 'Status',
            cell: ({ row }) => (
                <span className={`px-2 py-1 text-xs font-medium rounded-full ${row.original.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'}`}>
                    {row.original.is_active ? 'Active' : 'Inactive'}
                </span>
            ),
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex space-x-2">
                    <button onClick={() => openEditModal(row.original)} className="text-amber-600 hover:text-amber-800">
                        <Edit2 className="h-4 w-4" />
                    </button>
                    <button onClick={() => handleDelete(row.original)} className="text-red-600 hover:text-red-800">
                        <Trash2 className="h-4 w-4" />
                    </button>
                </div>
            ),
        },
    ];

    const resetForm = () => {
        form.reset();
        form.clearErrors();
        form.setData({
            name: '',
            description: '',
            facility_type: '',
            requires_weight: true,
            is_active: true,
        });
        setEditingId(null);
    };

    const openCreateModal = () => {
        resetForm();
        setMode('create');
        setModalOpen(true);
    };

    const openEditModal = (facility) => {
        form.clearErrors();
        form.setData({
            name: facility.name ?? '',
            description: facility.description ?? '',
            facility_type: facility.facility_type ?? '',
            requires_weight: Boolean(facility.requires_weight),
            is_active: Boolean(facility.is_active),
        });
        setEditingId(facility.id);
        setMode('edit');
        setModalOpen(true);
    };

    const closeModal = () => {
        setModalOpen(false);
        resetForm();
    };

    const handleDelete = (facility) => {
        if (confirm(`Delete facility “${facility.name}”?`)) {
            router.delete(`/settings/facilities/${facility.id}`, {
                preserveScroll: true,
            });
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        if (mode === 'create') {
            form.post('/settings/facilities', {
                preserveScroll: true,
                onSuccess: closeModal,
            });
        } else if (editingId) {
            form.put(`/settings/facilities/${editingId}`, {
                preserveScroll: true,
                onSuccess: closeModal,
            });
        }
    };

    const submitSearch = (event) => {
        event.preventDefault();
        router.get('/settings/facilities', { search }, { preserveState: true, replace: true });
    };

    return (
        <DashboardLayout title="Settings • Facilities">
            <Head title="Facilities" />

            <div className="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <div className="text-sm text-gray-500 mb-1">
                        <Link href="/settings" className="hover:text-primary-600">Settings</Link>
                        <span className="mx-1">/</span>
                        <span>Facilities</span>
                    </div>
                    <h1 className="text-2xl font-semibold text-gray-900">Facilities</h1>
                    <p className="text-sm text-gray-600">Control the facilities available for waste streams and materials.</p>
                </div>
                <div className="flex space-x-2">
                    <form onSubmit={submitSearch} className="flex items-center space-x-2">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input
                                type="text"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                className="pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Search facilities..."
                            />
                        </div>
                        <button
                            type="submit"
                            className="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700"
                        >
                            <RefreshCw className="h-4 w-4 mr-1" />
                            Apply
                        </button>
                    </form>
                    <button
                        onClick={openCreateModal}
                        className="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700"
                    >
                        <Plus className="h-4 w-4 mr-1" />
                        New Facility
                    </button>
                </div>
            </div>

            <DataTable data={facilities.data} columns={columns} title="All Facilities" />

            <Modal show={modalOpen} onClose={closeModal} maxWidth="lg">
                <form onSubmit={handleSubmit} className="p-6 space-y-6">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">
                            {mode === 'create' ? 'Create Facility' : 'Edit Facility'}
                        </h2>
                        <p className="text-sm text-gray-600">
                            Provide the details below and save to {mode === 'create' ? 'create a new' : 'update this'} facility.
                        </p>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="facility-name" value="Name" />
                            <TextInput
                                id="facility-name"
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                className="mt-1 block w-full"
                                required
                                autoFocus
                            />
                            <InputError message={form.errors.name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="facility-description" value="Description" />
                            <TextInput
                                id="facility-description"
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                                className="mt-1 block w-full"
                                placeholder="Optional"
                            />
                            <InputError message={form.errors.description} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="facility-type" value="Facility Type" />
                            <TextInput
                                id="facility-type"
                                value={form.data.facility_type}
                                onChange={(event) => form.setData('facility_type', event.target.value)}
                                className="mt-1 block w-full"
                                placeholder="e.g. landfill, recycling, compost"
                            />
                            <InputError message={form.errors.facility_type} className="mt-2" />
                        </div>

                        <div className="flex items-center space-x-4">
                            <label className="inline-flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    checked={form.data.requires_weight}
                                    onChange={(event) => form.setData('requires_weight', event.target.checked)}
                                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                />
                                <span className="text-sm text-gray-700">Requires Weight</span>
                            </label>
                            <label className="inline-flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    checked={form.data.is_active}
                                    onChange={(event) => form.setData('is_active', event.target.checked)}
                                    className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                />
                                <span className="text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>

                    <div className="flex justify-end space-x-3">
                        <SecondaryButton type="button" onClick={closeModal}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving...' : 'Save'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </DashboardLayout>
    );
}
