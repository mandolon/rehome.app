import React, { useState, useEffect } from 'react';
import { apiPatch, apiPost } from '../../lib/api';

export default function TaskDrawer({ 
  isOpen = false, 
  task = null, 
  onClose = () => {},
  onSaved = () => {},
  projectId = '1'
}) {
  // Form state
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [status, setStatus] = useState('open');
  const [assigneeId, setAssigneeId] = useState('');
  const [file, setFile] = useState(null);

  // API state
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  // Update form when task changes
  useEffect(() => {
    if (task) {
      setTitle(task.title || '');
      setDescription(task.description || '');
      setStatus(task.status || 'todo'); // backend enum: todo/in_progress/blocked/done
      setAssigneeId(task.assignee_id || '');
      setFile(null);
      setError('');
    }
  }, [task]);

  // API-integrated save handler
  async function handleSave() {
    if (!task) return;
    
    setSaving(true);
    setError("");
    try {
      const res = await apiPatch(`/projects/${projectId}/tasks/${task.id}`, {
        title,
        description,
        status,
        assignee_id: assigneeId || null,
      });

      // Upload file if provided
      if (file) {
        const form = new FormData();
        form.append("file_id", file.id); // assuming file object has id
        await apiPost(`/tasks/${task.id}/files`, form, {
          headers: { "Content-Type": "multipart/form-data" },
        });
      }

      onSaved?.(res?.data);
      onClose?.();
    } catch (e) {
      setError(e?.response?.data?.message || "Failed to save task.");
    } finally {
      setSaving(false);
    }
  }
  if (!isOpen || !task) return null;

  return (
    <>
      {/* Overlay */}
      <div 
        className="fixed inset-0 bg-black bg-opacity-50 z-40"
        onClick={onClose}
      />
      
      {/* Drawer */}
      <div className="fixed inset-y-0 right-0 w-96 bg-white shadow-xl z-50 flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <div>
            <span className={`px-2 py-1 text-xs font-semibold uppercase rounded ${
              task.category === 'TASK/REDLINE' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'
            }`}>
              {task.category}
            </span>
          </div>
          <button 
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 p-2"
          >
            ✕
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 p-6 overflow-y-auto">
          {/* Error Display */}
          {error && (
            <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
              <p className="text-sm text-red-600">{error}</p>
            </div>
          )}

          {/* Editable Title */}
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Title
            </label>
            <input
              type="text"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          {/* Editable Description */}
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Description
            </label>
            <textarea
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              rows="3"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          {/* Status Selection */}
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Status
            </label>
            <select
              value={status}
              onChange={(e) => setStatus(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="todo">To Do</option>
              <option value="in_progress">In Progress</option>
              <option value="blocked">Blocked</option>
              <option value="done">Done</option>
            </select>
          </div>
          
          {/* Placeholder for more content */}
          <div className="mt-6">
            <p className="text-sm text-gray-400">Task details will be implemented here...</p>
          </div>
        </div>

        {/* Footer */}
        <div className="border-t border-gray-200 p-6">
          <div className="flex space-x-3">
            <button 
              onClick={onClose}
              className="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
            >
              Cancel
            </button>
            <button 
              onClick={handleSave}
              disabled={saving}
              className="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {saving ? 'Saving...' : 'Save Changes'}
            </button>
          </div>
        </div>
      </div>
    </>
  );
}
