import { useState, useEffect, useRef } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { Dialog } from '@headlessui/react';
import CommandPalette from '@/Components/CommandPalette';
import ClientHubAdvertModal from '@/Components/ClientHubAdvertModal';
import {
    LayoutDashboard,
    Users,
    Package,
    RefreshCw,
    Settings,
    BarChart3,
    FileText,
    Menu,
    X,
    Bell,
    Search,
    User,
    LogOut,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    FolderOpen,
    Truck,
    ShoppingBag,
    Sun,
    Moon,
    UserCog,
    Shield,
    ShieldCheck,
    History,
    Megaphone,
} from 'lucide-react';

export default function DashboardLayout({ children }) {
    const { url, props } = usePage();
    const version = props.app?.version ?? '';
    const user = usePage().props.auth.user;
    const impersonating = usePage().props.impersonating ?? false;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(() => {
        try {
            return localStorage.getItem('sidebarCollapsed') === 'true';
        } catch {
            return false;
        }
    });
    const [searchOpen, setSearchOpen] = useState(false);
    const [profileDropdownOpen, setProfileDropdownOpen] = useState(false);
    const [notificationsOpen, setNotificationsOpen] = useState(false);
    const [selectedNote, setSelectedNote] = useState(null);
    const [isDark, setIsDark] = useState(false);
    const profileDropdownRef = useRef(null);
    const notificationsRef = useRef(null);
    const bellNotifications = usePage().props.bellNotifications ?? [];
    const hasUnseenDocuments = usePage().props.hasUnseenDocuments ?? false;
    const hasUnseenSheqCompliance = usePage().props.hasUnseenSheqCompliance ?? false;
    const clientHubPopupAdvert = usePage().props.clientHubPopupAdvert ?? null;
    const [showClientHubPopup, setShowClientHubPopup] = useState(false);

    // Auto-popup an undismissed Client Hub advert once per browser tab session (server-side
    // dismissed_at is what actually stops it long-term; this just avoids re-popping on every
    // in-app navigation before the client has clicked close). The 900ms delay before marking
    // it "shown" is deliberate: Dashboard.jsx restores a saved company/branch/site selection on
    // mount via router.get(..., { preserveState: false }), which remounts this whole layout
    // almost immediately after the first load. Marking "shown" synchronously on that first,
    // about-to-be-discarded mount would burn the one-time flag before the client ever actually
    // saw the popup. Waiting lets that remount settle first.
    useEffect(() => {
        if (!clientHubPopupAdvert) {
            return undefined;
        }
        const sessionKey = `client_hub_popup_shown_${clientHubPopupAdvert.id}`;
        try {
            if (sessionStorage.getItem(sessionKey)) {
                return undefined;
            }
        } catch {}
        const timer = setTimeout(() => {
            try {
                sessionStorage.setItem(sessionKey, 'true');
            } catch {}
            setShowClientHubPopup(true);
        }, 900);
        return () => clearTimeout(timer);
    }, [clientHubPopupAdvert?.id]);

    const dismissClientHubPopup = () => {
        if (clientHubPopupAdvert) {
            router.post(`/client-hub/${clientHubPopupAdvert.id}/dismiss`, {}, { preserveScroll: true, preserveState: true });
        }
        setShowClientHubPopup(false);
    };

    // Open command palette with ⌘K / Ctrl+K
    useEffect(() => {
        function handleKeydown(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setSearchOpen((v) => !v);
            }
        }
        document.addEventListener('keydown', handleKeydown);
        return () => document.removeEventListener('keydown', handleKeydown);
    }, []);

    // Close dropdowns when clicking outside
    useEffect(() => {
        function handleClickOutside(event) {
            if (profileDropdownRef.current && !profileDropdownRef.current.contains(event.target)) {
                setProfileDropdownOpen(false);
            }
            if (notificationsRef.current && !notificationsRef.current.contains(event.target)) {
                setNotificationsOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    // Initialize theme from localStorage or system preference
    useEffect(() => {
        const stored = localStorage.getItem('theme');
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const shouldUseDark = stored ? stored === 'dark' : prefersDark;
        setIsDark(shouldUseDark);
        document.documentElement.classList.toggle('dark', shouldUseDark);
    }, []);

    const toggleTheme = () => {
        const next = !isDark;
        setIsDark(next);
        document.documentElement.classList.toggle('dark', next);
        localStorage.setItem('theme', next ? 'dark' : 'light');
    };

    // Persist sidebar collapsed state so it's remembered across reloads and sessions
    useEffect(() => {
        try {
            localStorage.setItem('sidebarCollapsed', sidebarCollapsed ? 'true' : 'false');
        } catch {}
    }, [sidebarCollapsed]);

    const permissions = user?.permissions ?? [];
    const hasAny = (perms) => perms.some((p) => permissions.includes(p));

    const allNavItems = [
        { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, permissions: ['view-dashboard'] },
        {
            name: 'Orders',
            href: '/orders',
            icon: Package,
            permissions: [
                'manage-waste-collections',
                'orders-view',
                'orders-create',
                'orders-schedule',
                'orders-generate-consolidated',
                'orders-status-documents-required',
                'orders-status-weight-required',
                'orders-capture-documents',
                'orders-capture-weights',
                'orders-finalize',
            ],
        },
        {
            name: 'Recurring Orders',
            href: '/recurring-orders',
            icon: RefreshCw,
            permissions: ['manage-recurring-orders'],
        },
        { name: 'Reports', href: '/reports', icon: FileText, permissions: ['view-reports'] },
        { name: 'Activity Log', href: '/activity-log', icon: History, permissions: ['view-activity-log'] },
        { name: 'Companies', href: '/companies', icon: Users, permissions: ['manage-clients'] },
        { name: 'Users', href: '/users', icon: UserCog, permissions: ['manage-users'] },
        { name: 'Roles', href: '/roles', icon: Shield, permissions: ['manage-roles'] },
        { name: 'Service Providers', href: '/service-providers', icon: Truck, permissions: ['manage-services'] },
        { name: 'Materials', href: '/materials', icon: ShoppingBag, permissions: ['manage-services'] },
        { name: 'Settings', href: '/settings', icon: Settings, permissions: ['manage-settings'] },
    ];

    const navigation = allNavItems.filter((item) => hasAny(item.permissions));

    return (
        <>
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
            {/* Mobile sidebar */}
            <div className={`fixed inset-0 z-50 lg:hidden print:hidden ${sidebarOpen ? 'block' : 'hidden'}`}>
                <div className="fixed inset-0 bg-gray-900/70" onClick={() => setSidebarOpen(false)} />
                <div className="fixed inset-y-0 left-0 flex w-64 flex-col bg-white dark:bg-gray-800 shadow-xl">
                    <div className="flex h-16 items-center justify-between px-4">
                        <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">WasteFlow</h1>
                        <button
                            onClick={() => setSidebarOpen(false)}
                            className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <X className="h-6 w-6" />
                        </button>
                    </div>
                    <nav className="flex-1 px-4 py-4 overflow-y-auto">
                        <ul className="space-y-2">
                            {navigation.map((item) => {
                                const isCurrent = url.startsWith(item.href);
                                return (
                                    <li key={item.name}>
                                        <Link
                                            href={item.href}
                                            className={`group flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                                isCurrent
                                                    ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                                                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                                            }`}
                                        >
                                            <item.icon
                                                className={`mr-3 h-5 w-5 ${
                                                    isCurrent ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-300'
                                                }`}
                                            />
                                            {item.name}
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </nav>
                    <div className="shrink-0 border-t border-gray-200 dark:border-gray-700 p-2">
                        <Link
                            href="/documents"
                            className={`group relative flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                url.startsWith('/documents')
                                    ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                            }`}
                        >
                            <FolderOpen
                                className={`mr-3 h-5 w-5 ${
                                    url.startsWith('/documents') ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-300'
                                }`}
                            />
                            Documents
                            {hasUnseenDocuments && (
                                <span className="ml-auto h-2 w-2 rounded-full bg-primary-500" />
                            )}
                        </Link>
                        <Link
                            href="/sheq-compliance"
                            className={`group relative flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                url.startsWith('/sheq-compliance')
                                    ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                            }`}
                        >
                            <ShieldCheck
                                className={`mr-3 h-5 w-5 ${
                                    url.startsWith('/sheq-compliance') ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-300'
                                }`}
                            />
                            SHEQ Compliance
                            {hasUnseenSheqCompliance && (
                                <span className="ml-auto h-2 w-2 rounded-full bg-primary-500" />
                            )}
                        </Link>
                        {user?.is_admin && (
                            <Link
                                href="/client-hub"
                                className={`group flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                    url.startsWith('/client-hub')
                                        ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                                        : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                                }`}
                            >
                                <Megaphone
                                    className={`mr-3 h-5 w-5 ${
                                        url.startsWith('/client-hub') ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-300'
                                    }`}
                                />
                                Client Hub
                            </Link>
                        )}
                    </div>
                </div>
            </div>

            {/* Desktop sidebar */}
            <div
                className={`hidden lg:fixed lg:inset-y-0 lg:flex lg:flex-col transition-[width] duration-200 ease-in-out print:hidden ${
                    sidebarCollapsed ? 'lg:w-20' : 'lg:w-64'
                }`}
            >
                <div className="flex flex-col flex-grow bg-white dark:bg-gray-800 shadow-lg">
                    <div className="flex h-16 items-center px-4 shrink-0">
                        {!sidebarCollapsed && (
                            <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100 truncate">
                                WasteFlow
                                {version && (
                                    <Link
                                        href={route('release-notes.index')}
                                        title="View release notes"
                                        className="ml-2 text-xs font-normal text-gray-400 hover:text-primary-600 hover:underline dark:text-gray-500 dark:hover:text-primary-400"
                                    >
                                        {version}
                                    </Link>
                                )}
                            </h1>
                        )}
                        {sidebarCollapsed && (
                            <h1 className="text-lg font-bold text-gray-900 dark:text-gray-100 mx-auto" title="WasteFlow">WC</h1>
                        )}
                    </div>
                    <nav className="flex-1 px-3 py-4 overflow-hidden">
                        <ul className="space-y-2">
                            {navigation.map((item) => {
                                const isCurrent = url.startsWith(item.href);
                                return (
                                    <li key={item.name}>
                                        <Link
                                            href={item.href}
                                            title={sidebarCollapsed ? item.name : undefined}
                                            className={`group flex items-center rounded-lg text-sm font-medium transition-colors ${
                                                sidebarCollapsed ? 'justify-center px-2 py-2' : 'px-3 py-2'
                                            } ${
                                                isCurrent
                                                    ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                                                    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                                            }`}
                                        >
                                            <item.icon
                                                className={`h-5 w-5 shrink-0 ${
                                                    sidebarCollapsed ? '' : 'mr-3'
                                                } ${
                                                    isCurrent ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-300'
                                                }`}
                                            />
                                            {!sidebarCollapsed && <span className="truncate">{item.name}</span>}
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </nav>
                    <div className="shrink-0">
                        <div className="border-t border-gray-200 dark:border-gray-700 p-2">
                            <Link
                                href="/documents"
                                title={sidebarCollapsed ? 'Documents' : undefined}
                                className={`group relative flex items-center rounded-lg text-sm font-medium transition-colors ${
                                    sidebarCollapsed ? 'justify-center px-2 py-2' : 'px-3 py-2'
                                } ${
                                    url.startsWith('/documents')
                                        ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                                        : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                                }`}
                            >
                                <FolderOpen
                                    className={`h-5 w-5 shrink-0 ${sidebarCollapsed ? '' : 'mr-3'} ${
                                        url.startsWith('/documents') ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-300'
                                    }`}
                                />
                                {!sidebarCollapsed && <span className="truncate">Documents</span>}
                                {hasUnseenDocuments && (
                                    <span
                                        className={`absolute h-2 w-2 rounded-full bg-primary-500 ${
                                            sidebarCollapsed ? 'top-1.5 right-1.5' : 'right-3 top-1/2 -translate-y-1/2'
                                        }`}
                                    />
                                )}
                            </Link>
                        </div>
                        <div className="border-t border-gray-200 dark:border-gray-700 p-2">
                            <Link
                                href="/sheq-compliance"
                                title={sidebarCollapsed ? 'SHEQ Compliance' : undefined}
                                className={`group relative flex items-center rounded-lg text-sm font-medium transition-colors ${
                                    sidebarCollapsed ? 'justify-center px-2 py-2' : 'px-3 py-2'
                                } ${
                                    url.startsWith('/sheq-compliance')
                                        ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                                        : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                                }`}
                            >
                                <ShieldCheck
                                    className={`h-5 w-5 shrink-0 ${sidebarCollapsed ? '' : 'mr-3'} ${
                                        url.startsWith('/sheq-compliance') ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-300'
                                    }`}
                                />
                                {!sidebarCollapsed && <span className="truncate">SHEQ Compliance</span>}
                                {hasUnseenSheqCompliance && (
                                    <span
                                        className={`absolute h-2 w-2 rounded-full bg-primary-500 ${
                                            sidebarCollapsed ? 'top-1.5 right-1.5' : 'right-3 top-1/2 -translate-y-1/2'
                                        }`}
                                    />
                                )}
                            </Link>
                        </div>
                        {user?.is_admin && (
                            <div className="border-t border-gray-200 dark:border-gray-700 p-2">
                                <Link
                                    href="/client-hub"
                                    title={sidebarCollapsed ? 'Client Hub' : undefined}
                                    className={`group flex items-center rounded-lg text-sm font-medium transition-colors ${
                                        sidebarCollapsed ? 'justify-center px-2 py-2' : 'px-3 py-2'
                                    } ${
                                        url.startsWith('/client-hub')
                                            ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
                                            : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                                    }`}
                                >
                                    <Megaphone
                                        className={`h-5 w-5 shrink-0 ${sidebarCollapsed ? '' : 'mr-3'} ${
                                            url.startsWith('/client-hub') ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500 group-hover:text-gray-500 dark:group-hover:text-gray-300'
                                        }`}
                                    />
                                    {!sidebarCollapsed && <span className="truncate">Client Hub</span>}
                                </Link>
                            </div>
                        )}
                        <div className="border-t border-gray-200 dark:border-gray-700 p-2">
                            <button
                                type="button"
                                onClick={() => setSidebarCollapsed((c) => !c)}
                                className="flex w-full items-center justify-center rounded-lg px-2 py-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                title={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                                aria-label={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                            >
                                {sidebarCollapsed ? (
                                    <ChevronRight className="h-5 w-5" />
                                ) : (
                                    <>
                                        <ChevronLeft className="h-5 w-5 mr-2" />
                                        <span className="text-sm">Collapse</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Main content */}
            <div className={`transition-[padding] duration-200 print:pl-0 ${sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'}`}>
                {/* Top navigation */}
                <div className="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8 dark:border-gray-700 dark:bg-gray-800 print:hidden">
                    <button
                        type="button"
                        className="-m-2.5 p-2.5 text-gray-700 lg:hidden dark:text-gray-300"
                        onClick={() => setSidebarOpen(true)}
                    >
                        <Menu className="h-6 w-6" />
                    </button>

                    <div className="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                        <div className="relative flex flex-1 items-center">
                            <button
                                type="button"
                                onClick={() => setSearchOpen(true)}
                                className="flex w-full items-center gap-2 rounded-md px-3 py-1.5 text-sm text-gray-400 ring-1 ring-inset ring-gray-300 hover:ring-gray-400 dark:ring-gray-600 dark:hover:ring-gray-500 dark:text-gray-500 transition-colors"
                            >
                                <Search className="h-4 w-4 flex-shrink-0" />
                                <span className="flex-1 text-left">Search…</span>
                                <kbd className="hidden sm:inline-flex items-center gap-0.5 text-[11px] font-mono text-gray-400 dark:text-gray-500">
                                    <span className="text-xs">⌘</span>K
                                </kbd>
                            </button>
                        </div>
                        <div className="flex items-center gap-x-4 lg:gap-x-6">
                            <button
                                type="button"
                                onClick={toggleTheme}
                                className="-m-2.5 p-2.5 flex items-center text-gray-400 hover:text-gray-500 dark:text-gray-300 dark:hover:text-gray-100"
                                aria-label="Toggle theme"
                                title="Toggle theme"
                            >
                                {isDark ? <Sun className="h-6 w-6" /> : <Moon className="h-6 w-6" />}
                            </button>
                            {/* Notifications */}
                            <div className="relative" ref={notificationsRef}>
                                <button
                                    type="button"
                                    onClick={() => setNotificationsOpen(!notificationsOpen)}
                                    className="-m-2.5 p-2.5 flex items-center text-gray-400 hover:text-gray-500 dark:text-gray-300 dark:hover:text-gray-100 relative"
                                    aria-label="View notifications"
                                >
                                    <Bell className="h-6 w-6" />
                                    {bellNotifications.length > 0 && (
                                        <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-primary-500/80 text-[10px] font-bold text-white leading-none">
                                            {bellNotifications.length > 9 ? '9+' : bellNotifications.length}
                                        </span>
                                    )}
                                </button>

                                {notificationsOpen && (
                                    <div className="absolute right-0 z-20 mt-2 w-80 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-gray-700">
                                        <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                            <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">Notifications</h3>
                                            {bellNotifications.length > 0 && (
                                                <button
                                                    onClick={() => {
                                                        router.post('/release-notes/read-all');
                                                        setNotificationsOpen(false);
                                                    }}
                                                    className="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                                                >
                                                    Mark all as read
                                                </button>
                                            )}
                                        </div>
                                        {bellNotifications.length === 0 ? (
                                            <p className="px-4 py-6 text-sm text-center text-gray-500 dark:text-gray-400">You're all caught up!</p>
                                        ) : (
                                            <div className="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                                                {bellNotifications.map((note) => (
                                                    <div
                                                        key={note.id}
                                                        className="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-750 cursor-pointer"
                                                        onClick={() => { setNotificationsOpen(false); setSelectedNote(note); }}
                                                    >
                                                        <div className="flex-1 min-w-0">
                                                            <div className="flex items-center gap-2 mb-1">
                                                                <span className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${
                                                                    note.badge_type === 'feature' || note.badge_type === 'success'
                                                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                                                        : note.badge_type === 'bugfix' || note.badge_type === 'error'
                                                                        ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400'
                                                                        : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400'
                                                                }`}>
                                                                    {note.badge_label}
                                                                </span>
                                                                {note.kind === 'release_note' && note.version && (
                                                                    <span className="text-[11px] text-gray-400 dark:text-gray-500">v{note.version}</span>
                                                                )}
                                                            </div>
                                                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">{note.title}</p>
                                                            {note.description && (
                                                                <p className="mt-0.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{note.description}</p>
                                                            )}
                                                        </div>
                                                        <button
                                                            type="button"
                                                            onClick={(e) => { e.stopPropagation(); router.post(note.read_url); }}
                                                            className="flex-shrink-0 mt-0.5 text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-300"
                                                            title="Dismiss"
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>

                            {/* Profile dropdown */}
                            <div className="relative" ref={profileDropdownRef}>
                                <button
                                    type="button"
                                    onClick={() => setProfileDropdownOpen(!profileDropdownOpen)}
                                    className="-m-1.5 flex items-center p-1.5"
                                >
                                    <span className="sr-only">Open user menu</span>
                                    {user?.avatar ? (
                                        <img
                                            src={user.avatar}
                                            alt={user.name}
                                            className="h-8 w-8 rounded-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-white text-sm font-medium">
                                            {user?.name?.charAt(0).toUpperCase()}
                                        </div>
                                    )}
                                    <span className="hidden lg:flex lg:items-center">
                                        <span className="ml-4 text-sm font-semibold leading-6 text-gray-900 dark:text-gray-100" aria-hidden="true">
                                            {user?.name}
                                        </span>
                                        <ChevronDown className={`ml-2 h-5 w-5 text-gray-400 dark:text-gray-500 transition-transform ${profileDropdownOpen ? 'rotate-180' : ''}`} />
                                    </span>
                                </button>

                                {/* Dropdown menu */}
                                {profileDropdownOpen && (
                                    <div className="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800 dark:ring-gray-700">
                                        <Link
                                            href="/profile"
                                            className="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                            onClick={() => setProfileDropdownOpen(false)}
                                        >
                                            <User className="mr-3 h-4 w-4" />
                                            Your Profile
                                        </Link>
                                        <hr className="my-1 border-gray-200 dark:border-gray-700" />
                                        <button
                                            onClick={() => {
                                                setProfileDropdownOpen(false);
                                                router.post('/logout');
                                            }}
                                            className="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                        >
                                            <LogOut className="mr-3 h-4 w-4" />
                                            Sign out
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Impersonation banner */}
                {impersonating && (
                    <div className="bg-amber-400 text-amber-900 px-4 py-2 flex items-center justify-between text-sm font-medium print:hidden">
                        <span>You are impersonating <strong>{user?.name}</strong> ({user?.email}). Actions taken here are real.</span>
                        <button
                            onClick={() => router.post('/users/impersonate/leave')}
                            className="ml-4 px-3 py-1 rounded bg-amber-900 text-amber-50 hover:bg-amber-800 text-xs font-semibold"
                        >
                            Stop impersonating
                        </button>
                    </div>
                )}

                {/* Main content area */}
                <main className="py-2">
                    <div className="mx-auto px-4 sm:px-6 lg:px-8">
                        {children}
                    </div>
                </main>
            </div>
        </div>

        {/* Client Hub advert detail (opened from the bell) - marks read on close */}
        {selectedNote?.kind === 'client_hub_advert' && (
            <ClientHubAdvertModal
                show
                advert={selectedNote}
                closeLabel="Mark as read"
                onClose={() => {
                    router.post(selectedNote.read_url);
                    setSelectedNote(null);
                }}
            />
        )}

        {/* Client Hub advert auto-popup on login */}
        <ClientHubAdvertModal
            show={showClientHubPopup}
            advert={clientHubPopupAdvert}
            onClose={dismissClientHubPopup}
        />

        {/* Notification detail modal */}
        <Dialog open={!!selectedNote && selectedNote.kind !== 'client_hub_advert'} onClose={() => setSelectedNote(null)} className="relative z-50">
            <div className="fixed inset-0 bg-black/40 backdrop-blur-sm" aria-hidden="true" />
            <div className="fixed inset-0 flex items-center justify-center p-4">
                <Dialog.Panel className="w-full max-w-md rounded-xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-black/10 dark:ring-white/10">
                    {selectedNote && (
                        <>
                            <div className="px-6 pt-6 pb-4">
                                <div className="flex items-center gap-2 mb-3">
                                    <span className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${
                                        selectedNote.badge_type === 'feature' || selectedNote.badge_type === 'success'
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                            : selectedNote.badge_type === 'bugfix' || selectedNote.badge_type === 'error'
                                            ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400'
                                            : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400'
                                    }`}>
                                        {selectedNote.badge_label}
                                    </span>
                                    {selectedNote.kind === 'release_note' && selectedNote.version && (
                                        <span className="text-xs text-gray-400 dark:text-gray-500">v{selectedNote.version}</span>
                                    )}
                                </div>
                                <Dialog.Title className="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                    {selectedNote.title}
                                </Dialog.Title>
                                {selectedNote.description && (
                                    <p className="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                        {selectedNote.description}
                                    </p>
                                )}
                            </div>
                            <div className="flex justify-end gap-2 px-6 py-4 border-t border-gray-100 dark:border-gray-800">
                                <button
                                    type="button"
                                    onClick={() => setSelectedNote(null)}
                                    className="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                                >
                                    Close
                                </button>
                                <button
                                    type="button"
                                    onClick={() => { router.post(selectedNote.read_url); setSelectedNote(null); }}
                                    className="px-3 py-1.5 text-sm rounded-md bg-primary-600 text-white hover:bg-primary-700"
                                >
                                    Mark as read
                                </button>
                            </div>
                        </>
                    )}
                </Dialog.Panel>
            </div>
        </Dialog>

        <CommandPalette open={searchOpen} onClose={() => setSearchOpen(false)} />
        </>
    );
}
