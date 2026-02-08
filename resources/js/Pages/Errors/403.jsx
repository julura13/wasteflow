import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { AlertTriangle, Mail, Home } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function Error403({ status, message }) {
    const [showModal, setShowModal] = useState(true);
    const errorMessage = message || 'This action is unauthorized.';

    useEffect(() => {
        setShowModal(true);
    }, []);

    return (
        <DashboardLayout title="Access Denied">
            <Head title="Access Denied" />

            {showModal && (
                <div className="fixed inset-0 z-50 overflow-y-auto">
                    <div className="flex min-h-screen items-center justify-center p-4">
                        <div className="fixed inset-0 bg-gray-900/70 transition-opacity" onClick={() => setShowModal(false)} />
                        
                        <div className="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-xl transition-all sm:w-full sm:max-w-lg">
                            <div className="bg-white dark:bg-gray-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                <div className="sm:flex sm:items-start">
                                    <div className="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                        <AlertTriangle className="h-6 w-6 text-red-600 dark:text-red-400" />
                                    </div>
                                    <div className="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                        <h3 className="text-base font-semibold leading-6 text-gray-900 dark:text-gray-100">
                                            Access Denied
                                        </h3>
                                        <div className="mt-2">
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                {errorMessage.includes('No company assigned') ? (
                                                    <>
                                                        You don't have a company assigned to your account yet. 
                                                        Please contact your administrator to get access.
                                                    </>
                                                ) : (
                                                    errorMessage
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                <div className="flex flex-col gap-2 sm:flex-row sm:justify-end">
                                    {errorMessage.includes('No company assigned') && (
                                        <a
                                            href="mailto:info@wasteflow.example.com"
                                            className="inline-flex w-full justify-center items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 sm:ml-3 sm:w-auto"
                                        >
                                            <Mail className="h-4 w-4 mr-2" />
                                            Contact Support
                                        </a>
                                    )}
                                    <Link
                                        href="/dashboard"
                                        className="inline-flex w-full justify-center items-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:ml-3 sm:w-auto"
                                        onClick={() => setShowModal(false)}
                                    >
                                        <Home className="h-4 w-4 mr-2" />
                                        Go to Dashboard
                                    </Link>
                                    <button
                                        type="button"
                                        className="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto"
                                        onClick={() => setShowModal(false)}
                                    >
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            <div className="max-w-7xl mx-auto py-12">
                <div className="text-center">
                    <AlertTriangle className="mx-auto h-24 w-24 text-red-500" />
                    <h1 className="mt-4 text-3xl font-bold text-gray-900 dark:text-gray-100">403</h1>
                    <p className="mt-2 text-lg text-gray-600 dark:text-gray-400">Access Denied</p>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-500">{errorMessage}</p>
                    <div className="mt-6">
                        <Link
                            href="/dashboard"
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700"
                        >
                            <Home className="h-4 w-4 mr-2" />
                            Go to Dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}

