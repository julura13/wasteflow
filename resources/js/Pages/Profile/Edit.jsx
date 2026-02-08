import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import AvatarUploadForm from './Partials/AvatarUploadForm';

export default function Edit({ mustVerifyEmail, status, user }) {
    return (
        <DashboardLayout title="Profile">
            <Head title="Profile" />

            <div className="py-6">
                <div className="mx-auto max-w-5xl space-y-6">
                    <div className="bg-white dark:bg-gray-800 p-4 shadow sm:rounded-lg sm:p-8">
                        <AvatarUploadForm user={user} status={status} className="max-w-xl" />
                    </div>

                    <div className="bg-white dark:bg-gray-800 p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    <div className="bg-white dark:bg-gray-800 p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

                    <div className="bg-white dark:bg-gray-800 p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}
