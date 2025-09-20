import React from 'react';
import AppLayout from '../../Layouts/AppLayout';
import TaskDrawer from '../../Components/tasks/TaskDrawer';
import TaskCreateModal from '../../Components/tasks/TaskCreateModal';
import GroupSection from '../../Components/tasks/GroupSection';
import FilterChip from '../../Components/common/FilterChip';

export default function TaskBoard() {
  const [tasks, setTasks] = React.useState([
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
      filesCount: 3,
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
      filesCount: 1,
      attachments: [],
      activity: []
    },
    {
      id: '3',
      title: 'Electrical system inspection',
      subtitle: 'Ocean View Resort • 123 Beach Blvd, Miami, FL',
      category: 'TASK/REDLINE',
      status: 'complete',
      description: 'Complete electrical inspection for floors 1-3 according to local building codes.',
      createdAt: '2025-06-19T09:00:00Z',
      createdBy: { id: 2, name: 'Sarah Johnson', initials: 'SJ' },
      assignees: [{ id: 4, name: 'Alex Rivera', initials: 'AR' }],
      dueDate: '2025-06-22',
      allowClient: false,
      filesCount: 0,
      attachments: [],
      activity: []
    }
  ]);

  const [searchQuery, setSearchQuery] = React.useState('');
  const [selectedTask, setSelectedTask] = React.useState(null);
  const [isDrawerOpen, setIsDrawerOpen] = React.useState(false);
  const [isCreateModalOpen, setIsCreateModalOpen] = React.useState(false);
  const [showClosed, setShowClosed] = React.useState(false);
  const [collapsedGroups, setCollapsedGroups] = React.useState({});
  const [editData, setEditData] = React.useState({});

  const mockUsers = [
    { id: 1, name: 'John Smith', initials: 'JS' },
    { id: 2, name: 'Sarah Johnson', initials: 'SJ' },
    { id: 3, name: 'Mike Chen', initials: 'MC' },
    { id: 4, name: 'Alex Rivera', initials: 'AR' },
    { id: 5, name: 'Emma Wilson', initials: 'EW' }
  ];

  // Helper functions
  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric', 
      year: '2-digit' 
    }).replace(',', '');
  };

  const getAvatarColor = (name) => {
    const colors = [
      '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
      '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
      '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
      '#ec4899', '#f43f5e'
    ];
    const index = name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
    return colors[index % colors.length];
  };

  // URL sync for task drawer
  React.useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const taskId = urlParams.get('task');
    
    if (taskId && !isDrawerOpen) {
      const task = tasks.find(t => t.id === taskId);
      if (task) {
        setSelectedTask(task);
        setIsDrawerOpen(true);
      }
    } else if (!taskId && isDrawerOpen) {
      setIsDrawerOpen(false);
      setSelectedTask(null);
    }
  }, [tasks, isDrawerOpen]);

  const updateURL = (taskId) => {
    const url = new URL(window.location);
    if (taskId) {
      url.searchParams.set('task', taskId);
    } else {
      url.searchParams.delete('task');
    }
    window.history.replaceState({}, '', url);
  };

  React.useEffect(() => {
    if (selectedTask) {
      setEditData({
        title: selectedTask.title || '',
        description: selectedTask.description || '',
        status: selectedTask.status || 'open',
        assigneeIds: selectedTask.assignees?.map(a => a.id) || [],
        dueDate: selectedTask.dueDate || '',
        allowClient: selectedTask.allowClient || false
      });
    }
  }, [selectedTask]);

  const filteredTasks = tasks.filter(task => {
    const matchesSearch = !searchQuery || 
      task.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      task.subtitle.toLowerCase().includes(searchQuery.toLowerCase());
    
    const matchesStatus = showClosed || task.status !== 'complete';
    
    return matchesSearch && matchesStatus;
  });

  const groupedTasks = {
    'TASK/REDLINE': filteredTasks.filter(task => task.category === 'TASK/REDLINE'),
    'PROGRESS/UPDATE': filteredTasks.filter(task => task.category === 'PROGRESS/UPDATE')
  };

  const handleTaskClick = (task) => {
    setSelectedTask(task);
    setIsDrawerOpen(true);
    updateURL(task.id);
  };

  const handleDrawerClose = () => {
    setIsDrawerOpen(false);
    setSelectedTask(null);
    updateURL(null);
  };

  const handleTaskUpdate = (taskId, updateData) => {
    setTasks(prev => prev.map(task => 
      task.id === taskId 
        ? { 
            ...task, 
            ...updateData,
            assignees: updateData.assigneeIds 
              ? mockUsers.filter(user => updateData.assigneeIds.includes(user.id))
              : task.assignees
          }
        : task
    ));
    setIsDrawerOpen(false);
    updateURL(null);
  };

  const handleCreateTask = (formData) => {
    const newTask = {
      id: String(Date.now()),
      ...formData,
      createdAt: new Date().toISOString(),
      createdBy: { id: 1, name: 'Current User', initials: 'CU' },
      assignees: [],
      attachments: [],
      activity: [],
      filesCount: 0
    };
    setTasks(prev => [newTask, ...prev]);
    setIsCreateModalOpen(false);
  };

  const toggleGroup = (groupName) => {
    setCollapsedGroups(prev => ({
      ...prev,
      [groupName]: !prev[groupName]
    }));
  };

  return (
    <AppLayout>
      <style>{`
        .task-board-root {
          --bg-primary: #ffffff;
          --bg-secondary: #f8f9fa;
          --bg-tertiary: #f1f3f4;
          --text-primary: #202124;
          --text-secondary: #5f6368;
          --text-muted: #9aa0a6;
          --border-color: #dadce0;
          --accent-primary: #1a73e8;
          --success: #137333;
          font-family: 'Google Sans', Roboto, sans-serif;
        }
        
        [data-theme="dark"] .task-board-root {
          --bg-primary: #1f1f1f;
          --bg-secondary: #2d2d30;
          --bg-tertiary: #3c3c3c;
          --text-primary: #cccccc;
          --text-secondary: #969696;
          --text-muted: #6c6c6c;
          --border-color: #3c3c3c;
          --accent-primary: #4285f4;
          --success: #34a853;
        }

        .avatar-chip {
          width: 24px;
          height: 24px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 10px;
          font-weight: 500;
          color: white;
        }

        .status-dot {
          width: 6px;
          height: 6px;
          border-radius: 50%;
          display: inline-block;
        }

        .status-open { background-color: var(--accent-primary); }
        .status-complete { background-color: var(--success); }

        .task-row {
          cursor: pointer;
          transition: background-color 0.15s ease;
        }

        .task-row:hover {
          background-color: var(--bg-tertiary);
        }

        .drawer-overlay {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background-color: rgba(0, 0, 0, 0.4);
          z-index: 1000;
        }

        .drawer-content {
          position: fixed;
          top: 0;
          right: 0;
          height: 100vh;
          width: 400px;
          background-color: var(--bg-primary);
          box-shadow: -2px 0 8px rgba(0, 0, 0, 0.15);
          overflow-y: auto;
          transform: translateX(100%);
          transition: transform 0.3s ease;
          z-index: 1001;
        }

        .drawer-content.open {
          transform: translateX(0);
        }

        @media (max-width: 768px) {
          .drawer-content {
            width: 100%;
          }
        }

        .group-pill {
          display: inline-block;
          padding: 2px 8px;
          border-radius: 10px;
          font-size: 10px;
          font-weight: 600;
          text-transform: uppercase;
          color: white;
        }

        .pill-task { background-color: #1a73e8; }
        .pill-progress { background-color: #34a853; }

        .filter-button {
          display: flex;
          align-items: center;
          gap: 4px;
          padding: 4px 8px;
          font-size: 12px;
          color: var(--text-secondary);
          background: transparent;
          border: 1px solid transparent;
          border-radius: 4px;
          cursor: pointer;
          transition: all 0.15s ease;
        }

        .filter-button:hover {
          background-color: var(--bg-tertiary);
          border-color: var(--border-color);
        }

        .files-badge {
          background-color: var(--bg-tertiary);
          color: var(--text-secondary);
          padding: 2px 6px;
          border-radius: 3px;
          font-size: 11px;
          font-weight: 500;
        }

        .input-field {
          width: 100%;
          padding: 8px 12px;
          border: 1px solid var(--border-color);
          border-radius: 4px;
          background-color: var(--bg-primary);
          color: var(--text-primary);
          font-size: 14px;
        }

        .input-field:focus {
          outline: none;
          border-color: var(--accent-primary);
          box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

        .textarea-field {
          width: 100%;
          padding: 12px;
          border: 1px solid var(--border-color);
          border-radius: 4px;
          background-color: var(--bg-primary);
          color: var(--text-primary);
          font-size: 14px;
          min-height: 80px;
          resize: vertical;
          font-family: inherit;
          line-height: 1.4;
        }

        .textarea-field:focus {
          outline: none;
          border-color: var(--accent-primary);
          box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

        .btn {
          padding: 8px 16px;
          border-radius: 4px;
          font-size: 14px;
          font-weight: 500;
          cursor: pointer;
          transition: all 0.15s ease;
          border: none;
          display: inline-flex;
          align-items: center;
          gap: 8px;
        }

        .btn-primary {
          background-color: var(--accent-primary);
          color: white;
        }

        .btn-primary:hover {
          background-color: #1557b0;
        }

        .btn-secondary {
          background-color: var(--bg-secondary);
          color: var(--text-primary);
          border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
          background-color: var(--bg-tertiary);
        }

        .status-toggle {
          display: flex;
          background-color: var(--bg-secondary);
          border-radius: 4px;
          border: 1px solid var(--border-color);
          overflow: hidden;
        }

        .status-toggle button {
          flex: 1;
          padding: 8px 16px;
          border: none;
          background: transparent;
          color: var(--text-secondary);
          font-size: 14px;
          cursor: pointer;
          transition: all 0.15s ease;
        }

        .status-toggle button.active {
          background-color: var(--accent-primary);
          color: white;
        }
      `}</style>

      <div className="task-board-root h-full bg-background flex flex-col overflow-hidden" style={{ backgroundColor: 'var(--bg-primary)', color: 'var(--text-primary)' }}>
        {/* Header */}
        <div style={{ borderBottom: '1px solid var(--border-color)', padding: '4px 16px' }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
            <div></div>
          </div>
        </div>

        {/* Filters */}
        <div style={{ padding: '8px 16px', borderBottom: '1px solid var(--border-color)' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
            <span style={{ fontSize: '12px', fontWeight: 'bold', color: 'var(--text-secondary)', paddingRight: '8px' }}>Group by:</span>
            
            <FilterChip>Status</FilterChip>
            <FilterChip>Projects</FilterChip>
            <FilterChip>Date Created</FilterChip>
            <FilterChip>Assignee</FilterChip>
            <FilterChip>Created by</FilterChip>

            <div style={{ marginLeft: 'auto', display: 'flex', alignItems: 'center', gap: '8px' }}>
              <input 
                type="text"
                placeholder="Search tasks..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                style={{
                  padding: '6px 12px',
                  border: '1px solid var(--border-color)',
                  borderRadius: '4px',
                  fontSize: '14px',
                  width: '200px',
                  backgroundColor: 'var(--bg-primary)',
                  color: 'var(--text-primary)'
                }}
              />
              
              <button className="btn btn-secondary" disabled>+ Add Note</button>
              <button className="btn btn-secondary" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                  <path d="m5,16-3,-3 2,-2 3,3"></path>
                  <path d="m13,13 3,-3"></path>
                </svg>
                Screen Clip
              </button>
              <button className="btn btn-secondary" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                  <path d="m9,9 5,5"></path>
                  <path d="m15,9-5,5"></path>
                </svg>
                ToDo
              </button>
              
              <label style={{ display: 'flex', alignItems: 'center', gap: '6px', fontSize: '14px' }}>
                <input 
                  type="checkbox"
                  checked={showClosed}
                  onChange={(e) => setShowClosed(e.target.checked)}
                />
                Completed
              </label>
              
              <button 
                className="btn btn-primary"
                onClick={() => setIsCreateModalOpen(true)}
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Task
              </button>
            </div>
          </div>
        </div>

        {/* Content */}
        <div style={{ flex: 1, padding: '16px', backgroundColor: 'var(--bg-primary)', overflowY: 'auto' }}>
          <div style={{ maxWidth: '100%', margin: '0 auto' }}>
            {Object.entries(groupedTasks).map(([groupName, groupTasks]) => (
              <GroupSection
                key={groupName}
                groupName={groupName}
                groupTasks={groupTasks}
                isCollapsed={collapsedGroups[groupName]}
                onToggle={() => toggleGroup(groupName)}
                onTaskClick={handleTaskClick}
                formatDate={formatDate}
              />
            ))}
          </div>
        </div>

        <TaskDrawer
          isOpen={isDrawerOpen}
          selectedTask={selectedTask}
          editData={editData}
          setEditData={setEditData}
          mockUsers={mockUsers}
          onClose={handleDrawerClose}
          onUpdate={handleTaskUpdate}
          formatDate={formatDate}
          getAvatarColor={getAvatarColor}
        />

        <TaskCreateModal
          isOpen={isCreateModalOpen}
          onClose={() => setIsCreateModalOpen(false)}
          onCreate={handleCreateTask}
        />
      </div>
    </AppLayout>
  );
}
