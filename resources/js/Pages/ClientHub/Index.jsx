import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import ClientHubAdvertModal from '@/Components/ClientHubAdvertModal';
import { Megaphone, FileText, Image as ImageIcon } from 'lucide-react';

export default function ClientHubIndex({ adverts }) {
    const [selected, setSelected] = useState(null);

    const openAdvert = (advert) => {
        setSelected(advert);
        if (!advert.read_at) {
            router.post(`/client-hub/${advert.id}/read`, {}, { preserveScroll: true, preserveState: true });
        }
    };

    return (
        <DashboardLayout title="Client Hub">
            <Head title="Client Hub" />

            <div className="mb-6">
                <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">Client Hub</h1>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                    Announcements and adverts from WasteFlow. Anything you've already seen stays here so you can find it again.
                </p>
            </div>

            {adverts.length === 0 ? (
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center text-gray-500 dark:text-gray-400">
                    <Megaphone className="h-8 w-8 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
                    Nothing here yet.
                </div>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {adverts.map((advert) => (
                        <button
                            key={advert.id}
                            type="button"
                            onClick={() => openAdvert(advert)}
                            className="text-left bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-shadow p-4 relative"
                        >
                            {!advert.read_at && (
                                <span className="absolute top-3 right-3 inline-flex rounded-full bg-primary-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary-700 dark:bg-primary-900/40 dark:text-primary-400">
                                    New
                                </span>
                            )}
                            <div className="flex items-center gap-2 text-gray-400 dark:text-gray-500 mb-2">
                                {advert.mime_type === 'application/pdf' ? (
                                    <FileText className="h-4 w-4" />
                                ) : (
                                    <ImageIcon className="h-4 w-4" />
                                )}
                                <span className="text-xs">{advert.created_at}</span>
                            </div>
                            <p className="font-medium text-gray-900 dark:text-gray-100 pr-12">{advert.title}</p>
                            {advert.details && (
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">{advert.details}</p>
                            )}
                        </button>
                    ))}
                </div>
            )}

            <ClientHubAdvertModal show={!!selected} advert={selected} onClose={() => setSelected(null)} />
        </DashboardLayout>
    );
}
