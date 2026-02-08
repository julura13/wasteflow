import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import Modal from '@/Components/Modal';
import { ArrowLeft, Building2, CheckCircle, XCircle, UserPlus, AlertTriangle } from 'lucide-react';
import { useState } from 'react';
import { useForm } from '@inertiajs/react';

export default function Show({ user, allCompanies }) {
    const [showAssignModal, setShowAssignModal] = useState(false);
    const [showRemoveModal, setShowRemoveModal] = useState(false);
    const [companyToRemove, setCompanyToRemove] = useState(null);
    
    const { data, setData, post, processing } = useForm({
        user_id: user.id,
        company_id: '',
        role: 'viewer',
    });

    const handleAssignCompany = () => {
        setShowAssignModal(true);
    };

    const submitAssign = (e) => {
        e.preventDefault();
        post(route('users.assign-company'), {
            onSuccess: () => {
                setShowAssignModal(false);
            },
        });
    };

    const handleRemoveCompany = (companyId) => {
        setCompanyToRemove(companyId);
        setShowRemoveModal(true);
    };

    const handleRemoveConfirm = () => {
        if (companyToRemove) {
            router.delete(route('users.remove-company', { user: user.id, company: companyToRemove }), {
                preserveScroll: true,
                onSuccess: () => {
                    setShowRemoveModal(false);
                    setCompanyToRemove(null);
                },
            });
        }
    };

    const handleApprove = () => {
        router.post(route('users.approve', user.id), {
            preserveScroll: true,
        });
    };

    return (
        <DashboardLayout title={`User: ${user.name}`}>
            <Head title={`User: ${user.name}`} />

            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link
                        href={route('users.index')}
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                    >
                        <ArrowLeft className="h-4 w-4 mr-1" />
                        Back to Users
                    </Link>
                </div>

                <div className="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                    <div className="px-4 py-5 sm:p-6">
                        <div className="flex items-center justify-between mb-6">
                            <div className="flex items-center">
                                {user.avatar ? (
                                    <img
                                        src={user.avatar}
                                        alt={user.name}
                                        className="h-16 w-16 rounded-full mr-4"
                                    />
                                ) : (
                                    <div className="h-16 w-16 rounded-full bg-primary-600 text-white flex items-center justify-center text-2xl font-medium mr-4">
                                        {user.name.charAt(0).toUpperCase()}
                                    </div>
                                )}
                                <div>
                                    <h3 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                        {user.name}
                                    </h3>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">{user.email}</p>
                                </div>
                            </div>
                            <div>
                                {user.is_active ? (
                                    <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        <CheckCircle className="h-4 w-4 mr-1" />
                                        Active
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                        <XCircle className="h-4 w-4 mr-1" />
                                        Pending Approval
                                    </span>
                                )}
                            </div>
                        </div>

                        {!user.is_active && (
                            <div className="mb-6">
                                <button
                                    onClick={handleApprove}
                                    className="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700"
                                >
                                    Approve User
                                </button>
                            </div>
                        )}

                        <div className="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 className="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Assigned Companies
                            </h4>
                            {user.companies && user.companies.length > 0 ? (
                                <div className="space-y-3">
                                    {user.companies.map((company) => (
                                        <div
                                            key={company.id}
                                            className="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-md"
                                        >
                                            <div className="flex items-center">
                                                <Building2 className="h-5 w-5 text-gray-400 mr-3" />
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {company.name}
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        Role: {company.role}
                                                    </p>
                                                </div>
                                            </div>
                                            <button
                                                onClick={() => handleRemoveCompany(company.id)}
                                                className="text-red-600 hover:text-red-900 text-sm"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    No companies assigned
                                </p>
                            )}
                            <button
                                onClick={handleAssignCompany}
                                className="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium text-primary-600 border border-primary-300 rounded-md hover:bg-primary-50"
                            >
                                <UserPlus className="h-4 w-4 mr-2" />
                                Assign to Company
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Assign Company Modal */}
            {showAssignModal && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                        <div className="mt-3">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Assign to Company
                            </h3>
                            <form onSubmit={submitAssign}>
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Company
                                    </label>
                                    <select
                                        value={data.company_id}
                                        onChange={(e) => setData('company_id', e.target.value)}
                                        className="block w-full border-gray-300 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="">Select a company</option>
                                        {allCompanies.map((company) => (
                                            <option key={company.id} value={company.id}>
                                                {company.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Role
                                    </label>
                                    <select
                                        value={data.role}
                                        onChange={(e) => setData('role', e.target.value)}
                                        className="block w-full border-gray-300 rounded-md shadow-sm"
                                    >
                                        <option value="viewer">Viewer</option>
                                        <option value="manager">Manager</option>
                                    </select>
                                </div>
                                <div className="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        onClick={() => setShowAssignModal(false)}
                                        className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700"
                                    >
                                        Assign
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            <Modal show={showRemoveModal} onClose={() => setShowRemoveModal(false)} maxWidth="md">
                <div className="p-6">
                    <div className="flex items-center mb-4">
                        <AlertTriangle className="h-6 w-6 text-red-600 mr-3" />
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Remove from Company
                        </h3>
                    </div>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Are you sure you want to remove this user from the company? This action cannot be undone.
                    </p>
                    <div className="flex justify-end space-x-3">
                        <button
                            onClick={() => {
                                setShowRemoveModal(false);
                                setCompanyToRemove(null);
                            }}
                            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleRemoveConfirm}
                            className="px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            </Modal>
        </DashboardLayout>
    );
}

