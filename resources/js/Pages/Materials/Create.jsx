import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { ArrowLeft, Save } from 'lucide-react';

export default function Create({ lookups }) {
    const { data, setData, post, processing, errors } = useForm({
        waste_stream_id: '',
        grade_id: '',
        classification_id: '',
        facility_id: '',
        service_provider_id: '',
        weight_required: 'Yes',
        rebate_offered: false,
        rebate_rate: '',
        client_rebate_share: 50,
        backing_document: false,
        notes: '',
        is_active: true,
    });

    const handleSubmit = (event) => {
        event.preventDefault();
        post('/materials');
    };

    const selectField = (id, label, options, required = true) => (
        <div>
            <label htmlFor={id} className="block text-sm font-medium text-gray-700">
                {label} {required && <span className="text-red-500">*</span>}
            </label>
            <select
                id={id}
                value={data[id] ?? ''}
                onChange={(e) => setData(id, e.target.value)}
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                required={required}
            >
                <option value="">Select...</option>
                {options.map((option) => (
                    <option key={option.id} value={option.id}>
                        {option.name}
                    </option>
                ))}
            </select>
            {errors[id] && <p className="mt-1 text-sm text-red-600">{errors[id]}</p>}
        </div>
    );

    return (
        <DashboardLayout title="Create Material">
            <Head title="Create Material" />

            <div className="mb-6">
                <Link
                    href="/materials"
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Materials
                </Link>
            </div>

            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 mb-6">
                        Create Material Definition
                    </h3>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            {selectField('waste_stream_id', 'Waste Stream', lookups.wasteStreams)}
                            {selectField('grade_id', 'Grade', lookups.grades)}
                            {selectField('classification_id', 'Classification', lookups.classifications)}
                            {selectField('facility_id', 'Facility', lookups.facilities)}
                            {selectField('service_provider_id', 'Default Service Provider', lookups.serviceProviders, false)}
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label htmlFor="weight_required" className="block text-sm font-medium text-gray-700">
                                    Weight Requirement *
                                </label>
                                <input
                                    type="text"
                                    id="weight_required"
                                    value={data.weight_required}
                                    onChange={(e) => setData('weight_required', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                    placeholder="Yes, No, Yes/No, No Average Weight"
                                    required
                                />
                                {errors.weight_required && (
                                    <p className="mt-1 text-sm text-red-600">{errors.weight_required}</p>
                                )}
                            </div>

                            <div className="flex items-center space-x-6">
                                <label className="inline-flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        checked={data.rebate_offered}
                                        onChange={(e) => {
                                            const checked = e.target.checked;
                                            setData('rebate_offered', checked);
                                            if (checked) {
                                                if (!data.rebate_rate) {
                                                    setData('rebate_rate', '');
                                                }
                                                if (data.client_rebate_share === '' || data.client_rebate_share === null || data.client_rebate_share === undefined) {
                                                    setData('client_rebate_share', 50);
                                                }
                                            } else {
                                                setData('rebate_rate', '');
                                                setData('client_rebate_share', 50);
                                            }
                                        }}
                                        className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                    />
                                    <span className="text-sm text-gray-700">Rebate Offered</span>
                                </label>
                                <label className="inline-flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        checked={data.backing_document}
                                        onChange={(e) => setData('backing_document', e.target.checked)}
                                        className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                    />
                                    <span className="text-sm text-gray-700">Backing Document Required</span>
                                </label>
                                <label className="inline-flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                    />
                                    <span className="text-sm text-gray-700">Active</span>
                                </label>
                            </div>
                            {(errors.rebate_offered || errors.backing_document || errors.is_active) && (
                                <div className="md:col-span-2">
                                    {[errors.rebate_offered, errors.backing_document, errors.is_active]
                                        .filter(Boolean)
                                        .map((message, index) => (
                                            <p key={index} className="text-sm text-red-600">
                                                {message}
                                            </p>
                                        ))}
                                </div>
                            )}
                        </div>

                        {data.rebate_offered && (
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label htmlFor="rebate_rate" className="block text-sm font-medium text-gray-700">
                                        Rebate Rate (R/kg) *
                                    </label>
                                    <div className="mt-1 relative rounded-md shadow-sm">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span className="text-gray-500 sm:text-sm">R</span>
                                        </div>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            id="rebate_rate"
                                            value={data.rebate_rate ?? ''}
                                            onChange={(e) => setData('rebate_rate', e.target.value)}
                                            className="pl-8 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                            placeholder="0.00"
                                            required
                                        />
                                    </div>
                                    {errors.rebate_rate && (
                                        <p className="mt-1 text-sm text-red-600">{errors.rebate_rate}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="client_rebate_share" className="block text-sm font-medium text-gray-700">
                                        Client Rebate Share (%) *
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        id="client_rebate_share"
                                        value={data.client_rebate_share ?? ''}
                                        onChange={(e) => setData('client_rebate_share', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                        placeholder="50"
                                        required
                                    />
                                    {errors.client_rebate_share && (
                                        <p className="mt-1 text-sm text-red-600">{errors.client_rebate_share}</p>
                                    )}
                                </div>
                            </div>
                        )}

                        <div>
                            <label htmlFor="notes" className="block text-sm font-medium text-gray-700">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                rows={3}
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                placeholder="Additional operational notes (optional)"
                            />
                            {errors.notes && <p className="mt-1 text-sm text-red-600">{errors.notes}</p>}
                        </div>

                        <div className="flex justify-end space-x-3">
                            <Link
                                href="/materials"
                                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                            >
                                <Save className="h-4 w-4 mr-2" />
                                {processing ? 'Creating...' : 'Create Material'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}
