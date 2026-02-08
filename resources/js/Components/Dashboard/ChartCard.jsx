export default function ChartCard({ title, children, className = '' }) {
    return (
        <div className={`bg-white dark:bg-gray-800 shadow rounded-lg ${className}`}>
            <div className="px-4 py-5 sm:p-6">
                <h3 className="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
                    {title}
                </h3>
                <div className="h-64">
                    {children}
                </div>
            </div>
        </div>
    );
}
