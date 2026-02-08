import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Layers, SlidersHorizontal, PackageSearch, MapPin, Building2 } from 'lucide-react';

const cards = [
    {
        name: 'Waste Streams',
        description: 'Top-level waste stream categories used across materials and orders.',
        href: '/settings/waste-streams',
        icon: Layers,
    },
    {
        name: 'Grades',
        description: 'Maintain material grades such as K4, PET Clear, Heavy Steel.',
        href: '/settings/grades',
        icon: SlidersHorizontal,
    },
    {
        name: 'Container Options',
        description: 'Define container sizes and options (skips, RELs, cages, etc.).',
        href: '/settings/container-options',
        icon: PackageSearch,
    },
    {
        name: 'Classifications',
        description: 'Manage classification labels like Disposed, Recovered, Recycling.',
        href: '/settings/classifications',
        icon: Building2,
    },
    {
        name: 'Facilities',
        description: 'Keep facility destinations up to date for routing and reporting.',
        href: '/settings/facilities',
        icon: MapPin,
    },
];

export default function SettingsIndex() {
    return (
        <DashboardLayout title="Settings">
            <Head title="Settings" />

            <div className="mb-6">
                <h1 className="text-3xl font-semibold text-gray-900 dark:text-gray-100">Settings</h1>
                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Manage lookup values and configuration used throughout the WasteFlow portal.
                </p>
            </div>

            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                {cards.map((card) => (
                    <Link
                        key={card.name}
                        href={card.href}
                        className="group relative flex flex-col rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:border-primary-200 hover:shadow-md dark:bg-gray-800 dark:border-gray-700"
                    >
                        <div className="flex items-center justify-between">
                            <card.icon className="h-10 w-10 text-primary-500" />
                            <span className="inline-flex items-center rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-600">
                                Manage
                            </span>
                        </div>
                        <h2 className="mt-4 text-xl font-semibold text-gray-900 group-hover:text-primary-600 dark:text-gray-100">
                            {card.name}
                        </h2>
                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {card.description}
                        </p>
                    </Link>
                ))}
            </div>
        </DashboardLayout>
    );
}
