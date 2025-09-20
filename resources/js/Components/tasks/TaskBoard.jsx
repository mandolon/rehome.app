import React, { useState, useEffect, useMemo } from 'react';
import AppLayout from '../Layouts/AppLayout';
import FilterChip from '../common/FilterChip';
import GroupSection from './GroupSection';
import TaskDrawer from './TaskDrawer';
import TaskCreateModal from './TaskCreateModal';
import { apiGet } from '../../lib/api';

export default function TaskBoard({ projectId = '1' }) {
  // API state
  const [rows, setRows] = useState([]);
  const [meta, setMeta] = useState({ counts: {}, pagination: {} });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  // Filter state (match UI controls)
  const [q, setQ] = useState("");
  const [status, setStatus] = useState([]);           // status[]
  const [assignees, setAssignees] = useState([]);     // assignee_id[]
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");

  const [selectedTask, setSelectedTask] = useState(null);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);

  // Build API params
  const params = useMemo(() => {
    const p = {};
    if (q) p.q = q;
    if (status.length) p["status[]"] = status;
    if (assignees.length) p["assignee_id[]"] = assignees;
    if (dateFrom) p.date_from = dateFrom;
    if (dateTo) p.date_to = dateTo;
    return p;
  }, [q, status, assignees, dateFrom, dateTo]);

  // Load tasks from API
  async function load() {
    setLoading(true);
    setError("");
    try {
      const res = await apiGet(`/projects/${projectId}/tasks`, params);
      setRows(res?.data ?? []);
      setMeta(res?.meta ?? {});
    } catch (e) {
      setError(e?.response?.data?.message || "Failed to load tasks.");
      setRows([]); // Show empty state on error
    } finally {
      setLoading(false);
    }
  }

  // Call load() on mount + when filters change
  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [projectId, JSON.stringify(params)]);

  // Category normalization for grouping
  function normalizeCategory(raw) {
    if (!raw) return "OTHER";
    const up = String(raw).toUpperCase();
    if (up.includes("TASK") || up.includes("REDLINE")) return "TASK/REDLINE";
    if (up.includes("PROGRESS") || up.includes("UPDATE")) return "PROGRESS/UPDATE";
    return "OTHER";
  }

  // Group tasks by category - use only API data
  const groupedTasks = rows.reduce((groups, task) => {
    const category = normalizeCategory(task.category);
    if (!groups[category]) {
      groups[category] = [];
    }
    groups[category].push(task);
    return groups;
  }, {});

  // Show "No tasks yet" message when empty
  const isEmpty = rows.length === 0;
  const showEmptyState = isEmpty && !loading;

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
    // Refresh task list after creation
    load();
  };

  const handleTaskSaved = (savedTask) => {
    console.log('Task saved:', savedTask);
    // Refresh task list after save
    load();
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
          
          {Object.keys(groupedTasks).length === 0 && !loading && (
            <div className="text-center py-12">
              <p className="text-gray-500">
                {error ? `Error loading tasks: ${error}` : "No tasks yet. Create your first task to get started."}
              </p>
            </div>
          )}
          
          {loading && (
            <div className="text-center py-12">
              <p className="text-gray-500">Loading tasks...</p>
            </div>
          )}
        </div>

        {/* Task Drawer */}
        <TaskDrawer
          isOpen={isDrawerOpen}
          task={selectedTask}
          onClose={handleCloseDrawer}
          onSaved={handleTaskSaved}
          projectId={projectId}
        />

        {/* Create Task Modal */}
        <TaskCreateModal
          isOpen={isCreateModalOpen}
          onClose={() => setIsCreateModalOpen(false)}
          onCreate={handleCreateTask}
          onCreated={handleCreateTask}
          projectId={projectId}
        />
      </div>
    </AppLayout>
  );
}
