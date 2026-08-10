import Modal from '@/Components/Modal';
import { Mail, X } from 'lucide-react';

/**
 * Shared advert-content dialog used both for the auto-popup on login and for the
 * "view again" dialog opened from the notification bell. `advert` fields (title,
 * details/description, contact_email, mime_type, view_url/image_url) are shaped
 * slightly differently between the two callers - normalize at the call site.
 */
export default function ClientHubAdvertModal({ show, advert, onClose, closeLabel = 'Close' }) {
    if (!advert) {
        return null;
    }

    const isPdf = advert.mime_type === 'application/pdf';

    return (
        <Modal show={show} onClose={onClose} maxWidth="2xl">
            <div className="relative">
                <button
                    type="button"
                    onClick={onClose}
                    className="absolute right-3 top-3 z-10 rounded-full bg-white/90 p-1.5 text-gray-500 shadow hover:bg-white hover:text-gray-700 dark:bg-gray-900/80 dark:text-gray-300 dark:hover:text-gray-100"
                >
                    <X className="h-5 w-5" />
                </button>

                {isPdf ? (
                    <iframe
                        src={advert.view_url ?? advert.image_url}
                        title={advert.title}
                        className="h-[70vh] w-full border-0"
                    />
                ) : (
                    <img
                        src={advert.view_url ?? advert.image_url}
                        alt={advert.title}
                        className="max-h-[70vh] w-full object-contain bg-gray-100 dark:bg-gray-900"
                    />
                )}

                <div className="p-5">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">{advert.title}</h2>
                    {(advert.details || advert.description) && (
                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {advert.details ?? advert.description}
                        </p>
                    )}
                    {advert.contact_email && (
                        <p className="mt-3 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <Mail className="h-4 w-4 shrink-0" />
                            Contact us:{' '}
                            <a href={`mailto:${advert.contact_email}`} className="font-medium text-primary-600 hover:underline">
                                {advert.contact_email}
                            </a>
                        </p>
                    )}
                    <div className="mt-5 flex justify-end">
                        <button
                            type="button"
                            onClick={onClose}
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                        >
                            {closeLabel}
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    );
}
