import { useState } from 'react';
import { ChevronDown, ChevronRight } from 'lucide-react';

const PERMISSION_SECTIONS = [
    {
        id: 'dashboard',
        title: 'Dashboard',
        permissionNames: ['view-dashboard'],
    },
    {
        id: 'orders',
        title: 'Orders',
        permissionNames: [
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
        id: 'recurring-orders',
        title: 'Recurring Orders',
        permissionNames: ['manage-recurring-orders'],
    },
    {
        id: 'reports',
        title: 'Reports',
        permissionNames: [
            'view-reports',
            'view-reports-all',
            'view-carbon-calculator',
            'view-water-calculator',
            'view-landfill-space-calculator',
        ],
    },
    {
        id: 'activity-log',
        title: 'Activity Log',
        permissionNames: ['view-activity-log'],
    },
    {
        id: 'companies',
        title: 'Companies',
        permissionNames: ['manage-clients'],
    },
    {
        id: 'users',
        title: 'Users',
        permissionNames: ['manage-users'],
    },
    {
        id: 'roles',
        title: 'Roles',
        permissionNames: ['manage-roles'],
    },
    {
        id: 'service-providers',
        title: 'Service Providers',
        permissionNames: ['manage-services'],
    },
    {
        id: 'materials',
        title: 'Materials',
        permissionNames: ['manage-services'],
    },
    {
        id: 'settings',
        title: 'Settings',
        permissionNames: ['manage-settings'],
    },
    {
        id: 'documents',
        title: 'Documents',
        permissionNames: ['manage-documents'],
    },
];

function formatLabel(name) {
    return name
        .split('-')
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
        .join(' ');
}

function groupPermissionsBySection(permissions) {
    const nameToPerm = (permissions ?? []).reduce((acc, p) => {
        acc[p.name] = p;
        return acc;
    }, {});

    const allAssignedNames = new Set(
        PERMISSION_SECTIONS.flatMap((s) => s.permissionNames)
    );

    const sections = PERMISSION_SECTIONS.map((section) => ({
        ...section,
        permissions: section.permissionNames
            .map((name) => nameToPerm[name])
            .filter(Boolean),
    }));

    const otherPerms = (permissions ?? []).filter(
        (p) => !allAssignedNames.has(p.name)
    );
    if (otherPerms.length > 0) {
        sections.push({
            id: 'other',
            title: 'Other',
            permissionNames: otherPerms.map((p) => p.name),
            permissions: otherPerms,
        });
    }

    return sections.filter((section) => section.permissions.length > 0);
}

export default function PermissionsBySection({
    permissions,
    selectedPermissions,
    onTogglePermission,
    onSelectAll,
    onClearAll,
    errors,
}) {
    const [openSections, setOpenSections] = useState(() =>
        PERMISSION_SECTIONS.reduce((acc, s) => {
            acc[s.id] = true;
            return acc;
        }, {})
    );

    const sections = groupPermissionsBySection(permissions);

    const toggleSection = (id) => {
        setOpenSections((prev) => ({ ...prev, [id]: !prev[id] }));
    };

    const selectAllInSection = (sectionPermissionNames) => {
        const current = new Set(selectedPermissions);
        sectionPermissionNames.forEach((name) => current.add(name));
        onSelectAll(Array.from(current));
    };

    const clearAllInSection = (sectionPermissionNames) => {
        const namesSet = new Set(sectionPermissionNames);
        onClearAll(selectedPermissions.filter((name) => !namesSet.has(name)));
    };

    const handleGlobalSelectAll = () => {
        onSelectAll(permissions?.map((p) => p.name) ?? []);
    };

    const handleGlobalClear = () => {
        onClearAll([]);
    };

    return (
        <div>
            <div className="flex items-center justify-between mb-2">
                <span className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                    Permissions
                </span>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={handleGlobalSelectAll}
                        className="text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400"
                    >
                        Select all
                    </button>
                    <button
                        type="button"
                        onClick={handleGlobalClear}
                        className="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <div className="border border-gray-200 dark:border-gray-600 rounded-md overflow-hidden bg-gray-50 dark:bg-gray-900/50">
                {sections.map((section) => {
                    const isOpen = openSections[section.id] ?? true;
                    const sectionSelected = section.permissions.filter((p) =>
                        selectedPermissions.includes(p.name)
                    ).length;
                    const sectionTotal = section.permissions.length;

                    return (
                        <div
                            key={section.id}
                            className="border-b border-gray-200 dark:border-gray-600 last:border-b-0"
                        >
                            <button
                                type="button"
                                onClick={() => toggleSection(section.id)}
                                className="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800/70 transition-colors"
                            >
                                <span className="flex items-center gap-2">
                                    {isOpen ? (
                                        <ChevronDown className="h-4 w-4 text-gray-500 dark:text-gray-400 shrink-0" />
                                    ) : (
                                        <ChevronRight className="h-4 w-4 text-gray-500 dark:text-gray-400 shrink-0" />
                                    )}
                                    {section.title}
                                </span>
                                <span className="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                    {sectionSelected}/{sectionTotal} selected
                                </span>
                            </button>

                            {isOpen && (
                                <div className="px-4 pb-3 pt-0">
                                    <div className="flex gap-2 mb-2">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                selectAllInSection(
                                                    section.permissions.map((p) => p.name)
                                                )
                                            }
                                            className="text-xs text-primary-600 hover:text-primary-800 dark:text-primary-400"
                                        >
                                            Select all in section
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                clearAllInSection(
                                                    section.permissions.map((p) => p.name)
                                                )
                                            }
                                            className="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400"
                                        >
                                            Clear section
                                        </button>
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 pl-6">
                                        {section.permissions.map((perm) => (
                                            <label
                                                key={perm.id}
                                                className="inline-flex items-center cursor-pointer"
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={selectedPermissions.includes(
                                                        perm.name
                                                    )}
                                                    onChange={() =>
                                                        onTogglePermission(perm.name)
                                                    }
                                                    className="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                                />
                                                <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                    {formatLabel(perm.name)}
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {errors?.permissions && (
                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.permissions}</p>
            )}
        </div>
    );
}
