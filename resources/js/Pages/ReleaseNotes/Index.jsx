import { Head } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Rocket, Wrench, Sparkles } from 'lucide-react';

const TYPE_META = {
    feature: {
        label: 'Feature',
        icon: Sparkles,
        badge: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
    },
    bugfix: {
        label: 'Bug fix',
        icon: Wrench,
        badge: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
    },
    improvement: {
        label: 'Improvement',
        icon: Rocket,
        badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
    },
};

function typeMeta(type) {
    return TYPE_META[type] ?? TYPE_META.improvement;
}

function formatReleaseDate(value) {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleDateString('en-ZA', { day: 'numeric', month: 'long', year: 'numeric' });
}

export default function ReleaseNotesIndex({ versions, currentVersion }) {
    return (
        <DashboardLayout title="Release Notes">
            <Head title="Release Notes" />

            <div className="max-w-3xl mx-auto space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Release Notes</h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Everything that has shipped to the WasteFlow Portal, newest first.
                        {currentVersion && <> Currently running <span className="font-semibold">v{currentVersion}</span>.</>}
                    </p>
                </div>

                {(!versions || versions.length === 0) && (
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center text-gray-500">
                        No release notes yet.
                    </div>
                )}

                <div className="space-y-8">
                    {versions?.map((group) => (
                        <div key={group.version} className="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div className="flex items-baseline justify-between gap-3 px-5 py-3 bg-gray-50 dark:bg-gray-750 border-b border-gray-100 dark:border-gray-700">
                                <h2 className="text-sm font-bold text-gray-900 dark:text-gray-100">
                                    v{group.version}
                                </h2>
                                {formatReleaseDate(group.released_at) && (
                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                        {formatReleaseDate(group.released_at)}
                                    </span>
                                )}
                            </div>
                            <ul className="divide-y divide-gray-100 dark:divide-gray-700">
                                {group.notes.map((note) => {
                                    const meta = typeMeta(note.type);
                                    const Icon = meta.icon;
                                    return (
                                        <li key={note.id} className="flex items-start gap-3 px-5 py-4">
                                            <span className={`mt-0.5 inline-flex items-center justify-center h-7 w-7 rounded-full shrink-0 ${meta.badge}`}>
                                                <Icon size={14} />
                                            </span>
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <span className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${meta.badge}`}>
                                                        {meta.label}
                                                    </span>
                                                    <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">{note.title}</p>
                                                </div>
                                                {note.description && (
                                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                                        {note.description}
                                                    </p>
                                                )}
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    ))}
                </div>
            </div>
        </DashboardLayout>
    );
}
