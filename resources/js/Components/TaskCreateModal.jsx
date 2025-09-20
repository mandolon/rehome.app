import React, { useState, useEffect } from 'react';

const TaskCreateModal = ({ isOpen, onClose, onCreate, mockUsers }) => {
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    status: 'open',
    assigneeIds: [],
    dueDate: '',
    projectId: ''
  });

  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!isOpen) {
      setFormData({
        title: '',
        description: '',
        status: 'open',
        assigneeIds: [],
        dueDate: '',
        projectId: ''
      });
      setErrors({});
    }
  }, [isOpen]);

  const validateForm = () => {
    const newErrors = {};
    
    if (!formData.title.trim()) {
      newErrors.title = 'Title is required';
    }
    
    if (!formData.projectId) {
      newErrors.projectId = 'Project is required';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    setLoading(true);
    
    try {
      // TODO: Replace with actual API call
      // await api.post('/tasks', formData);
      
      // Mock success response
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      const newTask = {
        id: Date.now(),
        ...formData,
        assignees: mockUsers?.filter(user => formData.assigneeIds.includes(user.id)) || [],
        createdAt: new Date().toISOString()
      };
      
      onCreate(newTask);
      onClose();
    } catch (error) {
      console.error('Error creating task:', error);
      setErrors({ submit: 'Failed to create task. Please try again.' });
    } finally {
      setLoading(false);
    }
  };

  const addAssignee = (userId) => {
    if (userId && !formData.assigneeIds.includes(userId)) {
      setFormData({
        ...formData,
        assigneeIds: [...formData.assigneeIds, userId]
      });
    }
  };

  const removeAssignee = (userId) => {
    setFormData({
      ...formData,
      assigneeIds: formData.assigneeIds.filter(id => id !== userId)
    });
  };

  if (!isOpen) return null;

  return (
    <>
      <div className="modal-overlay" onClick={onClose} />
      <div className={`modal-content ${isOpen ? 'open' : ''}`}>
        <div className="modal-header">
          <h2>Create New Task</h2>
          <button 
            className="btn btn-secondary"
            onClick={onClose}
            style={{ padding: '0.5rem' }}
          >
            ✕
          </button>
        </div>

        <form onSubmit={handleSubmit} className="modal-body">
          {errors.submit && (
            <div className="error-message" style={{ marginBottom: '1rem' }}>
              {errors.submit}
            </div>
          )}

          <div className="form-group">
            <label htmlFor="title">
              Title <span style={{ color: 'var(--error-color)' }}>*</span>
            </label>
            <input
              id="title"
              className={`input ${errors.title ? 'error' : ''}`}
              type="text"
              value={formData.title}
              onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              placeholder="Enter task title"
              disabled={loading}
            />
            {errors.title && <div className="error-text">{errors.title}</div>}
          </div>

          <div className="form-group">
            <label htmlFor="description">Description</label>
            <textarea
              id="description"
              className="input"
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              placeholder="Add a description (optional)"
              rows={3}
              style={{ resize: 'vertical' }}
              disabled={loading}
            />
          </div>

          <div className="form-row">
            <div className="form-group">
              <label htmlFor="status">Status</label>
              <select
                id="status"
                className="select"
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                disabled={loading}
              >
                <option value="open">Open</option>
                <option value="complete">Complete</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="projectId">
                Project <span style={{ color: 'var(--error-color)' }}>*</span>
              </label>
              <select
                id="projectId"
                className={`select ${errors.projectId ? 'error' : ''}`}
                value={formData.projectId}
                onChange={(e) => setFormData({ ...formData, projectId: e.target.value })}
                disabled={loading}
              >
                <option value="">Select project...</option>
                {/* TODO: Replace with actual projects from API */}
                <option value="1">Downtown Office Renovation</option>
                <option value="2">Residential Complex Phase 2</option>
                <option value="3">Shopping Center Expansion</option>
              </select>
              {errors.projectId && <div className="error-text">{errors.projectId}</div>}
            </div>
          </div>

          <div className="form-group">
            <label htmlFor="dueDate">Due Date</label>
            <input
              id="dueDate"
              className="input"
              type="date"
              value={formData.dueDate}
              onChange={(e) => setFormData({ ...formData, dueDate: e.target.value })}
              disabled={loading}
            />
          </div>

          <div className="form-group">
            <label>Assignees</label>
            
            {formData.assigneeIds.length > 0 && (
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.5rem', marginBottom: '0.5rem' }}>
                {formData.assigneeIds.map(userId => {
                  const user = mockUsers?.find(u => u.id === userId);
                  if (!user) return null;
                  
                  return (
                    <div 
                      key={userId}
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '0.5rem',
                        padding: '0.25rem 0.5rem',
                        backgroundColor: 'var(--background-secondary)',
                        borderRadius: '16px',
                        fontSize: '0.875rem'
                      }}
                    >
                      <AvatarChip user={user} size="sm" />
                      <span>{user.name}</span>
                      <button
                        type="button"
                        onClick={() => removeAssignee(userId)}
                        style={{
                          background: 'none',
                          border: 'none',
                          cursor: 'pointer',
                          padding: '0',
                          fontSize: '0.75rem',
                          color: 'var(--text-secondary)'
                        }}
                      >
                        ✕
                      </button>
                    </div>
                  );
                })}
              </div>
            )}

            <select
              className="select"
              onChange={(e) => {
                if (e.target.value) {
                  addAssignee(parseInt(e.target.value));
                  e.target.value = '';
                }
              }}
              disabled={loading}
            >
              <option value="">Add assignee...</option>
              {mockUsers?.filter(user => !formData.assigneeIds.includes(user.id)).map(user => (
                <option key={user.id} value={user.id}>{user.name}</option>
              ))}
            </select>
          </div>

          <div className="modal-footer">
            <button 
              type="button"
              className="btn btn-secondary"
              onClick={onClose}
              disabled={loading}
            >
              Cancel
            </button>
            <button 
              type="submit"
              className="btn btn-primary"
              disabled={loading}
            >
              {loading ? 'Creating...' : 'Create Task'}
            </button>
          </div>
        </form>
      </div>
    </>
  );
};

// Avatar component helper
const AvatarChip = ({ user, size = 'sm' }) => {
  const getAvatarColor = (name) => {
    const colors = [
      '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
      '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
      '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
      '#ec4899', '#f43f5e'
    ];
    const index = name.split('').reduce((acc, char) => acc + char.charCodeCode(0), 0);
    return colors[index % colors.length];
  };

  const sizeClasses = {
    sm: { width: '24px', height: '24px', fontSize: '0.625rem' },
    md: { width: '32px', height: '32px', fontSize: '0.75rem' },
    lg: { width: '40px', height: '40px', fontSize: '0.875rem' }
  };

  return (
    <div 
      className="avatar"
      style={{
        backgroundColor: getAvatarColor(user.name),
        ...sizeClasses[size]
      }}
      title={user.name}
    >
      {user.initials}
    </div>
  );
};

export default TaskCreateModal;
