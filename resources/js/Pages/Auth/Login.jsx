import { Head, Link, useForm } from '@inertiajs/react';
import { Leaf, Mail, Lock, ArrowRight, Recycle, Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Log in - WasteFlow" />

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
                                <p className="text-primary-100 text-sm">Waste Management Portal</p>
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

                        {/* Features */}
                        <div className="space-y-4 pt-8">
                            <div className="flex items-start space-x-3">
                                <div className="bg-primary-500 p-2 rounded-lg">
                                    <Leaf className="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <h3 className="text-white font-semibold">Zero Waste Goals</h3>
                                    <p className="text-primary-100 text-sm">Drive sustainability with expert waste optimization</p>
                                </div>
                            </div>
                            <div className="flex items-start space-x-3">
                                <div className="bg-primary-500 p-2 rounded-lg">
                                    <Recycle className="h-5 w-5 text-white" />
                                </div>
                                <div>
                                    <h3 className="text-white font-semibold">Resource Recovery</h3>
                                    <p className="text-primary-100 text-sm">Maximize recycling and reduce environmental impact</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="relative z-10">
                        <p className="text-primary-200 text-sm">
                            © 2025 WasteFlow. All rights reserved.
                        </p>
                    </div>
                </div>

                {/* Right Side - Login Form */}
                <div className="w-full lg:w-1/2 flex items-center justify-center p-8">
                    <div className="w-full max-w-md">
                        {/* Mobile Logo */}
                        <div className="lg:hidden flex items-center justify-center mb-8">
                            <div className="bg-primary-600 p-3 rounded-xl shadow-lg">
                                <Recycle className="h-8 w-8 text-white" />
                            </div>
                            <h1 className="ml-3 text-2xl font-bold text-primary-800">WasteFlow</h1>
                        </div>

                        {/* Welcome Text */}
                        <div className="mb-8">
                            <h2 className="text-3xl font-bold text-gray-900 mb-2">Welcome Back</h2>
                            <p className="text-gray-600">Sign in to access your waste management dashboard</p>
                        </div>

                        {/* Status Message */}
                        {status && (
                            <div className="mb-4 p-4 rounded-lg bg-primary-50 border border-primary-200">
                                <p className="text-sm font-medium text-primary-700">{status}</p>
                            </div>
                        )}

                        {/* Login Form */}
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                                    Email Address
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <Mail className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        className="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-150 ease-in-out"
                                        placeholder="you@company.com"
                                        autoComplete="username"
                                        autoFocus
                                        onChange={(e) => setData('email', e.target.value)}
                                    />
                                </div>
                                {errors.email && (
                                    <p className="mt-2 text-sm text-red-600">{errors.email}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-2">
                                    Password
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <Lock className="h-5 w-5 text-gray-400" />
                                    </div>
                                    <input
                                        id="password"
                                        type={showPassword ? "text" : "password"}
                                        name="password"
                                        value={data.password}
                                        className="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition duration-150 ease-in-out"
                                        placeholder="••••••••"
                                        autoComplete="current-password"
                                        onChange={(e) => setData('password', e.target.value)}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute inset-y-0 right-0 pr-3 flex items-center"
                                    >
                                        {showPassword ? (
                                            <EyeOff className="h-5 w-5 text-gray-400 hover:text-gray-600 transition-colors" />
                                        ) : (
                                            <Eye className="h-5 w-5 text-gray-400 hover:text-gray-600 transition-colors" />
                                        )}
                                    </button>
                                </div>
                                {errors.password && (
                                    <p className="mt-2 text-sm text-red-600">{errors.password}</p>
                                )}
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="flex items-center">
                                    <input
                                        id="remember"
                                        name="remember"
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                        className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                    />
                                    <label htmlFor="remember" className="ml-2 block text-sm text-gray-700">
                                        Remember me
                                    </label>
                                </div>

                                {canResetPassword && (
                                    <Link
                                        href={route('password.request')}
                                        className="text-sm font-medium text-primary-600 hover:text-primary-500 transition duration-150 ease-in-out"
                                    >
                                        Forgot password?
                                    </Link>
                                )}
                            </div>

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
                                        Signing in...
                                    </>
                                ) : (
                                    <>
                                        Sign in
                                        <ArrowRight className="ml-2 h-5 w-5" />
                                    </>
                                )}
                            </button>
                        </form>

                        {/* Additional Links */}
                        <div className="mt-6">
                            <div className="relative">
                                <div className="absolute inset-0 flex items-center">
                                    <div className="w-full border-t border-gray-300"></div>
                                </div>
                                <div className="relative flex justify-center text-sm">
                                    <span className="px-2 bg-primary-50 text-gray-500">Or</span>
                                </div>
                            </div>

                            <div className="mt-6 text-center">
                                <p className="text-sm text-gray-600">
                                    Don't have an account?{' '}
                                    <Link
                                        href={route('register')}
                                        className="font-medium text-primary-600 hover:text-primary-500 transition duration-150 ease-in-out"
                                    >
                                        Register here
                                    </Link>
                                </p>
                            </div>

                            <div className="mt-4 text-center">
                                <p className="text-sm text-gray-600">
                                    Need help? Contact support at{' '}
                                    <a href="mailto:info@wasteflow.example.com" className="font-medium text-primary-600 hover:text-primary-500">
                                        info@wasteflow.example.com
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
