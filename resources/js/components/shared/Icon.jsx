import React from 'react';

const paths = {
  home: (
    <>
      <path d="M3 10.5L12 3l9 7.5" />
      <path d="M5 9.9V21h14V9.9" />
    </>
  ),
  users: (
    <>
      <path d="M17 21v-2a4 4 0 0 0-3-3.87" />
      <path d="M7 21v-2a4 4 0 0 1 3-3.87" />
      <circle cx="12" cy="7" r="4" />
    </>
  ),
  departments: (
    <>
      <path d="M3 21h18" />
      <path d="M5 21V8l7-5 7 5v13" />
    </>
  ),
  branches: (
    <>
      <path d="M4 20h16" />
      <path d="M6 20V8l6-4 6 4v12" />
    </>
  ),
  calendar: (
    <>
      <path d="M8 2v4" />
      <path d="M16 2v4" />
      <rect x="3" y="5" width="18" height="16" rx="2" />
    </>
  ),
  employees: (
    <>
      <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
      <circle cx="8.5" cy="7" r="4" />
    </>
  ),
  attendance: (
    <>
      <circle cx="12" cy="12" r="9" />
      <path d="M12 7v5l3.2 2" />
    </>
  ),
  leave: (
    <>
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
    </>
  ),
  payroll: (
    <>
      <rect x="2" y="5" width="20" height="14" rx="2" />
      <path d="M2 10h20" />
    </>
  ),
  communication: (
    <>
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
    </>
  ),
  notifications: (
    <>
      <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" />
      <path d="M13.73 21a2 2 0 0 1-3.46 0" />
    </>
  ),
  reports: (
    <>
      <path d="M3 3v18h18" />
      <path d="M7 14l4-4 3 3 5-6" />
    </>
  ),
  settings: (
    <>
      <circle cx="12" cy="12" r="3" />
      <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83" />
    </>
  ),
  default: (
    <>
      <circle cx="12" cy="12" r="10" />
    </>
  ),
};

export const ICON_MAP = Object.keys(paths);

export default function Icon({ name = 'default', size = 20, className = '', strokeWidth = 2, ...rest }) {
  const content = paths[name] ?? paths.default;
  return (
    <svg
      className={className}
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={strokeWidth}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      {...rest}
    >
      {content}
    </svg>
  );
}

