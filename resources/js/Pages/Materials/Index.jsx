import { useEffect, useMemo, useState, useRef, useCallback } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Plus, Edit, Trash2, Eye, Search, Filter, CheckCircle, X, Loader2 } from 'lucide-react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DataTable from '@/Components/Dashboard/DataTable';

// Separate component for editable rebate rate cell to prevent re-renders
function EditableRebateRateCell({ material, onSave }) {
    const [isEditing, setIsEditing] = useState(false);
    const [value, setValue] = useState(material.rebate_rate !== null ? Number(material.rebate_rate).toFixed(2) : '');
    const [isSaving, setIsSaving] = useState(false);
    const inputRef = useRef(null);

    // Update value when material data changes (after save)
    useEffect(() => {
        if (!isEditing) {
            setValue(material.rebate_rate !== null ? Number(material.rebate_rate).toFixed(2) : '');
        }
    }, [material.rebate_rate, isEditing]);

    useEffect(() => {
        if (isEditing && inputRef.current) {
            inputRef.current.focus();
            inputRef.current.select();
        }
    }, [isEditing]);

    const handleClick = () => {
        setIsEditing(true);
        setValue(material.rebate_rate !== null ? Number(material.rebate_rate).toFixed(2) : '');
    };

    const handleChange = (e) => {
        setValue(e.target.value);
    };

    const handleSave = () => {
        if (value === '') {
            setIsEditing(false);
            return;
        }

        const numValue = parseFloat(value);
        if (isNaN(numValue) || numValue < 0) {
            alert('Please enter a valid positive number');
            return;
        }

        setIsSaving(true);
        onSave(material.id, numValue, () => {
            setIsEditing(false);
            setIsSaving(false);
        }, () => {
            setIsSaving(false);
        });
    };

    const handleCancel = () => {
        setIsEditing(false);
        setValue(material.rebate_rate !== null ? Number(material.rebate_rate).toFixed(2) : '');
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleSave();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            handleCancel();
        }
    };

    if (!material.rebate_offered || material.rebate_rate === null) {
        return <span className="text-sm text-gray-400 dark:text-gray-500">—</span>;
    }

    if (isEditing) {
        return (
            <div className="flex items-center space-x-2">
                <span className="text-sm text-gray-900 dark:text-gray-100">R</span>
                <input
                    ref={inputRef}
                    type="number"
                    step="0.01"
                    min="0"
                    max="999999.99"
                    value={value}
                    onChange={handleChange}
                    onBlur={handleSave}
                    onKeyDown={handleKeyDown}
                    className="w-24 px-2 py-1 text-sm border border-primary-500 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600"
                    disabled={isSaving}
                />
                {isSaving && (
                    <Loader2 className="h-4 w-4 animate-spin text-primary-600" />
                )}
            </div>
        );
    }

    return (
        <button
            onClick={handleClick}
            className="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-primary-600 dark:hover:text-primary-400 transition-colors cursor-pointer text-left"
            title="Click to edit rebate rate"
        >
            R {Number(material.rebate_rate).toFixed(2)}
        </button>
    );
}

// Editable client rebate share (rebate %) cell
function EditableRebateShareCell({ material, onSave }) {
    const [isEditing, setIsEditing] = useState(false);
    const [value, setValue] = useState(material.client_rebate_share !== null ? Number(material.client_rebate_share).toFixed(2) : '');
    const [isSaving, setIsSaving] = useState(false);
    const inputRef = useRef(null);

    useEffect(() => {
        if (!isEditing) {
            setValue(material.client_rebate_share !== null ? Number(material.client_rebate_share).toFixed(2) : '');
        }
    }, [material.client_rebate_share, isEditing]);

    useEffect(() => {
        if (isEditing && inputRef.current) {
            inputRef.current.focus();
            inputRef.current.select();
        }
    }, [isEditing]);

    const handleClick = () => {
        setIsEditing(true);
        setValue(material.client_rebate_share !== null ? Number(material.client_rebate_share).toFixed(2) : '');
    };

    const handleChange = (e) => setValue(e.target.value);

    const handleSave = () => {
        if (value === '') {
            setIsEditing(false);
            return;
        }
        const numValue = parseFloat(value);
        if (isNaN(numValue) || numValue < 0 || numValue > 100) {
            alert('Please enter a number between 0 and 100');
            return;
        }
        setIsSaving(true);
        onSave(material.id, numValue, () => {
            setIsEditing(false);
            setIsSaving(false);
        }, () => setIsSaving(false));
    };

    const handleCancel = () => {
        setIsEditing(false);
        setValue(material.client_rebate_share !== null ? Number(material.client_rebate_share).toFixed(2) : '');
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleSave();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            handleCancel();
        }
    };

    if (!material.rebate_offered || material.client_rebate_share === null) {
        return <span className="text-sm text-gray-400 dark:text-gray-500">—</span>;
    }

    if (isEditing) {
        return (
            <div className="flex items-center space-x-2">
                <input
                    ref={inputRef}
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    value={value}
                    onChange={handleChange}
                    onBlur={handleSave}
                    onKeyDown={handleKeyDown}
                    className="w-20 px-2 py-1 text-sm border border-primary-500 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600"
                    disabled={isSaving}
                />
                <span className="text-sm text-gray-600 dark:text-gray-400">%</span>
                {isSaving && (
                    <Loader2 className="h-4 w-4 animate-spin text-primary-600" />
                )}
            </div>
        );
    }

    return (
        <button
            onClick={handleClick}
            className="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-primary-600 dark:hover:text-primary-400 transition-colors cursor-pointer text-left"
            title="Click to edit rebate percentage"
        >
            {Number(material.client_rebate_share).toFixed(2)}%
        </button>
    );
}

export default function MaterialsIndex({ materials, filters, wasteStreams, facilities }) {
    const { flash } = usePage().props;
    const [showSuccess, setShowSuccess] = useState(false);

    const [search, setSearch] = useState(filters.search || '');
    const [wasteStreamFilter, setWasteStreamFilter] = useState(filters.waste_stream_id || '');
    const [facilityFilter, setFacilityFilter] = useState(filters.facility_id || '');
    const [rebateFilter, setRebateFilter] = useState(
        filters.rebate !== undefined && filters.rebate !== null ? String(filters.rebate) : ''
    );
    const [statusFilter, setStatusFilter] = useState(
        filters.status !== undefined && filters.status !== null ? String(filters.status) : ''
    );

    useEffect(() => {
        if (flash?.success) {
            setShowSuccess(true);
            const timer = setTimeout(() => setShowSuccess(false), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash]);

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this material definition?')) {
            router.delete(`/materials/${id}`);
        }
    };

    const handleRebateRateSave = useCallback((materialId, rebateRate, onSuccess, onError) => {
        router.patch(
            `/materials/${materialId}/rebate-rate`,
            { rebate_rate: rebateRate },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['materials'],
                onSuccess: () => {
                    onSuccess();
                },
                onError: (errors) => {
                    const errorMessage = errors.rebate_rate?.[0] || errors.message || 'Failed to update rebate rate. Please try again.';
                    alert(errorMessage);
                    onError();
                },
            }
        );
    }, []);

    const handleRebateShareSave = useCallback((materialId, clientRebateShare, onSuccess, onError) => {
        router.patch(
            `/materials/${materialId}/rebate-share`,
            { client_rebate_share: clientRebateShare },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['materials'],
                onSuccess: () => onSuccess(),
                onError: (errors) => {
                    const errorMessage = errors.client_rebate_share?.[0] || errors.message || 'Failed to update rebate percentage. Please try again.';
                    alert(errorMessage);
                    onError();
                },
            }
        );
    }, []);

    const columns = useMemo(
        () => [
            {
                header: 'Waste Stream',
                accessorFn: (row) => row.waste_stream?.name || '—',
            },
            {
                header: 'Grade',
                accessorFn: (row) => row.grade?.name || '—',
                cell: ({ getValue }) => (
                    <span className="font-mono text-sm font-semibold text-gray-900 dark:text-gray-100">{getValue()}</span>
                ),
            },
            {
                header: 'Classification',
                accessorFn: (row) => row.classification?.name || '—',
            },
            {
                header: 'Facility',
                accessorFn: (row) => row.facility?.name || '—',
            },
            {
                id: 'rebate',
                header: 'Rebate',
                cell: ({ row }) => (
                    <span
                        className={`px-2 py-1 text-xs font-medium rounded-full ${
                            row.original.rebate_offered
                                ? 'bg-green-100 text-green-800'
                                : 'bg-gray-100 text-gray-600'
                        }`}
                    >
                        {row.original.rebate_offered ? 'Yes' : 'No'}
                    </span>
                ),
            },
            {
                id: 'rebate_rate',
                header: 'Rebate Rate',
                cell: ({ row }) => (
                    <EditableRebateRateCell material={row.original} onSave={handleRebateRateSave} />
                ),
            },
            {
                id: 'client_share',
                header: 'Rebate %',
                cell: ({ row }) => (
                    <EditableRebateShareCell material={row.original} onSave={handleRebateShareSave} />
                ),
            },
            {
                id: 'backing_document',
                header: 'Backing Doc',
                cell: ({ row }) => (
                    <span
                        className={`px-2 py-1 text-xs font-medium rounded-full ${
                            row.original.backing_document
                                ? 'bg-primary-100 text-primary-800'
                                : 'bg-gray-100 text-gray-600'
                        }`}
                    >
                        {row.original.backing_document ? 'Required' : 'Optional'}
                    </span>
                ),
            },
            {
                accessorKey: 'is_active',
                header: 'Status',
                cell: ({ getValue }) => {
                    const isActive = getValue();
                    return (
                        <span
                            className={`px-2 py-1 text-xs font-medium rounded-full ${
                                isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                            }`}
                        >
                            {isActive ? 'Active' : 'Inactive'}
                        </span>
                    );
                },
            },
            {
                id: 'actions',
                header: 'Actions',
                cell: ({ row }) => {
                    const material = row.original;
                    return (
                        <div className="flex space-x-2">
                            <Link
                                href={`/materials/${material.id}`}
                                className="text-primary-600 hover:text-primary-800"
                                title="View"
                            >
                                <Eye className="h-4 w-4" />
                            </Link>
                            <Link
                                href={`/materials/${material.id}/edit`}
                                className="text-amber-600 hover:text-amber-800"
                                title="Edit"
                            >
                                <Edit className="h-4 w-4" />
                            </Link>
                            <button
                                onClick={() => handleDelete(material.id)}
                                className="text-red-600 hover:text-red-800"
                                title="Delete"
                            >
                                <Trash2 className="h-4 w-4" />
                            </button>
                        </div>
                    );
                },
            },
        ],
        [handleDelete, handleRebateRateSave]
    );

    const resolveFilterValue = (value, fallback) => {
        const candidate = value !== undefined ? value : fallback;
        return candidate === '' || candidate === null || candidate === undefined ? undefined : candidate;
    };

    const applyFilters = (nextFilters = {}) => {
        router.get(
            '/materials',
            {
                search: resolveFilterValue(nextFilters.search, search),
                waste_stream_id: resolveFilterValue(nextFilters.waste_stream_id, wasteStreamFilter),
                facility_id: resolveFilterValue(nextFilters.facility_id, facilityFilter),
                rebate: resolveFilterValue(nextFilters.rebate, rebateFilter),
                status: resolveFilterValue(nextFilters.status, statusFilter),
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        applyFilters();
    };

    return (
        <DashboardLayout title="Materials">
            <Head title="Materials" />

            {showSuccess && flash?.success && (
                <div className="mb-6 rounded-lg bg-primary-50 border border-primary-200 p-4 animate-fade-in">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center">
                            <CheckCircle className="h-5 w-5 text-primary-600 mr-3" />
                            <p className="text-sm font-medium text-primary-800">{flash.success}</p>
                        </div>
                        <button
                            onClick={() => setShowSuccess(false)}
                            className="text-primary-600 hover:text-primary-800"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>
                </div>
            )}

            <div className="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Material Definitions</h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Combine Waste Streams, Grades, and Facilities to define operational
                        material profiles.
                    </p>
                </div>
                <Link
                    href="/materials/create"
                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <Plus className="h-4 w-4 mr-2" />
                    Add Material
                </Link>
            </div>

            <div className="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <form onSubmit={handleSubmit} className="grid gap-4 md:grid-cols-5">
                    <div className="md:col-span-2">
                        <label htmlFor="search" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Search
                        </label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 dark:text-gray-500" />
                            <input
                                id="search"
                                type="text"
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                className="pl-10 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-400"
                                placeholder="Grade, waste stream, facility..."
                            />
                        </div>
                    </div>

                    <div>
                        <label htmlFor="waste_stream_id" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Waste Stream
                        </label>
                        <select
                            id="waste_stream_id"
                            value={wasteStreamFilter}
                            onChange={(event) => {
                                const value = event.target.value;
                                setWasteStreamFilter(value);
                                applyFilters({ waste_stream_id: value });
                            }}
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="">All</option>
                            {wasteStreams.map((stream) => (
                                <option key={stream.id} value={stream.id}>
                                    {stream.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label htmlFor="facility_id" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Facility
                        </label>
                        <select
                            id="facility_id"
                            value={facilityFilter}
                            onChange={(event) => {
                                const value = event.target.value;
                                setFacilityFilter(value);
                                applyFilters({ facility_id: value });
                            }}
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="">All</option>
                            {facilities.map((facility) => (
                                <option key={facility.id} value={facility.id}>
                                    {facility.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label htmlFor="rebate" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Rebate
                        </label>
                        <select
                            id="rebate"
                            value={rebateFilter}
                            onChange={(event) => {
                                const value = event.target.value;
                                setRebateFilter(value);
                                applyFilters({ rebate: value });
                            }}
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="">All</option>
                            <option value="1">Rebate Offered</option>
                            <option value="0">No Rebate</option>
                        </select>
                    </div>

                    <div>
                        <label htmlFor="status" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Status
                        </label>
                        <select
                            id="status"
                            value={statusFilter}
                            onChange={(event) => {
                                const value = event.target.value;
                                setStatusFilter(value);
                                applyFilters({ status: value });
                            }}
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="">All</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <div className="md:col-span-5 flex justify-end">
                        <button
                            type="submit"
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            <Filter className="h-4 w-4 mr-2" />
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <DataTable data={materials.data} columns={columns} title="Defined Materials" pagination={false} />

            {materials.links && (
                <div className="mt-6 flex items-center justify-between">
                    <div className="text-sm text-gray-700 dark:text-gray-300">
                        Showing {materials.from || 0} to {materials.to || 0} of {materials.total || 0} results
                    </div>
                    <div className="flex space-x-1">
                        {materials.links.map((link, index) => (
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
        </DashboardLayout>
    );
}

