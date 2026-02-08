import { Head } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import DataTable from '@/Components/Dashboard/DataTable';
import { useMemo } from 'react';
import { Plus, Edit, Trash2, Eye } from 'lucide-react';

export default function ClientsIndex() {
    const clients = useMemo(() => [
        { 
            id: 1, 
            name: 'Acme Corporation', 
            email: 'contact@acme.com', 
            phone: '+1 (555) 123-4567',
            status: 'Active', 
            joinDate: '2024-01-15',
            wasteType: 'Mixed',
            monthlyVolume: '2.5 tons'
        },
        { 
            id: 2, 
            name: 'Tech Solutions Inc', 
            email: 'info@techsol.com', 
            phone: '+1 (555) 234-5678',
            status: 'Active', 
            joinDate: '2024-01-20',
            wasteType: 'Electronic',
            monthlyVolume: '1.8 tons'
        },
        { 
            id: 3, 
            name: 'Green Industries', 
            email: 'hello@green.com', 
            phone: '+1 (555) 345-6789',
            status: 'Pending', 
            joinDate: '2024-01-25',
            wasteType: 'Organic',
            monthlyVolume: '3.2 tons'
        },
        { 
            id: 4, 
            name: 'Eco Systems Ltd', 
            email: 'contact@eco.com', 
            phone: '+1 (555) 456-7890',
            status: 'Active', 
            joinDate: '2024-02-01',
            wasteType: 'Recyclable',
            monthlyVolume: '4.1 tons'
        },
        { 
            id: 5, 
            name: 'Waste Management Co', 
            email: 'admin@waste.com', 
            phone: '+1 (555) 567-8901',
            status: 'Inactive', 
            joinDate: '2024-02-05',
            wasteType: 'Hazardous',
            monthlyVolume: '0.8 tons'
        },
    ], []);

    const columns = useMemo(() => [
        {
            accessorKey: 'name',
            header: 'Company Name',
        },
        {
            accessorKey: 'email',
            header: 'Email',
        },
        {
            accessorKey: 'phone',
            header: 'Phone',
        },
        {
            accessorKey: 'wasteType',
            header: 'Waste Type',
        },
        {
            accessorKey: 'monthlyVolume',
            header: 'Monthly Volume',
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ getValue }) => {
                const status = getValue();
                const statusColors = {
                    Active: 'bg-green-100 text-green-800',
                    Pending: 'bg-yellow-100 text-yellow-800',
                    Inactive: 'bg-red-100 text-red-800',
                };
                return (
                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${statusColors[status]}`}>
                        {status}
                    </span>
                );
            },
        },
        {
            accessorKey: 'joinDate',
            header: 'Join Date',
        },
        {
            id: 'actions',
            header: 'Actions',
            cell: ({ row }) => (
                <div className="flex space-x-2">
                    <button className="text-primary-600 hover:text-primary-800">
                        <Eye className="h-4 w-4" />
                    </button>
                    <button className="text-green-600 hover:text-green-800">
                        <Edit className="h-4 w-4" />
                    </button>
                    <button className="text-red-600 hover:text-red-800">
                        <Trash2 className="h-4 w-4" />
                    </button>
                </div>
            ),
        },
    ], []);

    return (
        <DashboardLayout title="Clients">
            <Head title="Clients" />

            <div className="mb-6 flex justify-between items-center">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900">Client Management</h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Manage your waste management clients and their information.
                    </p>
                </div>
                <button className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <Plus className="h-4 w-4 mr-2" />
                    Add Client
                </button>
            </div>

            <DataTable
                data={clients}
                columns={columns}
                title="All Clients"
            />
        </DashboardLayout>
    );
}