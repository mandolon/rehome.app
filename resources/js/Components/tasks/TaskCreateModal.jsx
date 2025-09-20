import React, { useState } from 'react';
import { apiPost } from '../../lib/api';

export default function TaskCreateModal({ 
  isOpen = false, 
  onClose = () => {},
  onCreate = () => {},
  onCreated = () => {},
  projectId = '1'
}) {
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    category: 'TASK/REDLINE',
    priority: 'medium',
    dueDate: ''
  });

  // API state
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  // API-integrated create handler
  async function handleCreate() {
    if (!formData.title.trim()) return;
    
    setSaving(true);
    setError("");
    try {
      const res = await apiPost(`/projects/${projectId}/tasks`, {
        title: formData.title,
        description: formData.description,
        category: formData.category,
        status: 'todo', // backend enum: todo/in_progress/blocked/done
        assignee_id: null, // can be extended later
        due_date: formData.dueDate || null,
      });
      
      onCreated?.(res?.data);
      onClose?.();
      
      // Reset form
      setFormData({
        title: '',
        description: '',
        category: 'TASK/REDLINE',
        priority: 'medium',
        dueDate: ''
      });
    } catch (e) {
      setError(e?.response?.data?.message || "Failed to create task.");
    } finally {
      setSaving(false);
    }
  }

  const handleSubmit = (e) => {
    e.preventDefault();
    handleCreate();
  };

  if (!isOpen) return null;

  return (
    <>
      {/* Overlay */}
      <div 
        className="fixed inset-0 bg-black bg-opacity-50 z-50"
        onClick={onClose}
      />
      
      {/* Modal */}
      <div className="fixed inset-0 flex items-center justify-center z-50 p-4">
        <div className="bg-white rounded-lg shadow-xl max-w-md w-full max-h-full overflow-hidden">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b border-gray-200">
            <h2 className="text-lg font-semibold">Create New Task</h2>
            <button 
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600 p-2"
            >
              ✕
            </button>
          </div>
          {/* Form */}
          <form onSubmit={handleSubmit} className="p-6">
            {/* Error Display */}
            {error && (
              <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                <p className="text-sm text-red-600">{error}</p>
              </div>
            )}
            {/* Title */}
            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Title *
              </label>
              <input
                type="text"
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={formData.title}
                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                placeholder="Enter task title"
                required
              />
            </div>

            {/* Category */}
            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Category
              </label>
              <select
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={formData.category}
                onChange={(e) => setFormData({ ...formData, category: e.target.value })}
              >
                <option value="TASK/REDLINE">Task/Redline</option>
                <option value="PROGRESS/PHOTO">Progress/Photo</option>
                <option value="TASK/INSPECTION">Task/Inspection</option>
              </select>
            </div>

            {/* Priority */}
            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Priority
              </label>
              <select
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={formData.priority}
                onChange={(e) => setFormData({ ...formData, priority: e.target.value })}
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
            </div>

            {/* Due Date */}
            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Due Date
              </label>
              <input
                type="date"
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={formData.dueDate}
                onChange={(e) => setFormData({ ...formData, dueDate: e.target.value })}
              />
            </div>

            {/* Description */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Description
              </label>
              <textarea
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                rows="3"
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Enter task description (optional)"
              />
            </div>

            {/* Actions */}
            <div className="flex space-x-3">
              <button 
                type="button"
                onClick={onClose}
                className="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
              >
                Cancel
              </button>
              <button 
                type="submit"
                disabled={saving || !formData.title.trim()}
                className="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {saving ? 'Creating...' : 'Create Task'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </>
  );
}
