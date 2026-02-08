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

export default function GradesIndex({ grades, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [modalOpen, setModalOpen] = useState(false);
    const [mode, setMode] = useState('create');
    const [editingId, setEditingId] = useState(null);

    const form = useForm({
        name: '',
        description: '',
        is_active: true,
    });

    const columns = [
        {
            accessorKey: 'name',
            header: 'Grade',
            cell: ({ getValue }) => <span className="font-medium text-gray-900">{getValue()}</span>,
        },
        {
            accessorKey: 'description',
            header: 'Description',
            cell: ({ getValue }) => <span className="text-sm text-gray-600">{getValue() ?? '—'}</span>,
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
            is_active: true,
        });
        setEditingId(null);
    };

    const openCreateModal = () => {
        resetForm();
        setMode('create');
        setModalOpen(true);
    };

    const openEditModal = (grade) => {
        form.clearErrors();
        form.setData({
            name: grade.name ?? '',
            description: grade.description ?? '',
            is_active: Boolean(grade.is_active),
        });
        setEditingId(grade.id);
        setMode('edit');
        setModalOpen(true);
    };

    const closeModal = () => {
        setModalOpen(false);
        resetForm();
    };

    const handleDelete = (grade) => {
        if (confirm(`Delete grade “${grade.name}”?`)) {
            router.delete(`/settings/grades/${grade.id}`, {
                preserveScroll: true,
            });
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        if (mode === 'create') {
            form.post('/settings/grades', {
                preserveScroll: true,
                onSuccess: closeModal,
            });
        } else if (editingId) {
            form.put(`/settings/grades/${editingId}`, {
                preserveScroll: true,
                onSuccess: closeModal,
            });
        }
    };

    const submitSearch = (event) => {
        event.preventDefault();
        router.get('/settings/grades', { search }, { preserveState: true, replace: true });
    };

    return (
        <DashboardLayout title="Settings • Grades">
            <Head title="Grades" />

            <div className="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <div className="text-sm text-gray-500 mb-1">
                        <Link href="/settings" className="hover:text-primary-600">Settings</Link>
                        <span className="mx-1">/</span>
                        <span>Grades</span>
                    </div>
                    <h1 className="text-2xl font-semibold text-gray-900">Grades</h1>
                    <p className="text-sm text-gray-600">Maintain the material grades used when defining materials.</p>
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
                                placeholder="Search grades..."
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
                        New Grade
                    </button>
                </div>
            </div>

            <DataTable data={grades.data} columns={columns} title="All Grades" />

            <Modal show={modalOpen} onClose={closeModal} maxWidth="lg">
                <form onSubmit={handleSubmit} className="p-6 space-y-6">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900">
                            {mode === 'create' ? 'Create Grade' : 'Edit Grade'}
                        </h2>
                        <p className="text-sm text-gray-600">
                            Provide the details below and save to {mode === 'create' ? 'create a new' : 'update this'} grade.
                        </p>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="grade-name" value="Name" />
                            <TextInput
                                id="grade-name"
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                className="mt-1 block w-full"
                                required
                                autoFocus
                            />
                            <InputError message={form.errors.name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="grade-description" value="Description" />
                            <TextInput
                                id="grade-description"
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                                className="mt-1 block w-full"
                                placeholder="Optional"
                            />
                            <InputError message={form.errors.description} className="mt-2" />
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
