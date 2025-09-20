import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function ProjectsIndex() {
    const [projects, setProjects] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [meta, setMeta] = useState({
        page: 1,
        perPage: 20,
        total: 0,
        lastPage: 1,
    });

    const fetchProjects = async (page = 1, searchQuery = '') => {
        setLoading(true);
        try {
            const params = new URLSearchParams({
                page: page.toString(),
                per_page: '20',
            });
            
            if (searchQuery) {
                params.append('q', searchQuery);
            }

            const response = await fetch(`/api/v1/projects?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to fetch projects');
            }

            const data = await response.json();
            
            if (data.ok) {
                setProjects(data.data);
                setMeta(data.meta);
                setCurrentPage(page);
            }
        } catch (error) {
            console.error('Error fetching projects:', error);
            // TODO: Add toast notification for errors
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchProjects();
    }, []);

    const handleSearch = (e) => {
        e.preventDefault();
        fetchProjects(1, search);
    };

    const handlePageChange = (page) => {
        fetchProjects(page, search);
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const getPhaseColor = (phase) => {
        const colors = {
            'design': 'bg-blue-100 text-blue-800',
            'permit': 'bg-yellow-100 text-yellow-800',
            'construction': 'bg-green-100 text-green-800',
            'complete': 'bg-gray-100 text-gray-800',
        };
        return colors[phase] || 'bg-gray-100 text-gray-800';
    };

    const renderPagination = () => {
        if (meta.lastPage <= 1) return null;

        const pages = [];
        const maxPages = 5;
        const half = Math.floor(maxPages / 2);
        let start = Math.max(currentPage - half, 1);
        let end = Math.min(start + maxPages - 1, meta.lastPage);

        if (end - start + 1 < maxPages) {
            start = Math.max(end - maxPages + 1, 1);
        }

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        return (
            <div className="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
                <div className="flex justify-between flex-1 sm:hidden">
                    <button
                        onClick={() => handlePageChange(Math.max(currentPage - 1, 1))}
                        disabled={currentPage === 1}
                        className="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <button
                        onClick={() => handlePageChange(Math.min(currentPage + 1, meta.lastPage))}
                        disabled={currentPage === meta.lastPage}
                        className="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
                <div className="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm text-gray-700">
                            Showing <span className="font-medium">{(currentPage - 1) * meta.perPage + 1}</span> to{' '}
                            <span className="font-medium">
                                {Math.min(currentPage * meta.perPage, meta.total)}
                            </span>{' '}
                            of <span className="font-medium">{meta.total}</span> results
                        </p>
                    </div>
                    <div>
                        <nav className="relative z-0 inline-flex -space-x-px rounded-md shadow-sm">
                            <button
                                onClick={() => handlePageChange(Math.max(currentPage - 1, 1))}
                                disabled={currentPage === 1}
                                className="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 disabled:opacity-50"
                            >
                                Previous
                            </button>
                            {pages.map((page) => (
                                <button
                                    key={page}
                                    onClick={() => handlePageChange(page)}
                                    className={`relative inline-flex items-center px-4 py-2 text-sm font-medium border ${
                                        page === currentPage
                                            ? 'bg-blue-50 border-blue-500 text-blue-600 z-10'
                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                    }`}
                                >
                                    {page}
                                </button>
                            ))}
                            <button
                                onClick={() => handlePageChange(Math.min(currentPage + 1, meta.lastPage))}
                                disabled={currentPage === meta.lastPage}
                                className="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 disabled:opacity-50"
                            >
                                Next
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <AppLayout>
            <Head title="Projects" />
            
            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <div className="md:flex md:items-center md:justify-between">
                            <div className="flex-1 min-w-0">
                                <h1 className="text-2xl font-bold text-gray-900">Projects</h1>
                                <p className="mt-1 text-sm text-gray-600">
                                    Manage your preconstruction projects and track progress.
                                </p>
                            </div>
                            <div className="mt-4 flex md:mt-0 md:ml-4">
                                <button
                                    type="button"
                                    className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    <span className="mr-2">+</span>
                                    New Project
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Search Bar */}
                    <div className="mb-6">
                        <form onSubmit={handleSearch} className="max-w-lg">
                            <div className="relative">
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Search projects..."
                                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                />
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span className="text-gray-400">🔍</span>
                                </div>
                                <div className="absolute inset-y-0 right-0 flex items-center">
                                    <button
                                        type="submit"
                                        className="mr-2 px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700"
                                    >
                                        Search
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {/* Projects Table */}
                    <div className="bg-white shadow overflow-hidden sm:rounded-md">
                        {loading ? (
                            <div className="p-6 text-center">
                                <div className="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                                <p className="mt-2 text-sm text-gray-500">Loading projects...</p>
                            </div>
                        ) : projects.length === 0 ? (
                            <div className="p-6 text-center">
                                <p className="text-gray-500">
                                    {search ? `No projects found matching "${search}"` : 'No projects found'}
                                </p>
                                {!search && (
                                    <button className="mt-2 text-blue-600 hover:text-blue-500">
                                        Create your first project
                                    </button>
                                )}
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Name
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Phase
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Zoning
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Updated
                                                </th>
                                                <th className="relative px-6 py-3">
                                                    <span className="sr-only">Actions</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {projects.map((project) => (
                                                <tr key={project.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div>
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {project.name}
                                                            </div>
                                                            {project.description && (
                                                                <div className="text-sm text-gray-500 truncate max-w-xs">
                                                                    {project.description}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getPhaseColor(project.phase)}`}>
                                                            {project.phase}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {project.zoning || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {formatDate(project.updated_at)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <Link
                                                            href={`/projects/${project.id}`}
                                                            className="text-blue-600 hover:text-blue-900"
                                                        >
                                                            View
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {renderPagination()}
                            </>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
