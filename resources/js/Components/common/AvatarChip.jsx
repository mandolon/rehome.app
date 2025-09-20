import React from 'react';

export default function AvatarChip({ user, size = 'md' }) {
  if (!user) return null;
  
  return (
    <div className={`inline-flex items-center justify-center rounded-full bg-blue-500 text-white font-medium ${
      size === 'sm' ? 'w-6 h-6 text-xs' : size === 'lg' ? 'w-10 h-10 text-sm' : 'w-8 h-8 text-xs'
    }`}>
      {user.initials || user.name?.charAt(0) || '?'}
    </div>
  );
}
