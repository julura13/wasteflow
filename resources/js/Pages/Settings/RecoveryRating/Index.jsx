import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';

export default function RecoveryRatingIndex({ tiers }) {
    const form = useForm({
        tiers: tiers.map((tier) => ({ id: tier.id, min_percentage: tier.min_percentage })),
    });

    const setMinPercentage = (id, value) => {
        form.setData(
            'tiers',
            form.data.tiers.map((tier) => (tier.id === id ? { ...tier, min_percentage: value } : tier)),
        );
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        form.put('/settings/recovery-rating', { preserveScroll: true });
    };

    return (
        <DashboardLayout title="Settings • Resource Recovery Rating">
            <Head title="Resource Recovery Rating" />

            <div className="mb-6">
                <div className="text-sm text-gray-500 mb-1">
                    <Link href="/settings" className="hover:text-primary-600">Settings</Link>
                    <span className="mx-1">/</span>
                    <span>Resource Recovery Rating</span>
                </div>
                <h1 className="text-2xl font-semibold text-gray-900">Resource Recovery Rating</h1>
                <p className="text-sm text-gray-600">
                    Set the minimum diversion rate (%) a client must reach to earn each rating tier. Tiers are evaluated
                    from the top down, so each minimum must be lower than the tier above it.
                </p>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead className="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tier</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Minimum Diversion Rate</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                            {tiers.map((tier) => {
                                const current = form.data.tiers.find((t) => t.id === tier.id);
                                return (
                                    <tr key={tier.id}>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span
                                                className="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium text-white"
                                                style={{ backgroundColor: tier.color }}
                                            >
                                                {tier.name}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center gap-2">
                                                <input
                                                    type="number"
                                                    min="0"
                                                    max="100"
                                                    step="0.01"
                                                    value={current?.min_percentage ?? ''}
                                                    onChange={(event) => setMinPercentage(tier.id, event.target.value)}
                                                    className="w-24 rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                                                />
                                                <span className="text-sm text-gray-500">%</span>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                <InputError message={form.errors.tiers} className="mt-3" />

                <div className="mt-6 flex items-center gap-3">
                    <PrimaryButton type="submit" disabled={form.processing}>
                        {form.processing ? 'Saving...' : 'Save Changes'}
                    </PrimaryButton>
                    {form.recentlySuccessful && <span className="text-sm text-green-600">Saved.</span>}
                </div>
            </form>
        </DashboardLayout>
    );
}
