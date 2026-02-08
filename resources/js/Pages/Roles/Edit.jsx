import { Head, useForm, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import PermissionsBySection from '@/Components/PermissionsBySection';
import { ArrowLeft, Save } from 'lucide-react';

function formatLabel(name) {
    return name
        .split('-')
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
        .join(' ');
}

export default function RolesEdit({ role, permissions }) {
    const { data, setData, put, processing, errors } = useForm({
        name: role?.name ?? '',
        permissions: role?.permissions ?? [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/roles/${role.id}`);
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
        <DashboardLayout title="Edit role">
            <Head title="Edit role" />

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
                        Edit role: {formatLabel(role?.name ?? '')}
                    </h3>

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
                                className="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:text-gray-100"
                                required
                            />
                            {errors.name && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.name}</p>
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
                                {processing ? 'Saving...' : 'Save changes'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </DashboardLayout>
    );
}
