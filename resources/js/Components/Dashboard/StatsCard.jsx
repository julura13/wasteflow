
export default function StatsCard({ title, value, change, changeType, icon: Icon, color = 'blue' }) {
    const colorClasses = {
        blue: 'bg-primary-500',
        green: 'bg-green-500',
        yellow: 'bg-yellow-500',
        red: 'bg-red-500',
        purple: 'bg-purple-500',
    };

    const changeColorClasses = {
        positive: 'text-green-600',
        negative: 'text-red-600',
        neutral: 'text-gray-600',
    };

    return (
        <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="p-5">
                <div className="flex items-center">
                    <div className="flex-shrink-0">
                        <div className={`p-3 rounded-md ${colorClasses[color]}`}>
                            <Icon className="h-6 w-6 text-white" />
                        </div>
                    </div>
                    <div className="ml-5 w-0 flex-1">
                        <dl>
                            <dt className="text-sm font-medium text-gray-500 truncate">
                                {title}
                            </dt>
                            <dd className="flex items-baseline">
                                <div className="text-2xl font-semibold text-gray-900">
                                    {value}
                                </div>
                                {change && (
                                    <div className={`ml-2 flex items-baseline text-sm font-semibold ${changeColorClasses[changeType]}`}>
                                        {changeType === 'positive' && '+'}
                                        {change}
                                        {changeType === 'positive' || changeType === 'negative' ? '%' : ''}
                                    </div>
                                )}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    );
}
