import { useState, useEffect, useRef } from 'react';
import { Dialog } from '@headlessui/react';
import { router } from '@inertiajs/react';
import {
    Search,
    Package,
    Building2,
    Users,
    MapPin,
    Layers,
    Leaf,
    LayoutGrid,
    Truck,
    Loader2,
} from 'lucide-react';

const CATEGORIES = {
    orders: { label: 'Orders', icon: Package },
    companies: { label: 'Companies', icon: Building2 },
    users: { label: 'Users', icon: Users },
    branches: { label: 'Branches', icon: MapPin },
    sites: { label: 'Collection Points', icon: MapPin },
    service_providers: { label: 'Service Providers', icon: Truck },
    container_options: { label: 'Container Types', icon: Layers },
    waste_streams: { label: 'Waste Streams', icon: Leaf },
    grades: { label: 'Grades', icon: LayoutGrid },
};

export default function CommandPalette({ open, onClose }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState({});
    const [loading, setLoading] = useState(false);
    const [activeIndex, setActiveIndex] = useState(0);
    const inputRef = useRef(null);
    const debounceRef = useRef(null);

    const flatResults = Object.entries(results).flatMap(([cat, items]) =>
        items.map((item) => ({ ...item, _cat: cat }))
    );

    useEffect(() => {
        if (open) {
            setQuery('');
            setResults({});
            setActiveIndex(0);
            // Small delay so Dialog finishes mounting before focusing
            const t = setTimeout(() => inputRef.current?.focus(), 30);
            return () => clearTimeout(t);
        }
    }, [open]);

    useEffect(() => {
        clearTimeout(debounceRef.current);

        if (!query || query.length < 2) {
            setResults({});
            setLoading(false);
            return;
        }

        setLoading(true);

        debounceRef.current = setTimeout(async () => {
            try {
                const res = await fetch(`/search?q=${encodeURIComponent(query)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                setResults(data);
                setActiveIndex(0);
            } catch {
                setResults({});
            } finally {
                setLoading(false);
            }
        }, 300);

        return () => clearTimeout(debounceRef.current);
    }, [query]);

    const navigate = (url) => {
        onClose();
        router.visit(url);
    };

    const handleKeyDown = (e) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActiveIndex((i) => Math.min(i + 1, flatResults.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActiveIndex((i) => Math.max(i - 1, 0));
        } else if (e.key === 'Enter' && flatResults[activeIndex]) {
            navigate(flatResults[activeIndex].url);
        }
    };

    const hasResults = flatResults.length > 0;
    const showEmpty = query.length >= 2 && !loading && !hasResults;

    return (
        <Dialog open={open} onClose={onClose} className="relative z-50">
            {/* Backdrop */}
            <div className="fixed inset-0 bg-black/40 backdrop-blur-sm" aria-hidden="true" />

            <div className="fixed inset-0 flex items-start justify-center pt-[12vh] px-4 pb-4">
                <Dialog.Panel className="w-full max-w-2xl bg-white dark:bg-gray-900 rounded-xl shadow-2xl ring-1 ring-black/10 dark:ring-white/10 overflow-hidden">
                    {/* Input row */}
                    <div className="flex items-center gap-3 px-4 py-3.5 border-b border-gray-200 dark:border-gray-700">
                        {loading ? (
                            <Loader2 className="h-5 w-5 text-gray-400 animate-spin flex-shrink-0" />
                        ) : (
                            <Search className="h-5 w-5 text-gray-400 flex-shrink-0" />
                        )}
                        <input
                            ref={inputRef}
                            type="text"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            onKeyDown={handleKeyDown}
                            placeholder="Search orders, companies, users…"
                            className="flex-1 bg-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 text-base outline-none"
                        />
                        <kbd className="hidden sm:inline-flex h-6 items-center gap-0.5 rounded border border-gray-200 dark:border-gray-700 px-1.5 text-xs text-gray-400 font-mono">
                            esc
                        </kbd>
                    </div>

                    {/* Results */}
                    <div className="max-h-[60vh] overflow-y-auto overscroll-contain">
                        {!query || query.length < 2 ? (
                            <p className="px-4 py-10 text-sm text-center text-gray-400 dark:text-gray-500">
                                Type at least 2 characters to search…
                            </p>
                        ) : showEmpty ? (
                            <p className="px-4 py-10 text-sm text-center text-gray-400 dark:text-gray-500">
                                No results for{' '}
                                <span className="font-medium text-gray-600 dark:text-gray-300">"{query}"</span>
                            </p>
                        ) : (
                            <div className="py-2">
                                {Object.entries(results).map(([cat, items]) => {
                                    if (!items.length) return null;
                                    const config = CATEGORIES[cat] ?? { label: cat, icon: Search };
                                    const Icon = config.icon;

                                    return (
                                        <div key={cat}>
                                            <div className="px-4 pt-3 pb-1">
                                                <span className="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                                                    {config.label}
                                                </span>
                                            </div>
                                            {items.map((item) => {
                                                const flatIdx = flatResults.findIndex(
                                                    (r) => r._cat === cat && r.id === item.id
                                                );
                                                const isActive = flatIdx === activeIndex;

                                                return (
                                                    <button
                                                        key={item.id}
                                                        type="button"
                                                        onClick={() => navigate(item.url)}
                                                        onMouseEnter={() => setActiveIndex(flatIdx)}
                                                        className={`w-full flex items-center gap-3 px-4 py-2.5 text-left transition-colors ${
                                                            isActive
                                                                ? 'bg-primary-50 dark:bg-primary-900/20'
                                                                : 'hover:bg-gray-50 dark:hover:bg-gray-800/50'
                                                        }`}
                                                    >
                                                        <div
                                                            className={`flex-shrink-0 flex h-8 w-8 items-center justify-center rounded-lg ${
                                                                isActive
                                                                    ? 'bg-primary-100 dark:bg-primary-900/40'
                                                                    : 'bg-gray-100 dark:bg-gray-800'
                                                            }`}
                                                        >
                                                            <Icon
                                                                className={`h-4 w-4 ${
                                                                    isActive
                                                                        ? 'text-primary-600 dark:text-primary-400'
                                                                        : 'text-gray-500 dark:text-gray-400'
                                                                }`}
                                                            />
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                                {item.label}
                                                            </p>
                                                            {item.subtitle && (
                                                                <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                                    {item.subtitle}
                                                                </p>
                                                            )}
                                                        </div>
                                                        {isActive && (
                                                            <kbd className="hidden sm:block flex-shrink-0 text-[10px] text-gray-400 font-mono border border-gray-200 dark:border-gray-700 rounded px-1 py-0.5">
                                                                ↵
                                                            </kbd>
                                                        )}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    {/* Footer hint */}
                    {hasResults && (
                        <div className="flex items-center gap-4 border-t border-gray-200 dark:border-gray-700 px-4 py-2">
                            <span className="text-[11px] text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                <kbd className="font-mono border border-gray-200 dark:border-gray-700 rounded px-1">↑↓</kbd> navigate
                            </span>
                            <span className="text-[11px] text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                <kbd className="font-mono border border-gray-200 dark:border-gray-700 rounded px-1">↵</kbd> open
                            </span>
                            <span className="text-[11px] text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                <kbd className="font-mono border border-gray-200 dark:border-gray-700 rounded px-1">esc</kbd> close
                            </span>
                        </div>
                    )}
                </Dialog.Panel>
            </div>
        </Dialog>
    );
}
