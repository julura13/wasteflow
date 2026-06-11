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

export default function ContainerOptionsIndex({ containerOptions, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [modalOpen, setModalOpen] = useState(false);
    const [mode, setMode] = useState('create');
    const [editingId, setEditingId] = useState(null);

    const form = useForm({
        order_type: 'waste',
        name: '',
        is_active: true,
        default_weight: '',
        show_in_summary: false,
    });

    const columns = [
        {
            id: 'order_type',
            header: 'Order type',
            cell: ({ row }) => (
                <span className="capitalize text-gray-600 dark:text-gray-300">{row.original.order_type ?? '—'}</span>
            ),
        },
        {
            accessorKey: 'name',
            header: 'Container Option',
            cell: ({ getValue }) => <span className="font-medium text-gray-900 dark:text-gray-100">{getValue()}</span>,
        },
        {
            id: 'default_weight',
            header: 'Default weight',
            cell: ({ row }) => (
                <span className="text-gray-600">
                    {row.original.default_weight != null ? `${Number(row.original.default_weight)} kg` : '—'}
                </span>
            ),
        },
        {
            id: 'show_in_summary',
            header: 'In summary',
            cell: ({ row }) => (
                <button
                    type="button"
                    onClick={() => router.patch(route('settings.container-options.toggle-summary', row.original.id), {}, { preserveScroll: true })}
                    title="Click to toggle"
                    className={`px-2 py-1 text-xs font-medium rounded-full transition-opacity hover:opacity-70 cursor-pointer ${row.original.show_in_summary ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-500'}`}
                >
                    {row.original.show_in_summary ? 'Yes' : 'No'}
                </button>
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
            order_type: 'waste',
            name: '',
            is_active: true,
            default_weight: '',
            show_in_summary: false,
        });
        setEditingId(null);
    };

    const openCreateModal = () => {
        resetForm();
        setMode('create');
        setModalOpen(true);
    };

    const openEditModal = (option) => {
        form.clearErrors();
        form.setData({
            order_type: option.order_type ?? 'waste',
            name: option.name ?? '',
            is_active: Boolean(option.is_active),
            default_weight: option.default_weight != null ? String(option.default_weight) : '',
            show_in_summary: Boolean(option.show_in_summary),
        });
        setEditingId(option.id);
        setMode('edit');
        setModalOpen(true);
    };

    const closeModal = () => {
        setModalOpen(false);
        resetForm();
    };

    const handleDelete = (option) => {
        if (confirm(`Delete container option “${option.name}”?`)) {
            router.delete(`/settings/container-options/${option.id}`, {
                preserveScroll: true,
            });
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        if (mode === 'create') {
            form.post('/settings/container-options', {
                preserveScroll: true,
                onSuccess: closeModal,
            });
        } else if (editingId) {
            form.put(`/settings/container-options/${editingId}`, {
                preserveScroll: true,
                onSuccess: closeModal,
            });
        }
    };

    const submitSearch = (event) => {
        event.preventDefault();
        router.get('/settings/container-options', { search }, { preserveState: true, replace: true });
    };

    return (
        <DashboardLayout title="Settings • Container Options">
            <Head title="Container Options" />

            <div className="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <div className="text-sm text-gray-500 mb-1">
                        <Link href="/settings" className="hover:text-primary-600">Settings</Link>
                        <span className="mx-1">/</span>
                        <span>Container Options</span>
                    </div>
                    <h1 className="text-2xl font-semibold text-gray-900">Container Options</h1>
                    <p className="text-sm text-gray-600">Define the container options available when creating materials and orders.</p>
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
                                placeholder="Search container options..."
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
                        New Container Option
                    </button>
                </div>
            </div>

            <DataTable
                data={containerOptions.data || []}
                columns={columns}
                title="All Container Options"
                pagination={false}
            />

            {containerOptions.links && (
                <div className="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="text-sm text-gray-700 dark:text-gray-300">
                        Showing {containerOptions.from ?? 0} to {containerOptions.to ?? 0} of {containerOptions.total ?? 0}{' '}
                        results
                    </div>
                    <div className="flex flex-wrap gap-1">
                        {containerOptions.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url || '#'}
                                className={`px-3 py-2 text-sm font-medium rounded-md ${
                                    link.active
                                        ? 'bg-primary-600 text-white'
                                        : link.url
                                          ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600 dark:hover:bg-gray-600'
                                          : 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-500'
                                }`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}

            <Modal show={modalOpen} onClose={closeModal} maxWidth="lg">
                <form onSubmit={handleSubmit} className="p-6 space-y-6">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">
                            {mode === 'create' ? 'Create Container Option' : 'Edit Container Option'}
                        </h2>
                        <p className="text-sm text-gray-600">
                            Provide the details below and save to {mode === 'create' ? 'create a new' : 'update this'} container option.
                        </p>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="container-order-type" value="Order type" />
                            <select
                                id="container-order-type"
                                value={form.data.order_type}
                                onChange={(event) => form.setData('order_type', event.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            >
                                <option value="waste">Waste</option>
                                <option value="recycling">Recycling</option>
                            </select>
                            <InputError message={form.errors.order_type} className="mt-2" />
                        </div>
                        <div>
                            <InputLabel htmlFor="container-name" value="Name" />
                            <TextInput
                                id="container-name"
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                className="mt-1 block w-full"
                                required
                                autoFocus
                            />
                            <InputError message={form.errors.name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="container-default-weight" value="Default weight (kg)" />
                            <TextInput
                                id="container-default-weight"
                                type="number"
                                min="0"
                                step="0.01"
                                value={form.data.default_weight}
                                onChange={(event) => form.setData('default_weight', event.target.value)}
                                className="mt-1 block w-full"
                                placeholder="Optional"
                            />
                            <InputError message={form.errors.default_weight} className="mt-2" />
                        </div>

                        <label className="inline-flex items-center space-x-2">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(event) => form.setData('is_active', event.target.checked)}
                                className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                            />
                            <span className="text-sm text-gray-700">Active</span>
                        </label>
                        <label className="inline-flex items-center space-x-2">
                            <input
                                type="checkbox"
                                checked={form.data.show_in_summary}
                                onChange={(event) => form.setData('show_in_summary', event.target.checked)}
                                className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                            />
                            <span className="text-sm text-gray-700">Show in Container Summary (dashboard)</span>
                        </label>
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
