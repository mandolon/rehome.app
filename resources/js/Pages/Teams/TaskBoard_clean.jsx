import React, { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import TaskDrawer from '../../Components/tasks/TaskDrawer';
import TaskCreateModal from '../../Components/tasks/TaskCreateModal';
import GroupSection from '../../Components/tasks/GroupSection';
import FilterChip from '../../Components/common/FilterChip';
import { apiGet, apiPost, apiPatch } from '../../lib/api';

// Mock data for immediate UI rendering
const MOCK_TASKS = [
  {
    id: '1',
    title: 'Review architectural plans',
    subtitle: 'Ocean View Resort • 123 Beach Blvd, Miami, FL',
    category: 'TASK/REDLINE',
    status: 'open',
    description: 'Review the latest architectural plans for the main lobby renovation. Focus on structural changes and code compliance.',
    createdAt: '2025-06-21T10:30:00Z',
    createdBy: { id: 1, name: 'John Smith', initials: 'JS' },
    assignees: [
      { id: 2, name: 'Sarah Johnson', initials: 'SJ' },
      { id: 3, name: 'Mike Chen', initials: 'MC' }
    ],
    dueDate: '2025-06-30',
    allowClient: false,
    fileCount: 3,
    attachments: [
      { id: 1, filename: 'floor-plan-v2.pdf', size: '2.4 MB', uploadedBy: 'John Smith', uploadedAt: '2025-06-21T10:00:00Z' },
      { id: 2, filename: 'elevation-drawings.dwg', size: '1.8 MB', uploadedBy: 'Sarah Johnson', uploadedAt: '2025-06-21T09:30:00Z' }
    ],
    activity: [
      { id: 1, type: 'status_change', description: 'Status changed: draft → open', timestamp: '2025-06-21T10:30:00Z', user: 'John Smith' },
      { id: 2, type: 'assignee_added', description: 'Sarah Johnson assigned to task', timestamp: '2025-06-21T10:15:00Z', user: 'John Smith' }
    ]
  },
  {
    id: '2',
    title: 'Update client on foundation progress',
    subtitle: 'Downtown Office Complex • 456 Main St, Boston, MA',
    category: 'PROGRESS/UPDATE',
    status: 'open',
    description: 'Prepare weekly progress update for the foundation work including photos and timeline updates.',
    createdAt: '2025-06-20T14:15:00Z',
    createdBy: { id: 3, name: 'Mike Chen', initials: 'MC' },
    assignees: [{ id: 1, name: 'John Smith', initials: 'JS' }],
    dueDate: '2025-06-25',
    allowClient: true,
    fileCount: 1,
    attachments: [],
    activity: []
  },
  {
    id: '3',
    title: 'Plumbing fixture placement review',
    subtitle: 'All bathroom fixtures',
    category: 'TASK/REDLINE',
    status: 'complete',
    description: 'Review placement of all bathroom fixtures against architectural plans.',
    createdAt: '2025-06-19T09:15:00Z',
    createdBy: { id: 4, name: 'Alex Rivera', initials: 'AR' },
    assignees: [
      { id: 1, name: 'John Smith', initials: 'JS' },
      { id: 5, name: 'Lisa Brown', initials: 'LB' }
    ],
    dueDate: '2025-06-25',
    allowClient: true,
    fileCount: 2,
    attachments: [
      { id: 3, filename: 'bathroom_layout.dwg', size: '1.8 MB', uploadedBy: 'Alex Rivera', uploadedAt: '2025-06-19T09:15:00Z' },
      { id: 4, filename: 'fixture_specs.pdf', size: '3.2 MB', uploadedBy: 'Lisa Brown', uploadedAt: '2025-06-19T11:30:00Z' }
    ],
    activity: [
      { id: 3, type: 'assignee_added', description: 'Lisa Brown was assigned to this task', timestamp: '2025-06-19T11:00:00Z', user: 'Alex Rivera' },
      { id: 4, type: 'status_change', description: 'Status changed: open → complete', timestamp: '2025-06-19T16:00:00Z', user: 'Lisa Brown' }
    ]
  }
];

export default function TaskBoard({ projectId = null }) {
  const { auth } = usePage().props;
  
  // Use mock data for now - TODO: Replace with API calls
  const [tasks, setTasks] = useState(MOCK_TASKS);
  const [loading, setLoading] = useState(false); // Set to false for immediate render
  const [error, setError] = useState(null);
  const [selectedTask, setSelectedTask] = useState(null);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);

  // TODO: Implement API loading
  // useEffect(() => {
  //   loadTasks();
  // }, [projectId]);

  // TODO: Replace with real API call
  // const loadTasks = async () => {
  //   try {
  //     setLoading(true);
  //     setError(null);
  //     
  //     const currentProjectId = projectId || 1;
  //     const response = await apiGet(`/api/v1/projects/${currentProjectId}/tasks`);
  //     
  //     const tasksData = response.data || response;
  //     setTasks(Array.isArray(tasksData) ? tasksData : []);
  //   } catch (err) {
  //     console.error('Error loading tasks:', err);
  //     setError(err.message);
  //     setTasks(MOCK_TASKS); // Fallback to mock data
  //   } finally {
  //     setLoading(false);
  //   }
  // };

  // Group tasks by category
  const groupedTasks = tasks.reduce((groups, task) => {
    if (!groups[task.category]) {
      groups[task.category] = [];
    }
    groups[task.category].push(task);
    return groups;
  }, {});

  const handleTaskClick = (task) => {
    // TODO: Load full task details from API
    // try {
    //   const response = await apiGet(`/api/v1/tasks/${task.id}`);
    //   const fullTask = response.data || response;
    //   setSelectedTask(fullTask);
    // } catch (err) {
    //   console.error('Error loading task details:', err);
    //   setSelectedTask(task); // Fallback to basic task data
    // }
    
    // For now, use mock task data
    setSelectedTask(task);
    setIsDrawerOpen(true);
  };

  const handleCloseDrawer = () => {
    setIsDrawerOpen(false);
    setSelectedTask(null);
  };

  const handleCreateTask = async (taskData) => {
    // TODO: Replace with real API call
    // try {
    //   const currentProjectId = projectId || 1;
    //   const response = await apiPost(`/api/v1/projects/${currentProjectId}/tasks`, taskData);
    //   await loadTasks(); // Refresh task list
    //   setIsCreateModalOpen(false);
    // } catch (err) {
    //   console.error('Error creating task:', err);
    // }
    
    // For now, add to local state
    const newTask = {
      id: Date.now().toString(),
      ...taskData,
      createdAt: new Date().toISOString(),
      createdBy: { name: auth.user?.name || 'Current User', initials: 'CU' },
      assignees: [],
      fileCount: 0,
      attachments: [],
      activity: [{
        id: Date.now(),
        type: 'task_created',
        description: 'Task created',
        user: auth.user?.name || 'Current User',
        timestamp: new Date().toISOString()
      }]
    };
    
    setTasks(prev => [...prev, newTask]);
    setIsCreateModalOpen(false);
  };

  const handleUpdateTask = async (taskId, updatedData) => {
    // TODO: Replace with real API call
    // try {
    //   await apiPatch(`/api/v1/tasks/${taskId}`, updatedData);
    //   await loadTasks(); // Refresh task list
    //   handleCloseDrawer();
    // } catch (err) {
    //   console.error('Error updating task:', err);
    // }
    
    // For now, update local state
    setTasks(prev => 
      prev.map(task => 
        task.id === taskId ? { ...task, ...updatedData } : task
      )
    );
    handleCloseDrawer();
  };

  if (loading) {
    return (
      <AppLayout>
        <div className="max-w-7xl mx-auto">
          <div className="animate-pulse">
            <div className="h-8 bg-gray-200 rounded w-1/4 mb-4"></div>
            <div className="h-4 bg-gray-200 rounded w-1/2 mb-8"></div>
            <div className="space-y-4">
              <div className="h-16 bg-gray-200 rounded"></div>
              <div className="h-16 bg-gray-200 rounded"></div>
              <div className="h-16 bg-gray-200 rounded"></div>
            </div>
          </div>
        </div>
      </AppLayout>
    );
  }

  if (error) {
    return (
      <AppLayout>
        <div className="max-w-7xl mx-auto">
          <div className="bg-red-50 border border-red-200 rounded-md p-4">
            <div className="text-red-800">
              <h3 className="font-medium">Error loading tasks</h3>
              <p className="text-sm mt-1">{error}</p>
              <button 
                onClick={() => window.location.reload()}
                className="mt-2 text-sm text-red-600 hover:text-red-500 underline"
              >
                Try again
              </button>
            </div>
          </div>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Task Board</h1>
            <p className="text-gray-600">Manage project tasks and progress updates</p>
          </div>
          <button 
            onClick={() => setIsCreateModalOpen(true)}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            + New Task
          </button>
        </div>

        {/* Filters */}
        <div className="flex gap-2 mb-6">
          <FilterChip>All Tasks</FilterChip>
          <FilterChip>Open</FilterChip>
          <FilterChip>Complete</FilterChip>
          <FilterChip>Overdue</FilterChip>
        </div>

        {/* Task Groups */}
        <div className="space-y-6">
          {Object.entries(groupedTasks).map(([category, categoryTasks]) => (
            <GroupSection
              key={category}
              title={category}
              tasks={categoryTasks}
              onTaskClick={handleTaskClick}
            />
          ))}
          
          {Object.keys(groupedTasks).length === 0 && (
            <div className="text-center py-12">
              <p className="text-gray-500">No tasks found. Create your first task to get started.</p>
            </div>
          )}
        </div>

        {/* Task Drawer */}
        <TaskDrawer
          isOpen={isDrawerOpen}
          task={selectedTask}
          onClose={handleCloseDrawer}
          onUpdate={handleUpdateTask}
        />

        {/* Create Task Modal */}
        <TaskCreateModal
          isOpen={isCreateModalOpen}
          onClose={() => setIsCreateModalOpen(false)}
          onCreate={handleCreateTask}
        />
      </div>
    </AppLayout>
  );
}
