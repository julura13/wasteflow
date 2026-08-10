import { useState, useRef } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DataTable from '@/Components/Dashboard/DataTable';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Plus, Edit2, Trash2, Eye, Megaphone } from 'lucide-react';

export default function ClientHubIndex({ adverts }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [mode, setMode] = useState('create');
    const [editingId, setEditingId] = useState(null);
    const fileInputRef = useRef(null);

    const form = useForm({
        title: '',
        details: '',
        contact_email: 'crm@wasteflow.example.com',
        is_active: true,
        file: null,
    });

    const resetForm = () => {
        form.reset();
        form.clearErrors();
        form.setData({ title: '', details: '', contact_email: 'crm@wasteflow.example.com', is_active: true, file: null });
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
        setEditingId(null);
    };

    const openCreateModal = () => {
        resetForm();
        setMode('create');
        setModalOpen(true);
    };

    const openEditModal = (advert) => {
        form.clearErrors();
        form.setData({
            title: advert.title ?? '',
            details: advert.details ?? '',
            contact_email: advert.contact_email ?? 'crm@wasteflow.example.com',
            is_active: advert.is_active,
            file: null,
        });
        setEditingId(advert.id);
        setMode('edit');
        setModalOpen(true);
    };

    const closeModal = () => {
        setModalOpen(false);
        resetForm();
    };

    const handleDelete = (advert) => {
        if (confirm(`Delete advert "${advert.title}"? This cannot be undone.`)) {
            router.delete(`/client-hub/${advert.id}`, { preserveScroll: true });
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (mode === 'create') {
            form.transform((data) => data);
            form.post('/client-hub', {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: closeModal,
            });
        } else if (editingId) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/client-hub/${editingId}`, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: closeModal,
            });
        }
    };

    const columns = [
        {
            accessorKey: 'title',
            header: 'Title',
            cell: ({ row }) => (
                <div className="flex items-center gap-2">
                    <Megaphone className="h-4 w-4 text-gray-400 shrink-0" />
                    <span className="font-medium text-gray-900 dark:text-gray-100">{row.original.title}</span>
                </div>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: ({ row }) => (
                <span
                    className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${
                        row.original.is_active
                            ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                            : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                    }`}
                >
                    {row.original.is_active ? 'Active' : 'Inactive'}
                </span>
            ),
        },
        {
            accessorKey: 'contact_email',
            header: 'Contact email',
            cell: ({ getValue }) => <span className="text-sm text-gray-600 dark:text-gray-400">{getValue()}</span>,
        },
        {
            id: 'uploaded_by',
            header: 'Uploaded by',
            cell: ({ row }) => (
                <span className="text-sm text-gray-600 dark:text-gray-400">{row.original.uploaded_by ?? '—'}</span>
            ),
        },
        {
            id: 'size',
            header: 'Size',
            cell: ({ row }) => (
                <span className="text-sm text-gray-600 dark:text-gray-400">{row.original.human_readable_size}</span>
            ),
        },
        {
            accessorKey: 'created_at',
            header: 'Uploaded',
            cell: ({ getValue }) => <span className="text-sm text-gray-600 dark:text-gray-400">{getValue()}</span>,
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex items-center space-x-3">
                    <a
                        href={row.original.view_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-primary-600 hover:text-primary-800 dark:text-primary-400"
                        title="View"
                    >
                        <Eye className="h-4 w-4" />
                    </a>
                    <button onClick={() => openEditModal(row.original)} className="text-amber-600 hover:text-amber-800" title="Edit">
                        <Edit2 className="h-4 w-4" />
                    </button>
                    <button onClick={() => handleDelete(row.original)} className="text-red-600 hover:text-red-800" title="Delete">
                        <Trash2 className="h-4 w-4" />
                    </button>
                </div>
            ),
        },
    ];

    return (
        <DashboardLayout title="Client Hub">
            <Head title="Client Hub" />

            <div className="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <Link href="/settings" className="text-sm text-primary-600 hover:underline dark:text-primary-400">
                        &larr; Settings
                    </Link>
                    <h1 className="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">Client Hub</h1>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        Adverts uploaded here pop up automatically for clients on login while marked Active. Closing
                        the popup doesn&apos;t clear their notification badge &mdash; only opening it from the bell does.
                    </p>
                </div>
                <button
                    onClick={openCreateModal}
                    className="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700"
                >
                    <Plus className="h-4 w-4 mr-1" />
                    Upload Advert
                </button>
            </div>

            <DataTable data={adverts || []} columns={columns} title="All Client Hub Adverts" pagination={false} />

            <Modal show={modalOpen} onClose={closeModal} maxWidth="lg">
                <form onSubmit={handleSubmit} className="p-6 space-y-6">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {mode === 'create' ? 'Upload Advert' : 'Edit Advert'}
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            PNG, JPG, or PDF. Shown to client-role users only.
                        </p>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="advert-title" value="Title" />
                            <TextInput
                                id="advert-title"
                                value={form.data.title}
                                onChange={(event) => form.setData('title', event.target.value)}
                                className="mt-1 block w-full"
                                required
                                autoFocus
                            />
                            <InputError message={form.errors.title} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="advert-details" value="Details" />
                            <textarea
                                id="advert-details"
                                value={form.data.details}
                                onChange={(event) => form.setData('details', event.target.value)}
                                rows={3}
                                placeholder="Optional"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 sm:text-sm"
                            />
                            <InputError message={form.errors.details} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="advert-contact-email" value="Contact email" />
                            <TextInput
                                id="advert-contact-email"
                                type="email"
                                value={form.data.contact_email}
                                onChange={(event) => form.setData('contact_email', event.target.value)}
                                className="mt-1 block w-full"
                            />
                            <InputError message={form.errors.contact_email} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="advert-file" value={mode === 'create' ? 'File' : 'Replace file (optional)'} />
                            <input
                                ref={fileInputRef}
                                id="advert-file"
                                type="file"
                                accept=".png,.jpg,.jpeg,.pdf,image/png,image/jpeg,application/pdf"
                                onChange={(event) => form.setData('file', event.target.files[0] ?? null)}
                                className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                                required={mode === 'create'}
                            />
                            <InputError message={form.errors.file} className="mt-2" />
                            <p className="mt-1 text-xs text-gray-500">PNG, JPG, or PDF. Up to 10MB.</p>
                        </div>

                        <div className="flex items-center gap-2">
                            <input
                                id="advert-is-active"
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(event) => form.setData('is_active', event.target.checked)}
                                className="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                            />
                            <InputLabel htmlFor="advert-is-active" value="Active (visible to clients)" className="mb-0" />
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
