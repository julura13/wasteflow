import { useState, useRef } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DataTable from '@/Components/Dashboard/DataTable';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Plus, Edit2, Trash2, Download, FileText } from 'lucide-react';

export default function DocumentsIndex({ documents }) {
    const { auth } = usePage().props;
    const canManage = auth.user?.permissions?.includes('manage-documents') ?? false;

    const [modalOpen, setModalOpen] = useState(false);
    const [mode, setMode] = useState('create');
    const [editingId, setEditingId] = useState(null);
    const fileInputRef = useRef(null);

    const form = useForm({
        title: '',
        description: '',
        file: null,
    });

    const resetForm = () => {
        form.reset();
        form.clearErrors();
        form.setData({ title: '', description: '', file: null });
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

    const openEditModal = (document) => {
        form.clearErrors();
        form.setData({
            title: document.title ?? '',
            description: document.description ?? '',
            file: null,
        });
        setEditingId(document.id);
        setMode('edit');
        setModalOpen(true);
    };

    const closeModal = () => {
        setModalOpen(false);
        resetForm();
    };

    const handleDelete = (document) => {
        if (confirm(`Delete document "${document.title}"?`)) {
            router.delete(`/documents/${document.id}`, { preserveScroll: true });
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (mode === 'create') {
            form.transform((data) => data);
            form.post('/documents', {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: closeModal,
            });
        } else if (editingId) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/documents/${editingId}`, {
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
                    <FileText className="h-4 w-4 text-gray-400 shrink-0" />
                    <span className="font-medium text-gray-900 dark:text-gray-100">{row.original.title}</span>
                </div>
            ),
        },
        {
            accessorKey: 'description',
            header: 'Description',
            cell: ({ getValue }) => (
                <span className="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{getValue() ?? '—'}</span>
            ),
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
                        href={`/documents/${row.original.id}/download`}
                        className="text-primary-600 hover:text-primary-800 dark:text-primary-400"
                        title="Download"
                    >
                        <Download className="h-4 w-4" />
                    </a>
                    {canManage && (
                        <>
                            <button onClick={() => openEditModal(row.original)} className="text-amber-600 hover:text-amber-800" title="Edit">
                                <Edit2 className="h-4 w-4" />
                            </button>
                            <button onClick={() => handleDelete(row.original)} className="text-red-600 hover:text-red-800" title="Delete">
                                <Trash2 className="h-4 w-4" />
                            </button>
                        </>
                    )}
                </div>
            ),
        },
    ];

    return (
        <DashboardLayout title="Documents">
            <Head title="Documents" />

            <div className="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">Documents</h1>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        Shared documents uploaded by WasteFlow staff.
                    </p>
                </div>
                {canManage && (
                    <button
                        onClick={openCreateModal}
                        className="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700"
                    >
                        <Plus className="h-4 w-4 mr-1" />
                        Upload Document
                    </button>
                )}
            </div>

            <DataTable data={documents.data || []} columns={columns} title="All Documents" pagination={false} />

            {documents.links && (
                <div className="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="text-sm text-gray-700 dark:text-gray-300">
                        Showing {documents.from ?? 0} to {documents.to ?? 0} of {documents.total ?? 0} results
                    </div>
                    <div className="flex flex-wrap gap-1">
                        {documents.links.map((link, index) => (
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

            {canManage && (
                <Modal show={modalOpen} onClose={closeModal} maxWidth="lg">
                    <form onSubmit={handleSubmit} className="p-6 space-y-6">
                        <div>
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {mode === 'create' ? 'Upload Document' : 'Edit Document'}
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                {mode === 'create'
                                    ? 'Provide a title, optional description, and a file to share with all users.'
                                    : 'Update the details below, and optionally replace the file.'}
                            </p>
                        </div>

                        <div className="space-y-4">
                            <div>
                                <InputLabel htmlFor="document-title" value="Title" />
                                <TextInput
                                    id="document-title"
                                    value={form.data.title}
                                    onChange={(event) => form.setData('title', event.target.value)}
                                    className="mt-1 block w-full"
                                    required
                                    autoFocus
                                />
                                <InputError message={form.errors.title} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="document-description" value="Description" />
                                <textarea
                                    id="document-description"
                                    value={form.data.description}
                                    onChange={(event) => form.setData('description', event.target.value)}
                                    rows={3}
                                    placeholder="Optional"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 sm:text-sm"
                                />
                                <InputError message={form.errors.description} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="document-file" value={mode === 'create' ? 'File' : 'Replace file (optional)'} />
                                <input
                                    ref={fileInputRef}
                                    id="document-file"
                                    type="file"
                                    onChange={(event) => form.setData('file', event.target.files[0] ?? null)}
                                    className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                                    required={mode === 'create'}
                                />
                                <InputError message={form.errors.file} className="mt-2" />
                                <p className="mt-1 text-xs text-gray-500">Up to 10MB.</p>
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
            )}
        </DashboardLayout>
    );
}
