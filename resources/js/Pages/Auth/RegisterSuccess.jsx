import { Head, Link } from '@inertiajs/react';
import { CheckCircle, Mail, ArrowRight } from 'lucide-react';

export default function RegisterSuccess() {
    return (
        <>
            <Head title="Registration Successful - WasteFlow" />
            
            <div className="min-h-screen bg-gradient-to-br from-primary-50 via-white to-primary-100 flex items-center justify-center p-4">
                <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
                    <div className="text-center">
                        <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                            <CheckCircle className="h-10 w-10 text-green-600" />
                        </div>
                        
                        <h1 className="text-2xl font-bold text-gray-900 mb-2">
                            Registration Successful!
                        </h1>
                        
                        <p className="text-gray-600 mb-6">
                            Your account has been created successfully. However, your account is pending approval by an administrator.
                        </p>
                        
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div className="flex items-start">
                                <Mail className="h-5 w-5 text-blue-600 mt-0.5 mr-3" />
                                <div className="text-left">
                                    <p className="text-sm font-medium text-blue-900 mb-1">
                                        What happens next?
                                    </p>
                                    <p className="text-sm text-blue-700">
                                        An administrator will review your registration and assign you to a company. Once approved, you'll be able to access the portal.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <Link
                            href="/login"
                            className="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            Go to Login
                            <ArrowRight className="ml-2 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}

