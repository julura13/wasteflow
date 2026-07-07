import { Head, useForm, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import PermissionsBySection from '@/Components/PermissionsBySection';
import { ArrowLeft, Save } from 'lucide-react';

export default function RolesCreate({ permissions }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        permissions: [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/roles');
    };

    const togglePermission = (permName) => {
        setData(
            'permissions',
            data.permissions.includes(permName)
                ? data.permissions.filter((p) => p !== permName)
                : [...data.permissions, permName]
        );
    };

    return (
        <DashboardLayout title="Create role">
            <Head title="Create role" />

            <div className="mb-6">
                <Link
                    href="/roles"
                    className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <ArrowLeft className="h-4 w-4 mr-1" />
                    Back to Roles
                </Link>
            </div>

            <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-6">
                        Create new role
                    </h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Give the role a name (e.g. order_creator) and select which permissions it has.
                    </p>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <label
                                htmlFor="name"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                            >
                                Role name *
                            </label>
                            <input
                                type="text"
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="e.g. order_creator"
                                className="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                required
                            />
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Use lowercase with underscores (e.g. document_capture, order_finalizer).
                            </p>
                            {errors.name && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.name}</p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="description"
                                className="block text-sm font-medium text-gray-700 dark:text-gray-200"
                            >
                                Description
                            </label>
                            <textarea
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={2}
                                placeholder="What is this role for? (optional)"
                                className="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                            />
                            {errors.description && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.description}</p>
                            )}
                        </div>

                        <div>
                            <PermissionsBySection
                                permissions={permissions}
                                selectedPermissions={data.permissions}
                                onTogglePermission={togglePermission}
                                onSelectAll={(names) => setData('permissions', names)}
                                onClearAll={(names) => setData('permissions', names ?? [])}
                                errors={errors}
                            />
                        </div>

                        <div className="flex justify-end">
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 dark:focus:ring-offset-gray-800"
                            >
                                <Save className="h-4 w-4 mr-2" />
                                {processing ? 'Creating...' : 'Create role'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}
