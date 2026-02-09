import { Head, useForm, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Save, Trash2, Recycle, Truck, Package, AlertTriangle, Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { useState, useEffect, useMemo } from 'react';
import SearchableDropdown from '@/Components/SearchableDropdown';

export default function Create({ companies = [], branches = [], sites = [], materials, serviceProviders, containerOptions = [] }) {
    const [quantityLines, setQuantityLines] = useState([
        { id: 1, quantity_type: '', quantity: '', description: '' }
    ]);

    const getNextWorkWeekday = () => {
        const today = new Date();
        const dayOfWeek = today.getDay(); // 0 = Sunday, 1 = Monday, ..., 5 = Friday, 6 = Saturday
        
        let nextDate = new Date(today);
        
        if (dayOfWeek === 1) { // Monday -> Tuesday
            nextDate.setDate(today.getDate() + 1);
        } else if (dayOfWeek === 5) { // Friday -> Monday
            nextDate.setDate(today.getDate() + 3);
        } else if (dayOfWeek === 6) { // Saturday -> Monday
            nextDate.setDate(today.getDate() + 2);
        } else if (dayOfWeek === 0) { // Sunday -> Monday
            nextDate.setDate(today.getDate() + 1);
        } else { // Tuesday, Wednesday, Thursday -> next day
            nextDate.setDate(today.getDate() + 1);
        }
        
        return nextDate.toISOString().split('T')[0];
    };

    const { data, setData, post, processing, errors } = useForm({
        order_type: 'waste',
        company_id: '',
        branch_id: '',
        site_id: '',
        service_provider_id: '',
        quantity_lines: [],
        requested_collection_date: getNextWorkWeekday(),
        notes: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        const isWaste = data.order_type === 'waste';
        const validQuantityLines = quantityLines
            .filter(line => {
                const hasType = isWaste
                    ? (line.quantity_type && line.quantity_type !== '')
                    : (line.quantity_type && line.quantity_type !== '');
                const hasQuantity = line.quantity && parseInt(line.quantity) > 0;
                const hasDescriptionIfOther = isWaste || line.quantity_type !== 'other' || (line.description && line.description.trim() !== '');
                return hasType && hasQuantity && hasDescriptionIfOther;
            })
            .map(line => {
                if (isWaste) {
                    return { container_option_id: parseInt(line.quantity_type, 10), quantity: parseInt(line.quantity, 10) };
                }
                return {
                    quantity_type: line.quantity_type,
                    quantity: parseInt(line.quantity, 10),
                    ...(line.quantity_type === 'other' && line.description ? { description: line.description.trim() } : {})
                };
            });

        if (validQuantityLines.length === 0) {
            alert('Please add at least one quantity line');
            return;
        }

        // Validate required fields
        if (!data.company_id) {
            alert('Please select a company');
            return;
        }

        if (!data.branch_id) {
            alert('Please select a branch');
            return;
        }

        if (!data.service_provider_id) {
            alert('Please select a service provider');
            return;
        }

        if (!data.requested_collection_date) {
            alert('Please select a collection date');
            return;
        }

        // Prepare the complete submission data
        const submitData = {
            ...data,
            quantity_lines: validQuantityLines
        };
        
        // Submit using router.post to ensure data is sent correctly
        router.post('/orders', submitData);
    };

    // Waste order container types from settings (container options)
    const wasteQuantityTypes = (containerOptions || []).map(opt => ({
        value: String(opt.id),
        label: opt.name,
        icon: Package,
        color: 'blue',
    }));

    // Recycling order container types
    const recyclingQuantityTypes = [
        { 
            value: 'scrap_load', 
            label: 'Scrap Load', 
            icon: Truck, 
            color: 'orange',
            description: 'Scrap load container'
        },
        { 
            value: 'loose_bags', 
            label: 'Loose Bags', 
            icon: Package, 
            color: 'blue',
            description: 'Loose bags'
        },
        { 
            value: 'cage_8m3', 
            label: '8m³ Cage', 
            icon: Truck, 
            color: 'green',
            description: '8 cubic meter cage'
        },
        { 
            value: 'cage_20m3', 
            label: '20m³ Cage', 
            icon: Truck, 
            color: 'purple',
            description: '20 cubic meter cage'
        },
        { 
            value: 'other', 
            label: 'Other', 
            icon: Package, 
            color: 'gray',
            description: 'Other container type (please specify)'
        },
    ];

    // Get quantity types based on order type
    const quantityTypes = data.order_type === 'recycling' ? recyclingQuantityTypes : wasteQuantityTypes;

    // Filter branches based on selected company
    const availableBranches = useMemo(() => {
        if (!data.company_id) return [];
        return branches.filter(branch => branch.company_id == data.company_id);
    }, [branches, data.company_id]);

    // Filter sites based on selected branch
    const availableSites = useMemo(() => {
        if (!data.branch_id) return [];
        return sites.filter(site => site.branch_id == data.branch_id);
    }, [sites, data.branch_id]);

    // Handle company selection - clear branch and site
    const handleCompanyChange = (companyId) => {
        setData({
            ...data,
            company_id: companyId,
            branch_id: '',
            site_id: '',
        });
    };

    // Handle branch selection - clear site
    const handleBranchChange = (branchId) => {
        setData({
            ...data,
            branch_id: branchId,
            site_id: '',
        });
    };

    const addQuantityLine = () => {
        const newId = Math.max(...quantityLines.map(line => line.id), 0) + 1;
        setQuantityLines([...quantityLines, { id: newId, quantity_type: '', quantity: '', description: '' }]);
    };

    const removeQuantityLine = (id) => {
        if (quantityLines.length > 1) {
            setQuantityLines(quantityLines.filter(line => line.id !== id));
        }
    };

    const updateQuantityLine = (id, field, value) => {
        setQuantityLines(quantityLines.map(line => {
            if (line.id === id) {
                const updated = { ...line, [field]: value };
                // Clear description if type changes away from "other"
                if (field === 'quantity_type' && value !== 'other') {
                    updated.description = '';
                }
                return updated;
            }
            return line;
        }));
    };

    const getQuantityTypeLabel = (value) => {
        if (data.order_type === 'waste' && containerOptions?.length) {
            const opt = containerOptions.find(o => String(o.id) === String(value));
            return opt ? opt.name : value;
        }
        const allTypes = [...wasteQuantityTypes, ...recyclingQuantityTypes];
        return allTypes.find(type => type.value === value)?.label || value;
    };

    const getTotalContainers = () => {
        return quantityLines
            .filter(line => line.quantity_type && line.quantity && parseInt(line.quantity) > 0)
            .reduce((total, line) => total + parseInt(line.quantity), 0);
    };

    return (
        <DashboardLayout title="Create Order">
            <Head title="Create Order" />

            <div className="mb-6">
                <Link
                    href="/orders"
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Orders
                </Link>
            </div>

            <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-6">
                        Create New Order
                    </h3>

                    <form onSubmit={handleSubmit} className="space-y-8">
                        {/* Order Type Selection */}
                        <div>
                            <h4 className="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Order Type</h4>
                            <div className="grid grid-cols-2 gap-4">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setData('order_type', 'waste');
                                        // Reset quantity lines when switching order type
                                        setQuantityLines([{ id: 1, quantity_type: '', quantity: '', description: '' }]);
                                    }}
                                    className={`relative flex items-center justify-center p-6 border-2 rounded-lg transition-all ${
                                        data.order_type === 'waste'
                                            ? 'border-primary-600 bg-primary-50 shadow-md dark:bg-primary-900/20'
                                            : 'border-gray-300 hover:border-primary-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'
                                    }`}
                                >
                                    <Trash2 className={`w-8 h-8 mr-3 ${
                                        data.order_type === 'waste' ? 'text-primary-600' : 'text-gray-400 dark:text-gray-500'
                                    }`} />
                                    <div className="flex flex-col items-start">
                                        <span className={`text-lg font-semibold ${
                                            data.order_type === 'waste' ? 'text-primary-700' : 'text-gray-700 dark:text-gray-200'
                                        }`}>
                                            Waste Order
                                        </span>
                                        <span className="text-sm text-gray-500 dark:text-gray-400">
                                            General waste collection
                                        </span>
                                    </div>
                                    {data.order_type === 'waste' && (
                                        <div className="absolute top-3 right-3">
                                            <svg className="w-6 h-6 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                            </svg>
                                        </div>
                                    )}
                                </button>

                                <button
                                    type="button"
                                    onClick={() => {
                                        setData('order_type', 'recycling');
                                        // Reset quantity lines when switching order type
                                        setQuantityLines([{ id: 1, quantity_type: '', quantity: '', description: '' }]);
                                    }}
                                    className={`relative flex items-center justify-center p-6 border-2 rounded-lg transition-all ${
                                        data.order_type === 'recycling'
                                            ? 'border-primary-600 bg-primary-50 shadow-md dark:bg-primary-900/20'
                                            : 'border-gray-300 hover:border-primary-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700'
                                    }`}
                                >
                                    <Recycle className={`w-8 h-8 mr-3 ${
                                        data.order_type === 'recycling' ? 'text-primary-600' : 'text-gray-400 dark:text-gray-500'
                                    }`} />
                                    <div className="flex flex-col items-start">
                                        <span className={`text-lg font-semibold ${
                                            data.order_type === 'recycling' ? 'text-primary-700' : 'text-gray-700 dark:text-gray-200'
                                        }`}>
                                            Recycling Order
                                        </span>
                                        <span className="text-sm text-gray-500 dark:text-gray-400">
                                            Pre-sorted recyclables
                                        </span>
                                    </div>
                                    {data.order_type === 'recycling' && (
                                        <div className="absolute top-3 right-3">
                                            <svg className="w-6 h-6 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                            </svg>
                                        </div>
                                    )}
                                </button>
                            </div>
                            {errors.order_type && (
                                <p className="mt-2 text-sm text-red-600">{errors.order_type}</p>
                            )}
                        </div>

                        {/* Company, Branch, Site, and Service Provider Selection */}
                        <div>
                            <h4 className="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Collection Details</h4>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Company Selection */}
                                <div>
                                    <SearchableDropdown
                                        id="company_id"
                                        name="company_id"
                                        label="Company"
                                        placeholder="Select a company"
                                        options={companies}
                                        value={data.company_id}
                                        onChange={handleCompanyChange}
                                        required
                                        error={errors.company_id}
                                    />
                                </div>

                                {/* Branch Selection */}
                                <div>
                                    <SearchableDropdown
                                        id="branch_id"
                                        name="branch_id"
                                        label="Branch"
                                        placeholder={data.company_id ? "Select a branch" : "Select a company first"}
                                        options={availableBranches}
                                        value={data.branch_id}
                                        onChange={handleBranchChange}
                                        disabled={!data.company_id}
                                        required
                                        error={errors.branch_id}
                                    />
                                </div>

                                {/* Site Selection - optional */}
                                <div>
                                    <SearchableDropdown
                                        id="site_id"
                                        name="site_id"
                                        label="Collection Site (optional)"
                                        placeholder={data.branch_id ? "Select a site or leave blank" : "Select a branch first"}
                                        options={availableSites}
                                        value={data.site_id}
                                        onChange={(siteId) => setData('site_id', siteId)}
                                        disabled={!data.branch_id}
                                        error={errors.site_id}
                                    />
                                </div>

                                {/* Service Provider Selection */}
                                <div>
                                    <SearchableDropdown
                                        id="service_provider_id"
                                        name="service_provider_id"
                                        label="Service Provider"
                                        placeholder="Select a service provider"
                                        options={serviceProviders ? serviceProviders.filter(provider => {
                                            const providerTypes = provider.types || [];
                                            // Filter by order type
                                            if (data.order_type === 'waste') {
                                                return providerTypes.some(type => ['waste_collection', 'general'].includes(type));
                                            } else if (data.order_type === 'recycling') {
                                                return providerTypes.some(type => ['recycling', 'general'].includes(type));
                                            }
                                            return true;
                                        }) : []}
                                        value={data.service_provider_id}
                                        onChange={(providerId) => setData('service_provider_id', providerId)}
                                        getOptionLabel={(provider) => {
                                            const providerTypes = provider.types || [];
                                            const typeLabels = {
                                                waste_collection: 'Waste Collection',
                                                recycling: 'Recycling',
                                                hazardous: 'Hazardous',
                                                general: 'General',
                                            };
                                            const displayTypes = providerTypes.map(type => typeLabels[type] || type).join(', ');
                                            return displayTypes ? `${provider.name} (${displayTypes})` : provider.name;
                                        }}
                                        required
                                        error={errors.service_provider_id}
                                    />
                                </div>
                            </div>
                        </div>


                        {/* Quantity Lines Table */}
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <h4 className="text-md font-medium text-gray-900 dark:text-gray-100">Quantities</h4>
                                <button
                                    type="button"
                                    onClick={addQuantityLine}
                                    className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                                >
                                    <Plus className="h-4 w-4 mr-2" />
                                    Add Line
                                </button>
                            </div>

                            <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                <table className="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Container Type
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Quantity
                                            </th>
                                            {(data.order_type === 'recycling' && quantityLines.some(line => line.quantity_type === 'other')) && (
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Description
                                                </th>
                                            )}
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        {quantityLines.map((line, index) => (
                                            <tr key={line.id}>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <select
                                                        value={line.quantity_type}
                                                        onChange={(e) => updateQuantityLine(line.id, 'quantity_type', e.target.value)}
                                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                                        required
                                                    >
                                                        <option value="">Select type</option>
                                                        {quantityTypes.map((type) => (
                                                            <option key={type.value} value={type.value}>
                                                                {type.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <input
                                                        type="number"
                                                        value={line.quantity}
                                                        onChange={(e) => updateQuantityLine(line.id, 'quantity', e.target.value)}
                                                        min="1"
                                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                                        placeholder="0"
                                                        required
                                                    />
                                                </td>
                                                {data.order_type === 'recycling' && quantityLines.some(l => l.quantity_type === 'other') && (
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {line.quantity_type === 'other' ? (
                                                            <input
                                                                type="text"
                                                                value={line.description || ''}
                                                                onChange={(e) => updateQuantityLine(line.id, 'description', e.target.value)}
                                                                className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                                                placeholder="Describe container type..."
                                                                required={line.quantity_type === 'other'}
                                                            />
                                                        ) : (
                                                            <span className="text-gray-400 dark:text-gray-500">—</span>
                                                        )}
                                                    </td>
                                                )}
                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    {quantityLines.length > 1 && (
                                                        <button
                                                            type="button"
                                                            onClick={() => removeQuantityLine(line.id)}
                                                            className="text-red-600 hover:text-red-900"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {quantityLines.length > 0 && (
                                <div className="mt-4 text-sm text-gray-600 dark:text-gray-300">
                                    Total Containers: <span className="font-semibold">{getTotalContainers()}</span>
                                </div>
                            )}
                        </div>

                        {/* Additional Information */}
                        <div>
                            <h4 className="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Additional Information</h4>
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label htmlFor="requested_collection_date" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        Requested Collection Date *
                                    </label>
                                    <input
                                        type="date"
                                        id="requested_collection_date"
                                        value={data.requested_collection_date}
                                        onChange={(e) => setData('requested_collection_date', e.target.value)}
                                        min={new Date().toISOString().split('T')[0]}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                        required
                                    />
                                    {errors.requested_collection_date && (
                                        <p className="mt-1 text-sm text-red-600">{errors.requested_collection_date}</p>
                                    )}
                                </div>

                                <div className="sm:col-span-2">
                                    <label htmlFor="notes" className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        Notes
                                    </label>
                                    <textarea
                                        id="notes"
                                        rows={3}
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                        placeholder="Special instructions or additional notes..."
                                    />
                                    {errors.notes && (
                                        <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Order Summary */}
                        {data.company_id && data.branch_id && data.site_id && data.service_provider_id && getTotalContainers() > 0 && data.requested_collection_date && (
                            <div className="bg-primary-50 border border-primary-200 rounded-md p-4">
                                <div className="flex">
                                    <div className="flex-shrink-0">
                                        <svg className="h-5 w-5 text-primary-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                        </svg>
                                    </div>
                                    <div className="ml-3">
                                        <h3 className="text-sm font-medium text-primary-800">
                                            Order Summary
                                        </h3>
                                        <div className="mt-2 text-sm text-primary-700">
                                            <ul className="list-disc pl-5 space-y-1">
                                                <li><strong>Type:</strong> {data.order_type === 'waste' ? 'Waste Collection' : 'Recycling Collection'}</li>
                                                <li><strong>Company:</strong> {companies.find(c => c.id == data.company_id)?.name || 'N/A'}</li>
                                                <li><strong>Branch:</strong> {branches.find(b => b.id == data.branch_id)?.name || 'N/A'}</li>
                                                <li><strong>Site:</strong> {sites.find(s => s.id == data.site_id)?.name || 'N/A'}</li>
                                                <li><strong>Service Provider:</strong> {serviceProviders?.find(sp => sp.id == data.service_provider_id)?.name || 'N/A'}</li>
                                                <li><strong>Containers:</strong></li>
                                                <ul className="list-disc pl-5 ml-2">
                                                    {quantityLines
                                                        .filter(line => line.quantity_type && line.quantity && parseInt(line.quantity) > 0)
                                                        .map((line, index) => (
                                                            <li key={index}>
                                                                {line.quantity} {getQuantityTypeLabel(line.quantity_type)}
                                                            </li>
                                                        ))
                                                    }
                                                </ul>
                                                <li><strong>Total Containers:</strong> {getTotalContainers()}</li>
                                                <li><strong>Collection Date:</strong> {new Date(data.requested_collection_date).toLocaleDateString()}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="flex justify-end space-x-3">
                            <Link
                                href="/orders"
                                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Save className="h-4 w-4 mr-2" />
                                {processing ? 'Creating...' : 'Create Order'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}