import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import Modal from '@/Components/Modal';
import SearchableDropdown from '@/Components/SearchableDropdown';
import { ArrowLeft, CheckCircle, Upload, Trash2, Download, File, AlertCircle, Plus, Save, AlertTriangle } from 'lucide-react';
import { useState, useMemo, useEffect, useRef } from 'react';

export default function Finalize({ order, materials = [], canManageOrder = true }) {
    const { flash, errors } = usePage().props;
    const [uploading, setUploading] = useState(false);
    const [finalizing, setFinalizing] = useState(false);
    const [savingWeights, setSavingWeights] = useState(false);
    const [showFinalizeModal, setShowFinalizeModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [documentToDelete, setDocumentToDelete] = useState(null);
    const [validationError, setValidationError] = useState(null);
    const [successMessage, setSuccessMessage] = useState(null);
    const [slipNumberExistsError, setSlipNumberExistsError] = useState(false);
    const [isDragging, setIsDragging] = useState(false);
    const [fileValidationError, setFileValidationError] = useState(null);
    const slipCheckTimeoutRef = useRef(null);

    // File upload validation (must match backend: max 10MB, allowed types)
    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    const ACCEPTED_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    const ACCEPTED_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
    ];

    const getFileExtension = (name) => {
        const parts = (name || '').split('.');
        return parts.length > 1 ? parts.pop().toLowerCase() : '';
    };

    const validateFiles = (fileList) => {
        const valid = [];
        const errors = [];
        const names = new Set();
        for (let i = 0; i < fileList.length; i++) {
            const file = fileList[i];
            if (names.has(file.name)) {
                errors.push(`Duplicate file: ${file.name}`);
                continue;
            }
            names.add(file.name);
            if (file.size > MAX_FILE_SIZE) {
                errors.push(`${file.name} exceeds 10MB limit`);
                continue;
            }
            const ext = getFileExtension(file.name);
            const mimeOk = ACCEPTED_MIMES.includes(file.type);
            const extOk = ACCEPTED_EXTENSIONS.includes(ext);
            if (!mimeOk && !extOk) {
                errors.push(`${file.name}: allowed types are PDF, DOC, DOCX, JPG, JPEG, PNG`);
                continue;
            }
            valid.push(file);
        }
        return { valid, errors };
    };

    const setFilesFromList = (fileList) => {
        setFileValidationError(null);
        if (!fileList || fileList.length === 0) {
            setUploadData('files', []);
            return { valid: [], errors: [] };
        }
        const { valid, errors } = validateFiles(Array.from(fileList));
        setUploadData('files', valid);
        if (errors.length > 0) {
            const msg = errors.length > 1 ? `${errors[0]} (and ${errors.length - 1} other issue${errors.length > 2 ? 's' : ''})` : errors[0];
            setFileValidationError(msg);
        }
        return { valid, errors };
    };

    const uploadFiles = async (filesToUpload) => {
        if (!filesToUpload || filesToUpload.length === 0) return;
        setUploading(true);
        for (let i = 0; i < filesToUpload.length; i++) {
            const file = filesToUpload[i];
            const formData = new FormData();
            formData.append('file', file);
            formData.append('mediable_type', uploadData.mediable_type);
            formData.append('mediable_id', uploadData.mediable_id);
            formData.append('collection', uploadData.collection);
            if (uploadData.description) formData.append('description', uploadData.description);
            try {
                await window.axios.post(route('media.upload'), formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
            } catch (error) {
                console.error('Error uploading file:', error);
                const errorMessage = error.response?.data?.message || error.message || 'Upload failed';
                alert(`Error uploading file ${file.name}: ${errorMessage}`);
                setUploading(false);
                return;
            }
        }
        router.reload({ only: ['order'], preserveScroll: true });
        resetUpload();
        setFileValidationError(null);
        setUploading(false);
    };

    // Slip number prefix from order's service provider
    const slipPrefix = (order.service_provider?.slip_number_prefix || '').trim();
    const hasSlipPrefix = slipPrefix.length > 0;

    // Parse initial slip number: if we have a prefix and order.slip_number starts with "PREFIX-", show only the part after
    const getInitialSlipPart = () => {
        const raw = (order.slip_number || '').trim();
        if (!raw) return '';
        if (hasSlipPrefix && raw.toLowerCase().startsWith(slipPrefix.toLowerCase() + '-')) {
            return raw.slice(slipPrefix.length + 1).trim();
        }
        return raw;
    };

    const [slipNumberPart, setSlipNumberPartState] = useState(getInitialSlipPart);

    const setSlipNumberPart = (value) => {
        const v = typeof value === 'string' ? value : '';
        setSlipNumberPartState(v);
        const full = hasSlipPrefix ? `${slipPrefix}-${v}`.trim() : v.trim();
        setFinalizeData('slip_number', full);
        setSlipNumberExistsError(false);
    };

    // Debounced slip number uniqueness check (500ms)
    useEffect(() => {
        const fullSlip = hasSlipPrefix ? `${slipPrefix}-${slipNumberPart}`.trim() : slipNumberPart.trim();
        if (!fullSlip) {
            setSlipNumberExistsError(false);
            return;
        }
        if (slipCheckTimeoutRef.current) clearTimeout(slipCheckTimeoutRef.current);
        slipCheckTimeoutRef.current = setTimeout(async () => {
            try {
                const { data } = await window.axios.get(route('orders.check-slip-number'), {
                    params: { slip_number: fullSlip, exclude_order_id: order.id },
                });
                if (data.exists) {
                    setSlipNumberExistsError(true);
                    alert('This slip number is already used on another order. Please enter a unique slip number.');
                } else {
                    setSlipNumberExistsError(false);
                }
            } catch (err) {
                console.error('Slip number check failed', err);
            }
            slipCheckTimeoutRef.current = null;
        }, 500);
        return () => {
            if (slipCheckTimeoutRef.current) clearTimeout(slipCheckTimeoutRef.current);
        };
    }, [slipNumberPart, hasSlipPrefix, slipPrefix, order.id]);

    // Initialize weight lines from existing waste streams or empty array
    const initialWeightLines = order.waste_streams && order.waste_streams.length > 0
        ? order.waste_streams.map((ws, index) => ({
            id: ws.id || `existing-${index}`,
            material_id: ws.material_id || '',
            weight: ws.nett_weight || ws.gross_weight || '',
            isExisting: true,
        }))
        : [{ id: 1, material_id: '', weight: '', isExisting: false }];
    
    const [weightLines, setWeightLines] = useState(initialWeightLines);

    const { data: uploadData, setData: setUploadData, post: uploadPost, reset: resetUpload, errors: uploadErrors } = useForm({
        files: [],
        mediable_type: 'App\\Models\\Order',
        mediable_id: order.id,
        collection: 'supporting_documents',
        description: '',
    });

    const { data: finalizeData, setData: setFinalizeData, post: finalizePost, errors: finalizeErrors } = useForm({
        actual_collection_date: order.actual_collection_date 
            ? new Date(order.actual_collection_date).toISOString().split('T')[0]
            : (order.requested_collection_date ? new Date(order.requested_collection_date).toISOString().split('T')[0] : ''),
        actual_quantity: order.actual_quantity || order.estimated_quantity || '',
        slip_number: order.slip_number || '',
    });

    const handleFileUpload = (e) => {
        e.preventDefault();
        if (uploadData.files?.length) uploadFiles(uploadData.files);
    };

    const handleDeleteDocument = (mediaId) => {
        setDocumentToDelete(mediaId);
        setShowDeleteModal(true);
    };

    const handleDeleteConfirm = () => {
        if (documentToDelete) {
            router.delete(route('media.destroy', documentToDelete), {
                preserveScroll: true,
                only: ['order'],
                onSuccess: () => {
                    setShowDeleteModal(false);
                    setDocumentToDelete(null);
                },
            });
        }
    };

    // Get material display name
    const getMaterialDisplayName = (material) => {
        if (!material) return '';
        const parts = [];
        if (material.grade?.name) parts.push(material.grade.name);
        if (material.waste_stream?.name) parts.push(material.waste_stream.name);
        if (material.rebate_offered && material.rebate_rate) {
            parts.push(`(Rebate: R${Number(material.rebate_rate).toFixed(2)})`);
        }
        return parts.join(' - ') || `Material #${material.id}`;
    };

    // Filter materials based on order type
    const availableMaterials = useMemo(() => {
        return materials.filter(material => {
            if (order.order_type === 'waste') {
                // For waste orders: only materials where waste stream name contains "Waste"
                return material.waste_stream?.name?.toLowerCase().includes('waste');
            } else {
                // For recycling orders: all materials
                return true;
            }
        });
    }, [materials, order.order_type]);

    const totalRebate = useMemo(() => {
        if (!order.waste_streams || order.waste_streams.length === 0) {
            return 0;
        }
        const companyRebatePercentage = order.site?.branch?.company?.rebate_percentage;
        
        return order.waste_streams.reduce((total, ws) => {
            if (ws.material?.rebate_offered && ws.material?.rebate_rate && ws.nett_weight) {
                const rebateAmount = ws.nett_weight * ws.material.rebate_rate;
                let clientShare;
                if (companyRebatePercentage !== null && companyRebatePercentage !== undefined) {
                    clientShare = companyRebatePercentage;
                } else {
                    clientShare = ws.material.client_rebate_share || 100;
                }
                return total + (rebateAmount * clientShare) / 100;
            }
            return total;
        }, 0);
    }, [order.waste_streams, order.site?.branch?.company?.rebate_percentage]);

    // Total weight from current form lines (for double-check before save)
    const totalWeightFromLines = useMemo(() => {
        return weightLines.reduce((sum, line) => {
            const w = parseFloat(line.weight);
            return sum + (Number.isFinite(w) && w > 0 ? w : 0);
        }, 0);
    }, [weightLines]);

    // Total weight from saved waste streams
    const totalSavedWeight = useMemo(() => {
        if (!order.waste_streams || order.waste_streams.length === 0) return 0;
        return order.waste_streams.reduce((sum, ws) => {
            const w = ws.nett_weight ?? ws.gross_weight ?? 0;
            return sum + (Number(w) || 0);
        }, 0);
    }, [order.waste_streams]);

    const addWeightLine = () => {
        const newId = Math.max(...weightLines.map(line => line.id || 0), 0) + 1;
        setWeightLines([...weightLines, { id: newId, material_id: '', weight: '', isExisting: false }]);
    };

    const removeWeightLine = (id) => {
        if (weightLines.length > 1) {
            setWeightLines(weightLines.filter(line => line.id !== id));
        }
    };

    const updateWeightLine = (id, field, value) => {
        setWeightLines(weightLines.map(line => {
            if (line.id === id) {
                return { ...line, [field]: value };
            }
            return line;
        }));
    };

    const handleSaveWeights = async (e) => {
        e.preventDefault();
        
        // Validate weight lines
        const validWeightLines = weightLines.filter(line => {
            return line.material_id && line.weight && parseFloat(line.weight) > 0;
        });
        
        if (validWeightLines.length === 0) {
            setValidationError('Please add at least one weight line with a material and weight.');
            return;
        }

        setSavingWeights(true);
        setValidationError(null);

        try {
            const response = await window.axios.post(route('orders.save-weights', order.id), {
                weight_lines: validWeightLines.map(line => ({
                    material_id: line.material_id,
                    weight: parseFloat(line.weight),
                    id: line.isExisting ? line.id : undefined,
                })),
            });

            router.reload({ only: ['order'], preserveScroll: true });
            
            setWeightLines(validWeightLines.map(line => ({ ...line, isExisting: true })));
            setSuccessMessage('Weights saved successfully. Order status updated to Documents Required.');
            
            setTimeout(() => setSuccessMessage(null), 5000);
        } catch (error) {
            console.error('Error saving weights:', error);
            const errorMessage = error.response?.data?.message || error.message || 'Failed to save weights';
            setValidationError(errorMessage);
        } finally {
            setSavingWeights(false);
        }
    };

    const handleFinalizeSubmit = (e) => {
        e.preventDefault();
        setValidationError(null);

        const hasWeights = order.waste_streams && order.waste_streams.length > 0;
        if (!hasWeights) {
            setValidationError('Please capture weights before finalizing the order.');
            return;
        }

        const documentCount = order.supporting_documents?.length || 0;
        if (documentCount < 1) {
            setValidationError('At least one supporting document is required to finalize the order.');
            return;
        }

        if (!finalizeData.slip_number || finalizeData.slip_number.trim() === '') {
            setValidationError('Slip number is required to finalize the order.');
            return;
        }

        if (slipNumberExistsError) {
            setValidationError('Please enter a unique slip number before finalizing.');
            return;
        }

        setShowFinalizeModal(true);
    };

    const handleFinalizeConfirm = () => {
        setFinalizing(true);
        finalizePost(route('orders.finalize.store', order.id), {
            onSuccess: () => {
                router.visit(route('orders.show', order.id));
            },
            onError: () => {
                setFinalizing(false);
                setShowFinalizeModal(false);
            },
        });
    };

    return (
        <DashboardLayout title={`Finalize Order • ${order.tracking_number}`}>
            <Head title={`Finalize Order • ${order.tracking_number}`} />

            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <div className="mb-6 flex items-center justify-between">
                    <Link
                        href={route('orders.show', order.id)}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                    >
                        <ArrowLeft className="h-4 w-4 mr-1" />
                        Back to Order
                    </Link>
                </div>

                {/* Success/Error Messages */}
                {flash?.success && (
                    <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-md text-green-800">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-md text-red-800">
                        {flash.error}
                    </div>
                )}

                {errors?.status && (
                    <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-md text-red-800">
                        {errors.status}
                    </div>
                )}

                {/* Status Warning */}
                {order.status !== 'documents_required' && order.status !== 'finalized' && (
                    <div className="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md text-yellow-800">
                        <p className="font-semibold mb-2">⚠️ Order Status: {order.status.replace('_', ' ').toUpperCase()}</p>
                        <p className="text-sm">
                            This order must be in "Documents Required" status before finalization. 
                            {order.status === 'pending' && ' Please schedule the order first, then capture weights and upload documents.'}
                            {order.status === 'scheduled' && ' Please mark as "Weight Required" after collection, then capture weights and upload documents.'}
                            {order.status === 'weight_required' && ' Please capture weights below and upload documents. After saving weights, the status will change to "Documents Required".'}
                        </p>
                    </div>
                )}

                {errors?.documents && (
                    <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-md text-red-800">
                        {errors.documents}
                    </div>
                )}

                {/* Order Info Card */}
                <div className="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Order Information
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Tracking Number</span>
                                <p className="text-sm text-gray-900 dark:text-gray-100 font-medium">{order.tracking_number}</p>
                            </div>
                            <div>
                                <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Order Type</span>
                                <p className="text-sm text-gray-900 dark:text-gray-100 font-medium">
                                    {order.order_type === 'waste' ? 'Waste Order' : 'Recycling Order'}
                                </p>
                            </div>
                            <div>
                                <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</span>
                                <p className="text-sm text-gray-900 dark:text-gray-100 font-medium capitalize">{order.status}</p>
                            </div>
                            <div>
                                <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Site</span>
                                <p className="text-sm text-gray-900 dark:text-gray-100 font-medium">{order.site?.name || '—'}</p>
                            </div>
                            <div>
                                <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Requested Collection Date</span>
                                <p className="text-sm text-gray-900 dark:text-gray-100 font-medium">
                                    {order.requested_collection_date ? new Date(order.requested_collection_date).toLocaleDateString() : '—'}
                                </p>
                            </div>
                            <div>
                                <span className="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Estimated Quantity</span>
                                <p className="text-sm text-gray-900 dark:text-gray-100 font-medium">{order.estimated_quantity || '—'}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Supporting Documents - Separate from finalize form */}
                <div className="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">
                            Supporting Documents
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            Upload supporting documents for this order. At least one document is required to finalize.
                        </p>

                        {/* Upload Form - Drag and drop + file input */}
                        {canManageOrder && (
                        <form onSubmit={handleFileUpload} className="mb-6 border-b border-gray-200 dark:border-gray-700 pb-6">
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                        Upload Documents (Multiple files allowed)
                                    </label>
                                    <div
                                        onDragEnter={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            setIsDragging(true);
                                        }}
                                        onDragOver={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                        }}
                                        onDragLeave={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            setIsDragging(false);
                                        }}
                                        onDrop={(e) => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            setIsDragging(false);
                                            const items = e.dataTransfer?.files;
                                            if (items && items.length > 0) {
                                                const result = setFilesFromList(items);
                                                if (result.valid.length > 0 && result.errors.length === 0) {
                                                    uploadFiles(result.valid);
                                                }
                                            }
                                        }}
                                        className={`relative border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
                                            isDragging
                                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                                : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
                                        }`}
                                    >
                                        <input
                                            type="file"
                                            id="files"
                                            multiple
                                            onChange={(e) => {
                                                const result = setFilesFromList(e.target.files || []);
                                                e.target.value = '';
                                                if (result.valid.length > 0 && result.errors.length === 0) {
                                                    uploadFiles(result.valid);
                                                }
                                            }}
                                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                            className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                        />
                                        <Upload className="mx-auto h-10 w-10 text-gray-400 dark:text-gray-500 mb-3" />
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            Drag and drop files here, or <span className="text-primary-600 dark:text-primary-400 font-medium">click to browse</span>
                                        </p>
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-500">
                                            PDF, DOC, DOCX, JPG, PNG up to 10MB each
                                        </p>
                                    </div>
                                    {uploadData.files && uploadData.files.length > 0 && (
                                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                            {uploadData.files.length} file{uploadData.files.length !== 1 ? 's' : ''} selected
                                        </p>
                                    )}
                                    {fileValidationError && (
                                        <p className="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                                            <AlertCircle className="h-4 w-4 shrink-0" />
                                            {fileValidationError}
                                        </p>
                                    )}
                                    {uploadErrors.files && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{uploadErrors.files}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="description" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                        Description (Optional)
                                    </label>
                                    <input
                                        type="text"
                                        id="description"
                                        value={uploadData.description}
                                        onChange={(e) => setUploadData('description', e.target.value)}
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                        placeholder="Document description"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    disabled={uploading || !uploadData.files || uploadData.files.length === 0}
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <Upload className="h-4 w-4 mr-2" />
                                    {uploading ? 'Uploading...' : `Upload ${uploadData.files?.length || 0} Document${(uploadData.files?.length || 0) !== 1 ? 's' : ''}`}
                                </button>
                            </div>
                        </form>
                        )}

                        {/* Documents List */}
                        <div>
                            <div className="flex items-center justify-between mb-3">
                                <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    Uploaded Documents ({order.supporting_documents?.length || 0})
                                </h4>
                                {(!order.supporting_documents || order.supporting_documents.length === 0) && (
                                    <div className="flex items-center text-yellow-600 text-sm">
                                        <AlertCircle className="h-4 w-4 mr-1" />
                                        At least one document required
                                    </div>
                                )}
                            </div>
                            {order.supporting_documents && order.supporting_documents.length > 0 ? (
                                <div className="space-y-2 max-h-64 overflow-y-auto">
                                    {order.supporting_documents.map((doc) => (
                                        <div
                                            key={doc.id}
                                            className="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-md"
                                        >
                                            <div className="flex items-center space-x-3">
                                                <File className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {doc.original_name}
                                                    </p>
                                                    {doc.description && (
                                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                                            {doc.description}
                                                        </p>
                                                    )}
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        {doc.human_readable_size} • {doc.mime_type}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <a
                                                    href={route('media.download', doc.id)}
                                                    className="text-primary-600 hover:text-primary-800"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <Download className="h-4 w-4" />
                                                </a>
                                                {canManageOrder && (
                                                    <button
                                                        onClick={() => handleDeleteDocument(doc.id)}
                                                        className="text-red-600 hover:text-red-800"
                                                        type="button"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    No documents uploaded yet. Please upload at least one document to finalize the order.
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                {validationError && (
                    <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-md text-red-800 flex items-center">
                        <AlertCircle className="h-5 w-5 mr-2" />
                        {validationError}
                    </div>
                )}

                {successMessage && (
                    <div className="mb-6 p-4 bg-green-50 border border-green-200 rounded-md text-green-800 flex items-center">
                        <CheckCircle className="h-5 w-5 mr-2" />
                        {successMessage}
                    </div>
                )}

                <div className="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Capture Weights
                            </h3>
                            <button
                                type="button"
                                onClick={addWeightLine}
                                className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                            >
                                <Plus className="h-4 w-4 mr-2" />
                                Add Line
                            </button>
                        </div>
                        
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            {order.order_type === 'waste' 
                                ? 'Add weight line items with materials that have "Waste" in the name.'
                                : 'Add weight line items with materials. Most recycling materials have rebate prices.'}
                        </p>

                        {canManageOrder && (
                        <form onSubmit={handleSaveWeights} className="mb-4">
                                <div className="shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Material
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Weight (kg)
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Rebate
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            {weightLines.map((line) => {
                                                const selectedMaterial = availableMaterials.find(m => m.id == line.material_id);
                                                return (
                                                    <tr key={line.id}>
                                                        <td className="px-6 py-4 whitespace-nowrap overflow-visible">
                                                            <SearchableDropdown
                                                                options={availableMaterials}
                                                                value={line.material_id}
                                                                onChange={(value) => updateWeightLine(line.id, 'material_id', value)}
                                                                placeholder="Select material"
                                                                getOptionLabel={getMaterialDisplayName}
                                                                required
                                                                className="w-full"
                                                            />
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <input
                                                                type="number"
                                                                step="0.001"
                                                                min="0"
                                                                value={line.weight}
                                                                onChange={(e) => updateWeightLine(line.id, 'weight', e.target.value)}
                                                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                                                placeholder="0.000"
                                                                required
                                                            />
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                            {selectedMaterial?.rebate_offered && selectedMaterial?.rebate_rate ? (
                                                                <div>
                                                                    <div className="text-green-600 font-medium">
                                                                        R{Number(selectedMaterial.rebate_rate).toFixed(2)}/kg
                                                                    </div>
                                                                    {line.weight && parseFloat(line.weight) > 0 && (() => {
                                                                        const companyRebatePercentage = order.site?.branch?.company?.rebate_percentage;
                                                                        const clientShare = companyRebatePercentage !== null && companyRebatePercentage !== undefined 
                                                                            ? companyRebatePercentage 
                                                                            : (selectedMaterial.client_rebate_share || 100);
                                                                        return (
                                                                            <div className="text-xs text-gray-500 mt-1">
                                                                                Est: R{Number(parseFloat(line.weight) * selectedMaterial.rebate_rate * clientShare / 100).toFixed(2)}
                                                                                {companyRebatePercentage !== null && companyRebatePercentage !== undefined && (
                                                                                    <span className="text-green-600 ml-1">(Company: {companyRebatePercentage}%)</span>
                                                                                )}
                                                                            </div>
                                                                        );
                                                                    })()}
                                                                </div>
                                                            ) : (
                                                                <span className="text-gray-400">—</span>
                                                            )}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                            {weightLines.length > 1 && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => removeWeightLine(line.id)}
                                                                    className="text-red-600 hover:text-red-900"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </button>
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                    </div>
                                </div>

                                <div className="mt-4 flex flex-wrap items-center justify-between gap-4">
                                    <div className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Total weight: <span className="text-gray-900 dark:text-gray-100">{totalWeightFromLines > 0 ? Number(totalWeightFromLines).toFixed(3) : '0'} kg</span>
                                    </div>
                                    <button
                                        type="submit"
                                        disabled={savingWeights}
                                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <Save className="h-4 w-4 mr-2" />
                                        {savingWeights ? 'Saving...' : 'Save Weights'}
                                    </button>
                                </div>
                            </form>
                        )}

                        {/* Show existing waste streams if any */}
                        {order.waste_streams && order.waste_streams.length > 0 && (
                            <div className="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                                    Saved Weights
                                </h4>
                                <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                    <table className="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Material
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Weight (kg)
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Rebate
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            {order.waste_streams.map((ws) => {
                                                const material = ws.material;
                                                const companyRebatePercentage = order.site?.branch?.company?.rebate_percentage;
                                                const clientShare = companyRebatePercentage !== null && companyRebatePercentage !== undefined 
                                                    ? companyRebatePercentage 
                                                    : (material?.client_rebate_share || 100);
                                                const rebateAmount = material?.rebate_offered && material?.rebate_rate && ws.nett_weight
                                                    ? Number(ws.nett_weight * material.rebate_rate * clientShare / 100).toFixed(2)
                                                    : null;
                                                return (
                                                    <tr key={ws.id}>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                            {material ? getMaterialDisplayName(material) : `Material ID: ${ws.material_id || 'N/A'}`}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                            {ws.nett_weight || ws.gross_weight || '—'} kg
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                            {rebateAmount ? (
                                                                <span className="text-green-600 font-medium">
                                                                    R{rebateAmount}
                                                                </span>
                                                            ) : (
                                                                <span className="text-gray-400">—</span>
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="mt-3 px-6 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-b-lg border-t border-gray-200 dark:border-gray-600">
                                    <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Total weight: <span className="text-gray-900 dark:text-gray-100">{Number(totalSavedWeight).toFixed(3)} kg</span>
                                    </span>
                                </div>
                            </div>
                        )}

                        {/* Total Rebate Summary */}
                        {order.waste_streams && order.waste_streams.length > 0 && totalRebate > 0 && (
                            <div className="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <div className="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <span className="text-xs uppercase tracking-wide text-green-700 dark:text-green-300">Total Rebate Amount</span>
                                            <p className="text-lg font-semibold text-green-900 dark:text-green-100 mt-1">
                                                R {Number(totalRebate).toFixed(2)}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <span className="text-xs text-green-700 dark:text-green-300">Based on captured weights</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Finalization Form - Separate form for finalization */}
                {canManageOrder && (
                <form onSubmit={handleFinalizeSubmit} className="space-y-6">
                    {/* Finalization Details */}
                    <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                        <div className="px-4 py-5 sm:p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-6">
                                Finalization Details
                            </h3>
                            
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label htmlFor="actual_collection_date" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                        Actual Collection Date
                                    </label>
                                    <input
                                        type="date"
                                        id="actual_collection_date"
                                        value={finalizeData.actual_collection_date}
                                        onChange={(e) => setFinalizeData('actual_collection_date', e.target.value)}
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Defaults to requested date: {order.requested_collection_date ? new Date(order.requested_collection_date).toLocaleDateString() : '—'}
                                    </p>
                                    {finalizeErrors.actual_collection_date && (
                                        <p className="mt-1 text-sm text-red-600">{finalizeErrors.actual_collection_date}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="actual_quantity" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                        Actual Quantity
                                    </label>
                                    <input
                                        type="number"
                                        id="actual_quantity"
                                        value={finalizeData.actual_quantity}
                                        onChange={(e) => setFinalizeData('actual_quantity', e.target.value)}
                                        min="0"
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                        placeholder={order.estimated_quantity ? `Estimated: ${order.estimated_quantity}` : ''}
                                    />
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {order.estimated_quantity ? `Estimated quantity: ${order.estimated_quantity}` : 'No estimated quantity'}
                                    </p>
                                    {finalizeErrors.actual_quantity && (
                                        <p className="mt-1 text-sm text-red-600">{finalizeErrors.actual_quantity}</p>
                                    )}
                                </div>

                                <div className="sm:col-span-2">
                                    <label htmlFor="slip_number" className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                        Slip Number <span className="text-red-600">*</span>
                                    </label>
                                    <div className="flex rounded-md shadow-sm">
                                        {hasSlipPrefix && (
                                            <span className="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm dark:bg-gray-600 dark:border-gray-500 dark:text-gray-300">
                                                {slipPrefix}-
                                            </span>
                                        )}
                                        <input
                                            type="text"
                                            id="slip_number"
                                            value={slipNumberPart}
                                            onChange={(e) => setSlipNumberPart(e.target.value)}
                                            className={`block w-full border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 ${hasSlipPrefix ? 'rounded-r-md rounded-l-none border-l-0' : 'rounded-md'}`}
                                            placeholder={hasSlipPrefix ? 'e.g. 12345' : 'Enter slip number from service provider'}
                                            required
                                        />
                                    </div>
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {hasSlipPrefix
                                            ? `Prefix ${slipPrefix}- is added automatically. Enter the slip number provided by the service provider.`
                                            : 'Required: Enter the slip number provided by the service provider'}
                                    </p>
                                    {slipNumberExistsError && (
                                        <p className="mt-1 text-sm text-red-600 flex items-center gap-1">
                                            <AlertCircle className="h-4 w-4 shrink-0" />
                                            This slip number is already used on another order. Please enter a unique slip number.
                                        </p>
                                    )}
                                    {finalizeErrors.slip_number && (
                                        <p className="mt-1 text-sm text-red-600">{finalizeErrors.slip_number}</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Form Actions */}
                    <div className="flex justify-end space-x-3 pt-4">
                        <Link
                            href={route('orders.show', order.id)}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            disabled={finalizing || slipNumberExistsError || !order.supporting_documents || order.supporting_documents.length < 1 || !order.waste_streams || order.waste_streams.length < 1 || !finalizeData.slip_number || finalizeData.slip_number.trim() === ''}
                            className="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <CheckCircle className="h-4 w-4 mr-2" />
                            {finalizing ? 'Finalizing...' : 'Finalize Order'}
                        </button>
                    </div>
                </form>
                )}
            </div>

            <Modal show={showFinalizeModal} onClose={() => !finalizing && setShowFinalizeModal(false)} maxWidth="md">
                <div className="p-6">
                    <div className="flex items-center mb-4">
                        <AlertTriangle className="h-6 w-6 text-green-600 mr-3" />
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Finalize Order
                        </h3>
                    </div>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Are you sure you want to finalize this order? This action cannot be undone. The order will be marked as completed and no further changes will be allowed.
                    </p>
                    <div className="flex justify-end space-x-3">
                        <button
                            onClick={() => setShowFinalizeModal(false)}
                            disabled={finalizing}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleFinalizeConfirm}
                            disabled={finalizing}
                            className="px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {finalizing ? 'Finalizing...' : 'Confirm Finalization'}
                        </button>
                    </div>
                </div>
            </Modal>

            <Modal show={showDeleteModal} onClose={() => setShowDeleteModal(false)} maxWidth="md">
                <div className="p-6">
                    <div className="flex items-center mb-4">
                        <AlertTriangle className="h-6 w-6 text-red-600 mr-3" />
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Delete Document
                        </h3>
                    </div>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Are you sure you want to delete this document? This action cannot be undone.
                    </p>
                    <div className="flex justify-end space-x-3">
                        <button
                            onClick={() => {
                                setShowDeleteModal(false);
                                setDocumentToDelete(null);
                            }}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleDeleteConfirm}
                            className="px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            Delete Document
                        </button>
                    </div>
                </div>
            </Modal>
        </DashboardLayout>
    );
}

