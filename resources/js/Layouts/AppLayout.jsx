import { Head } from '@inertiajs/react';
import { useState } from 'react';

export default function AppLayout({ children, title }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <>
            <Head title={title} />
            <div className="min-h-screen bg-gray-50">
                {/* Navigation */}
                <nav className="bg-white shadow-sm border-b border-gray-200">
                    <div className="mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <h1 className="text-2xl font-bold text-primary-600">
                                        WasteFlow
                                    </h1>
                                </div>
                            </div>

                            <div className="flex items-center space-x-4">
                                <a
                                    href="/"
                                    className="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"
                                >
                                    Home
                                </a>
                                <a
                                    href="/clients"
                                    className="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"
                                >
                                    Clients
                                </a>
                                <button
                                    type="button"
                                    className="bg-primary-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                >
                                    Dashboard
                                </button>
                            </div>
                        </div>
                    </div>
                </nav>

                {/* Main content */}
                <main className="mx-auto py-6 sm:px-6 lg:px-8">
                    {children}
                </main>
            </div>
        </>
    );
}
