import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import SearchableDropdown from '@/Components/SearchableDropdown';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';
import { useState, useMemo, useEffect } from 'react';

const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
const DAY_LABELS = { monday: 'Mon', tuesday: 'Tue', wednesday: 'Wed', thursday: 'Thu', friday: 'Fri', saturday: 'Sat', sunday: 'Sun' };

function filterProvidersByOrderType(providers, orderType) {
    if (!providers?.length) return [];
    return providers.filter((p) => {
        const types = p.types || [];
        if (orderType === 'waste') return types.some((t) => ['waste_collection', 'general'].includes(t));
        if (orderType === 'recycling') return types.some((t) => ['recycling', 'general'].includes(t));
        return true;
    });
}

export default function RecurringOrderForm({
    recurringOrder = null,
    companies = [],
    branches = [],
    sites = [],
    serviceProviders = [],
    containerOptionsWaste = [],
    containerOptionsRecycling = [],
}) {
    const isEdit = !!recurringOrder;

    const initLines = () => {
        if (isEdit && recurringOrder.quantity_lines?.length) {
            return recurringOrder.quantity_lines.map((l, i) => ({
                id: i + 1,
                container_option_id: String(l.container_option_id),
                quantity: String(l.quantity),
                description: l.description ?? '',
            }));
        }
        return [{ id: 1, container_option_id: '', quantity: '', description: '' }];
    };

    const [orderType, setOrderType] = useState(recurringOrder?.order_type ?? 'waste');
    const [companyId, setCompanyId] = useState(String(recurringOrder?.company_id ?? ''));
    const [branchId, setBranchId] = useState(String(recurringOrder?.branch_id ?? ''));
    const [siteId, setSiteId] = useState(String(recurringOrder?.site_id ?? ''));
    const [serviceProviderId, setServiceProviderId] = useState(String(recurringOrder?.service_provider_id ?? ''));
    const [daysOfWeek, setDaysOfWeek] = useState(recurringOrder?.days_of_week ?? []);
    const [quantityLines, setQuantityLines] = useState(initLines);
    const [notes, setNotes] = useState(recurringOrder?.notes ?? '');
    const [isActive, setIsActive] = useState(recurringOrder?.is_active ?? true);
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    const filteredProviders = useMemo(() => filterProvidersByOrderType(serviceProviders, orderType), [serviceProviders, orderType]);
    const availableBranches = useMemo(() => branches.filter((b) => b.company_id == companyId), [branches, companyId]);
    const availableSites = useMemo(() => sites.filter((s) => s.branch_id == branchId), [sites, branchId]);
    const containerOptions = useMemo(() => orderType === 'recycling' ? containerOptionsRecycling : containerOptionsWaste, [orderType, containerOptionsWaste, containerOptionsRecycling]);

    // Auto-select default service provider when company or order type changes
    useEffect(() => {
        if (!companyId) { setServiceProviderId(''); return; }
        const company = companies.find((c) => String(c.id) === companyId);
        if (!company) return;
        const rawId = orderType === 'recycling' ? company.default_recycling_service_provider_id : company.default_waste_service_provider_id;
        if (rawId == null) return;
        const allowed = filteredProviders.some((p) => String(p.id) === String(rawId));
        if (allowed && !isEdit) setServiceProviderId(String(rawId));
    }, [companyId, orderType, filteredProviders]);

    const toggleDay = (day) => {
        setDaysOfWeek((prev) => prev.includes(day) ? prev.filter((d) => d !== day) : [...prev, day]);
    };

    const addLine = () => {
        const nextId = Math.max(...quantityLines.map((l) => l.id), 0) + 1;
        setQuantityLines((prev) => [...prev, { id: nextId, container_option_id: '', quantity: '', description: '' }]);
    };

    const updateLine = (id, field, value) => {
        setQuantityLines((prev) => prev.map((l) => l.id === id ? { ...l, [field]: value } : l));
    };

    const removeLine = (id) => {
        setQuantityLines((prev) => prev.filter((l) => l.id !== id));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const validLines = quantityLines
            .filter((l) => l.container_option_id && parseInt(l.quantity) > 0)
            .map((l) => ({
                container_option_id: parseInt(l.container_option_id, 10),
                quantity: parseInt(l.quantity, 10),
                ...(l.description?.trim() ? { description: l.description.trim() } : {}),
            }));

        const payload = {
            order_type: orderType,
            company_id: companyId ? parseInt(companyId) : '',
            branch_id: branchId ? parseInt(branchId) : '',
            site_id: siteId ? parseInt(siteId) : null,
            service_provider_id: serviceProviderId ? parseInt(serviceProviderId) : '',
            days_of_week: daysOfWeek,
            quantity_lines: validLines,
            notes: notes || null,
            is_active: isActive,
        };

        setProcessing(true);
        const method = isEdit ? 'put' : 'post';
        const url = isEdit ? route('recurring-orders.update', recurringOrder.id) : route('recurring-orders.store');

        router[method](url, payload, {
            onError: (errs) => { setErrors(errs); setProcessing(false); },
            onSuccess: () => setProcessing(false),
        });
    };

    const fieldError = (key) => errors[key] && (
        <p className="mt-1 text-xs text-red-600 dark:text-red-400">{errors[key]}</p>
    );

    return (
        <DashboardLayout title={isEdit ? 'Edit Recurring Order' : 'New Recurring Order'}>
            <Head title={isEdit ? 'Edit Recurring Order' : 'New Recurring Order'} />

            <div className="mb-6 flex items-center gap-4">
                <Link href={route('recurring-orders.index')} className="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">
                    <ArrowLeft className="h-4 w-4 mr-1" /> Back
                </Link>
                <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {isEdit ? 'Edit Recurring Order' : 'New Recurring Order'}
                </h2>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6 max-w-3xl">
                {/* Order type */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border border-gray-200 dark:border-gray-700">
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Order Type</h3>
                    <div className="flex gap-4">
                        {['waste', 'recycling'].map((type) => (
                            <label key={type} className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    name="order_type"
                                    value={type}
                                    checked={orderType === type}
                                    onChange={() => { setOrderType(type); setQuantityLines([{ id: 1, container_option_id: '', quantity: '', description: '' }]); }}
                                    className="text-primary-600 focus:ring-primary-500"
                                />
                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">{type} Order</span>
                            </label>
                        ))}
                    </div>
                </div>

                {/* Customer */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border border-gray-200 dark:border-gray-700">
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Customer</h3>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Company *</label>
                            <SearchableDropdown
                                options={companies}
                                value={companyId}
                                onChange={(v) => { setCompanyId(v); setBranchId(''); setSiteId(''); }}
                                placeholder="Select company"
                            />
                            {fieldError('company_id')}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Branch *</label>
                            <SearchableDropdown
                                options={availableBranches}
                                value={branchId}
                                onChange={(v) => { setBranchId(v); setSiteId(''); }}
                                placeholder="Select branch"
                                disabled={!companyId}
                            />
                            {fieldError('branch_id')}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Site</label>
                            <SearchableDropdown
                                options={availableSites}
                                value={siteId}
                                onChange={setSiteId}
                                placeholder="Select site (optional)"
                                disabled={!branchId}
                            />
                        </div>
                    </div>
                </div>

                {/* Service provider */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border border-gray-200 dark:border-gray-700">
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Service Provider</h3>
                    <SearchableDropdown
                        options={filteredProviders}
                        value={serviceProviderId}
                        onChange={setServiceProviderId}
                        placeholder="Select service provider"
                    />
                    {fieldError('service_provider_id')}
                </div>

                {/* Days of the week */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border border-gray-200 dark:border-gray-700">
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Schedule — Days of Week</h3>
                    <div className="flex gap-2 flex-wrap">
                        {DAYS.map((day) => (
                            <label key={day} className="cursor-pointer">
                                <input type="checkbox" className="sr-only" checked={daysOfWeek.includes(day)} onChange={() => toggleDay(day)} />
                                <span className={[
                                    'inline-block px-4 py-2 rounded-md text-sm font-medium border transition-colors select-none',
                                    daysOfWeek.includes(day)
                                        ? 'bg-primary-600 border-primary-600 text-white'
                                        : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600',
                                ].join(' ')}>
                                    {DAY_LABELS[day]}
                                </span>
                            </label>
                        ))}
                    </div>
                    {errors.days_of_week && <p className="mt-2 text-xs text-red-600 dark:text-red-400">{errors.days_of_week}</p>}
                </div>

                {/* Container quantity lines */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border border-gray-200 dark:border-gray-700">
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Container Quantities</h3>
                    <div className="space-y-3">
                        {quantityLines.map((line) => (
                            <div key={line.id} className="flex gap-3 items-start">
                                <div className="flex-1">
                                    <select
                                        value={line.container_option_id}
                                        onChange={(e) => updateLine(line.id, 'container_option_id', e.target.value)}
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    >
                                        <option value="">Select container</option>
                                        {containerOptions.map((o) => (
                                            <option key={o.id} value={String(o.id)}>{o.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="w-24">
                                    <input
                                        type="number"
                                        min="1"
                                        value={line.quantity}
                                        onChange={(e) => updateLine(line.id, 'quantity', e.target.value)}
                                        placeholder="Qty"
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                </div>
                                <div className="flex-1">
                                    <input
                                        type="text"
                                        value={line.description}
                                        onChange={(e) => updateLine(line.id, 'description', e.target.value)}
                                        placeholder="Description (optional)"
                                        className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                    />
                                </div>
                                {quantityLines.length > 1 && (
                                    <button type="button" onClick={() => removeLine(line.id)} className="mt-1 text-red-500 hover:text-red-700">
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                    <button type="button" onClick={addLine} className="mt-3 inline-flex items-center text-sm text-primary-600 hover:text-primary-800">
                        <Plus className="h-4 w-4 mr-1" /> Add container line
                    </button>
                    {errors.quantity_lines && <p className="mt-1 text-xs text-red-600 dark:text-red-400">{errors.quantity_lines}</p>}
                </div>

                {/* Notes + active */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border border-gray-200 dark:border-gray-700 space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Notes</label>
                        <textarea
                            rows={3}
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            placeholder="e.g. Clear all bins — open order"
                            className="block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        />
                    </div>
                    <label className="flex items-center gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            checked={isActive}
                            onChange={(e) => setIsActive(e.target.checked)}
                            className="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 h-4 w-4"
                        />
                        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Active — generate orders on scheduled days</span>
                    </label>
                </div>

                <div className="flex gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center px-5 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50"
                    >
                        <Save className="h-4 w-4 mr-2" />
                        {processing ? 'Saving…' : isEdit ? 'Update' : 'Create Recurring Order'}
                    </button>
                    <Link href={route('recurring-orders.index')} className="inline-flex items-center px-5 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600">
                        Cancel
                    </Link>
                </div>
            </form>
        </DashboardLayout>
    );
}
