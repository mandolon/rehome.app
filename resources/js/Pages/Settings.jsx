import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';

export default function Settings() {
    const { auth } = usePage().props;

    return (
        <AppLayout>
            <Head title="Settings" />
            
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
                        <p className="mt-1 text-sm text-gray-600">
                            Configure your account and application preferences.
                        </p>
                    </div>

                    <div className="space-y-6">
                        {/* Account Settings */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">
                                    Account Settings
                                </h3>
                                <p className="text-gray-600 mb-4">
                                    Manage your account information and preferences.
                                </p>
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Company Name
                                        </label>
                                        <input
                                            type="text"
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                            placeholder="Your Company Name"
                                            disabled
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700">
                                            Time Zone
                                        </label>
                                        <select className="mt-1 block w-full border-gray-300 rounded-md shadow-sm" disabled>
                                            <option>Pacific Standard Time</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Notification Settings */}
                        <div className="bg-white shadow rounded-lg">
                            <div className="px-4 py-5 sm:p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">
                                    Notifications
                                </h3>
                                <p className="text-gray-600 mb-4">
                                    Choose how you want to be notified about project updates.
                                </p>
                                <div className="space-y-4">
                                    <div className="flex items-center">
                                        <input
                                            type="checkbox"
                                            className="h-4 w-4 text-blue-600 border-gray-300 rounded"
                                            disabled
                                        />
                                        <label className="ml-2 text-sm text-gray-700">
                                            Email notifications for project updates
                                        </label>
                                    </div>
                                    <div className="flex items-center">
                                        <input
                                            type="checkbox"
                                            className="h-4 w-4 text-blue-600 border-gray-300 rounded"
                                            disabled
                                        />
                                        <label className="ml-2 text-sm text-gray-700">
                                            SMS notifications for urgent items
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Development Notice */}
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div className="flex">
                                <div className="flex-shrink-0">
                                    <span className="text-blue-400">ℹ️</span>
                                </div>
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-blue-800">
                                        Settings Under Development
                                    </h3>
                                    <p className="mt-1 text-sm text-blue-700">
                                        Full settings functionality is coming soon. This page will include 
                                        billing management, team permissions, and advanced configuration options.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
