import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';

export default function Team() {
    const { auth } = usePage().props;

    return (
        <AppLayout>
            <Head title="Team" />
            
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Team Management</h1>
                        <p className="mt-1 text-sm text-gray-600">
                            Manage your team members and their permissions.
                        </p>
                    </div>

                    <div className="bg-white shadow rounded-lg">
                        <div className="px-4 py-5 sm:p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">
                                Coming Soon
                            </h3>
                            <p className="text-gray-600">
                                Team management features are under development. You'll be able to:
                            </p>
                            <ul className="mt-4 list-disc list-inside text-gray-600 space-y-2">
                                <li>Invite new team members</li>
                                <li>Manage user roles and permissions</li>
                                <li>View team activity and performance</li>
                                <li>Set up project assignments</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
