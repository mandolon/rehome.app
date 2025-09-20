import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';

export default function Dashboard() {
    const { auth } = usePage().props;
    const userRole = auth?.user?.role || 'client';

    const getDashboardStats = () => {
        switch (userRole) {
            case 'admin':
                return [
                    { name: 'Total Projects', value: '12', icon: '🏗️' },
                    { name: 'Active Users', value: '8', icon: '👥' },
                    { name: 'Revenue', value: '$24,500', icon: '💰' },
                    { name: 'Documents', value: '156', icon: '📄' },
                ];
            case 'team':
                return [
                    { name: 'My Projects', value: '6', icon: '🏗️' },
                    { name: 'Pending Tasks', value: '4', icon: '✅' },
                    { name: 'Documents', value: '89', icon: '📄' },
                    { name: 'This Week', value: '32h', icon: '⏰' },
                ];
            case 'client':
                return [
                    { name: 'My Projects', value: '2', icon: '🏗️' },
                    { name: 'Pending Approvals', value: '3', icon: '✅' },
                    { name: 'Documents', value: '24', icon: '📄' },
                    { name: 'Progress', value: '68%', icon: '📊' },
                ];
            default:
                return [];
        }
    };

    const stats = getDashboardStats();

    return (
        <AppLayout>
            <Head title="Dashboard" />
            
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">
                            Welcome back, {auth?.user?.name}
                        </h1>
                        <p className="mt-1 text-sm text-gray-600">
                            Here's what's happening with your preconstruction projects.
                        </p>
                    </div>

                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                        {stats.map((stat) => (
                            <div
                                key={stat.name}
                                className="bg-white overflow-hidden shadow rounded-lg"
                            >
                                <div className="p-5">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0">
                                            <span className="text-2xl">{stat.icon}</span>
                                        </div>
                                        <div className="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt className="text-sm font-medium text-gray-500 truncate">
                                                    {stat.name}
                                                </dt>
                                                <dd className="text-lg font-medium text-gray-900">
                                                    {stat.value}
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Quick Actions */}
                    <div className="bg-white shadow rounded-lg">
                        <div className="px-4 py-5 sm:p-6">
                            <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Quick Actions
                            </h3>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {userRole === 'admin' && (
                                    <>
                                        <button className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                            Create Project
                                        </button>
                                        <button className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Invite User
                                        </button>
                                        <button className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            View Reports
                                        </button>
                                    </>
                                )}
                                {userRole === 'team' && (
                                    <>
                                        <button className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                            Upload Document
                                        </button>
                                        <button className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Create Task
                                        </button>
                                        <button className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            View Schedule
                                        </button>
                                    </>
                                )}
                                {userRole === 'client' && (
                                    <>
                                        <button className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                            View Progress
                                        </button>
                                        <button className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Request Update
                                        </button>
                                        <button className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Download Files
                                        </button>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Recent Activity */}
                    <div className="mt-8 bg-white shadow rounded-lg">
                        <div className="px-4 py-5 sm:p-6">
                            <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                Recent Activity
                            </h3>
                            <div className="flow-root">
                                <ul className="-mb-8">
                                    {[
                                        { action: 'Document uploaded', time: '2 hours ago', type: 'upload' },
                                        { action: 'Project status updated', time: '4 hours ago', type: 'update' },
                                        { action: 'New comment added', time: '1 day ago', type: 'comment' },
                                        { action: 'Approval requested', time: '2 days ago', type: 'approval' },
                                    ].map((item, index) => (
                                        <li key={index}>
                                            <div className="relative pb-8">
                                                {index !== 3 && (
                                                    <span
                                                        className="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200"
                                                        aria-hidden="true"
                                                    />
                                                )}
                                                <div className="relative flex items-start space-x-3">
                                                    <div className="relative">
                                                        <div className="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                            <span className="text-gray-500">
                                                                {item.type === 'upload' && '📄'}
                                                                {item.type === 'update' && '🔄'}
                                                                {item.type === 'comment' && '💬'}
                                                                {item.type === 'approval' && '✅'}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <div className="text-sm text-gray-500">
                                                            <span className="font-medium text-gray-900">
                                                                {item.action}
                                                            </span>{' '}
                                                            <span>{item.time}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
