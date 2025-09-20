import React from 'react';
import { Link, usePage } from '@inertiajs/react';

const navigation = {
    admin: [
        { name: 'Dashboard', href: '/dashboard', icon: '📊' },
        { name: 'Projects', href: '/projects', icon: '🏗️' },
        { name: 'Team', href: '/team', icon: '👥' },
        { name: 'Billing', href: '/billing', icon: '💳' },
        { name: 'Settings', href: '/settings', icon: '⚙️' },
    ],
    team: [
        { name: 'Dashboard', href: '/dashboard', icon: '📊' },
        { name: 'Projects', href: '/projects', icon: '🏗️' },
        { name: 'Docs', href: '/docs', icon: '📄' },
        { name: 'Tasks', href: '/tasks', icon: '✅' },
    ],
    client: [
        { name: 'Dashboard', href: '/dashboard', icon: '📊' },
        { name: 'My Projects', href: '/projects', icon: '🏗️' },
        { name: 'Docs', href: '/docs', icon: '📄' },
        { name: 'Approvals', href: '/approvals', icon: '✅' },
    ],
};

export default function AppLayout({ children }) {
    const { auth, currentRoute } = usePage().props;
    const userRole = auth?.user?.role || 'client';
    const userNav = navigation[userRole] || navigation.client;

    return (
        <div className="min-h-screen bg-gray-50">
            <div className="flex">
                {/* Sidebar */}
                <div className="hidden md:flex md:w-64 md:flex-col">
                    <div className="flex flex-col flex-grow pt-5 overflow-y-auto bg-white border-r border-gray-200">
                        <div className="flex items-center flex-shrink-0 px-4">
                            <h1 className="text-xl font-bold text-gray-900">
                                PreConstruct
                            </h1>
                        </div>
                        
                        <div className="mt-5 flex-grow flex flex-col">
                            <nav className="flex-1 px-2 pb-4 space-y-1">
                                {userNav.map((item) => {
                                    const isActive = currentRoute?.startsWith(item.href) || 
                                                   (item.href === '/dashboard' && currentRoute === '/dashboard');
                                    
                                    return (
                                        <Link
                                            key={item.name}
                                            href={item.href}
                                            className={`group flex items-center px-2 py-2 text-sm font-medium rounded-md ${
                                                isActive
                                                    ? 'bg-blue-100 text-blue-900'
                                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                            }`}
                                        >
                                            <span className="mr-3 text-lg">{item.icon}</span>
                                            {item.name}
                                        </Link>
                                    );
                                })}
                            </nav>
                        </div>

                        {/* User info */}
                        <div className="flex-shrink-0 p-4 border-t border-gray-200">
                            <div className="flex items-center">
                                <div className="ml-3">
                                    <p className="text-sm font-medium text-gray-900">
                                        {auth?.user?.name}
                                    </p>
                                    <p className="text-xs text-gray-500 capitalize">
                                        {userRole}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main content */}
                <div className="flex flex-col flex-1">
                    {/* Mobile header */}
                    <div className="md:hidden">
                        <div className="bg-white shadow-sm border-b border-gray-200">
                            <div className="px-4 py-3">
                                <h1 className="text-lg font-semibold text-gray-900">
                                    PreConstruct
                                </h1>
                            </div>
                        </div>
                    </div>

                    {/* Page content */}
                    <main className="flex-1">
                        {children}
                    </main>
                </div>
            </div>
        </div>
    );
}
