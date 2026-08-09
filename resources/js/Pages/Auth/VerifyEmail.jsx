import { Head, Link, useForm } from '@inertiajs/react';
import { Leaf, MailCheck, Recycle, LogOut } from 'lucide-react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <>
            <Head title="Verify Email - WasteFlow" />

            <div className="min-h-screen bg-primary-50 flex">
                {/* Left Side - Branding */}
                <div className="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-600 to-primary-800 py-12 px-24 flex-col justify-between relative overflow-hidden">
                    {/* Background Pattern */}
                    <div className="absolute inset-0 opacity-10">
                        <div className="absolute top-20 left-20">
                            <Recycle className="h-64 w-64 text-white" />
                        </div>
                        <div className="absolute bottom-20 right-20">
                            <Leaf className="h-48 w-48 text-white" />
                        </div>
                    </div>

                    {/* Logo and Company Name */}
                    <div className="relative z-10">
                        <div className="flex items-center space-x-3 mb-6">
                            <div className="bg-white p-3 rounded-xl shadow-lg">
                                <Recycle className="h-8 w-8 text-primary-600" />
                            </div>
                            <div>
                                <h1 className="text-3xl font-bold text-white">WasteFlow</h1>
                                <p className="text-primary-100 text-sm">Real-Time Waste Intelligence Portal</p>
                            </div>
                        </div>
                    </div>

                    {/* Main Content */}
                    <div className="relative z-10 space-y-6">
                        <h2 className="text-4xl font-bold text-white leading-tight">
                            Transforming Waste,<br />
                            Nurturing the Planet
                        </h2>
                        <p className="text-xl text-primary-100">
                            Professional waste management solutions for a sustainable future
                        </p>
                    </div>

                    {/* Footer */}
                    <div className="relative z-10">
                        <p className="text-primary-200 text-sm">
                            © 2025 WasteFlow. All rights reserved.
                        </p>
                    </div>
                </div>

                {/* Right Side - Verify Email */}
                <div className="w-full lg:w-1/2 flex items-center justify-center p-8">
                    <div className="w-full max-w-md">
                        {/* Mobile Logo */}
                        <div className="lg:hidden flex items-center justify-center mb-8">
                            <div className="bg-primary-600 p-3 rounded-xl shadow-lg">
                                <Recycle className="h-8 w-8 text-white" />
                            </div>
                            <h1 className="ml-3 text-2xl font-bold text-primary-800">WasteFlow</h1>
                        </div>

                        {/* Heading */}
                        <div className="mb-8 text-center">
                            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary-100">
                                <MailCheck className="h-7 w-7 text-primary-600" />
                            </div>
                            <h2 className="text-3xl font-bold text-gray-900 mb-2">Verify Your Email</h2>
                            <p className="text-gray-600">
                                Thanks for signing up! Before getting started, could you verify your email address by
                                clicking on the link we just emailed to you? If you didn't receive the email, we'll gladly
                                send you another.
                            </p>
                        </div>

                        {/* Status Message */}
                        {status === 'verification-link-sent' && (
                            <div className="mb-6 p-4 rounded-lg bg-primary-50 border border-primary-200">
                                <p className="text-sm font-medium text-primary-700">
                                    A new verification link has been sent to the email address you provided during registration.
                                </p>
                            </div>
                        )}

                        <form onSubmit={submit} className="space-y-6">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full flex items-center justify-center px-4 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
                            >
                                {processing ? (
                                    <>
                                        <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Sending...
                                    </>
                                ) : (
                                    'Resend Verification Email'
                                )}
                            </button>
                        </form>

                        <div className="mt-6 text-center">
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition duration-150 ease-in-out"
                            >
                                <LogOut className="mr-1.5 h-4 w-4" />
                                Log out
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
