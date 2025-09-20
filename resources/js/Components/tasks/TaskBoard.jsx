import React, { useState } from 'react';
import AppLayout from '../Layouts/AppLayout';
import FilterChip from '../common/FilterChip';
import GroupSection from './GroupSection';
import TaskDrawer from './TaskDrawer';
import TaskCreateModal from './TaskCreateModal';

// Mock data for demo
const MOCK_TASKS = [
  {
    id: 1,
    title: 'Electrical rough-in inspection needed',
    subtitle: '2nd floor master bedroom',
    description: 'Review electrical rough-in work in master bedroom before drywall installation.',
    category: 'TASK/REDLINE',
    status: 'open',
    createdAt: '2024-01-15T10:00:00Z',
    createdBy: { name: 'John Smith', initials: 'JS' },
    assignees: [{ name: 'Mike Davis', initials: 'MD' }],
    fileCount: 3
  },
  {
    id: 2,
    title: 'Foundation inspection complete',
    subtitle: 'West side foundation',
    description: 'Foundation inspection passed all requirements.',
    category: 'PROGRESS/UPDATE',
    status: 'complete',
    createdAt: '2024-01-14T14:30:00Z',
    createdBy: { name: 'Sarah Wilson', initials: 'SW' },
    assignees: [{ name: 'John Smith', initials: 'JS' }],
    fileCount: 0
  }
];

export default function TaskBoard() {
  const [tasks] = useState(MOCK_TASKS);
  const [selectedTask, setSelectedTask] = useState(null);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  
  // Group tasks by category
  const groupedTasks = tasks.reduce((groups, task) => {
    if (!groups[task.category]) {
      groups[task.category] = [];
    }
    groups[task.category].push(task);
    return groups;
  }, {});

  const handleTaskClick = (task) => {
    setSelectedTask(task);
    setIsDrawerOpen(true);
  };

  const handleCloseDrawer = () => {
    setIsDrawerOpen(false);
    setSelectedTask(null);
  };

  const handleCreateTask = (taskData) => {
    console.log('Creating task:', taskData);
    // In real app, this would call API
  };

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
