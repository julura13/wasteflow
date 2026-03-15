import { useState } from 'react';
import Modal from '@/Components/Modal';
import { X } from 'lucide-react';

export const EDIT_REASON_OPTIONS = [
    { value: 'client_request', label: 'Client request' },
    { value: 'wrong_quantity', label: 'Wrong quantity entered' },
    { value: 'wrong_container_type', label: 'Wrong container type' },
    { value: 'date_correction', label: 'Date correction' },
    { value: 'data_entry_error', label: 'Data entry error' },
    { value: 'other', label: 'Other' },
];

export default function EditReasonModal({ show, onClose, onConfirm, title = 'Reason for this change' }) {
    const [reason, setReason] = useState('');
    const [reasonDetails, setReasonDetails] = useState('');

    const handleConfirm = (e) => {
        e.preventDefault();
        if (!reason) return;
        if (reason === 'other' && !reasonDetails.trim()) return;
        onConfirm({ reason, reason_details: reason === 'other' ? reasonDetails.trim() : '' });
        setReason('');
        setReasonDetails('');
        onClose();
    };

    const handleClose = () => {
        setReason('');
        setReasonDetails('');
        onClose();
    };

    const canConfirm = reason && (reason !== 'other' || reasonDetails.trim() !== '');

    return (
        <Modal show={show} onClose={handleClose}>
            <div className="p-6">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">{title}</h3>
                    <button
                        type="button"
                        onClick={handleClose}
                        className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Please select a reason for this change. This will be recorded in the activity log.
                </p>
                <form onSubmit={handleConfirm} className="space-y-4">
                    <div>
                        <label
                            htmlFor="edit_reason"
                            className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
                        >
                            Reason <span className="text-red-600">*</span>
                        </label>
                        <select
                            id="edit_reason"
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            required
                            className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                        >
                            <option value="">Select a reason</option>
                            {EDIT_REASON_OPTIONS.map((opt) => (
                                <option key={opt.value} value={opt.value}>
                                    {opt.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    {reason === 'other' && (
                        <div>
                            <label
                                htmlFor="edit_reason_details"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1"
                            >
                                Details <span className="text-red-600">*</span>
                            </label>
                            <textarea
                                id="edit_reason_details"
                                rows={3}
                                value={reasonDetails}
                                onChange={(e) => setReasonDetails(e.target.value)}
                                placeholder="Please provide details..."
                                className="block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                            />
                        </div>
                    )}
                    <div className="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            onClick={handleClose}
                            className="px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={!canConfirm}
                            className="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Confirm and save
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
