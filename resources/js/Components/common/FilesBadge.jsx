import React from 'react';

export default function FilesBadge({ count = 0 }) {
  if (count <= 0) return null;
  
  return (
    <span className="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded">
      📎 {count}
    </span>
  );
}
