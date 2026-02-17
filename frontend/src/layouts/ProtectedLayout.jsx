import { useState } from 'react';
import { Navigate, NavLink, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const PAGE_TITLES = {
  '/': 'Dashboard',
  '/owner/dashboard': 'Owner Analytics',
  '/invoices': 'Invoices',
  '/invoices/create': 'Create Invoice',
  '/payments': 'Payments',
  '/payments/:paymentId/receipt': 'Receipt',
  '/pdf-settings': 'PDF Settings',
};

function ProtectedLayout() {
  const { token, logout, user, ready } = useAuth();
  const location = useLocation();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

  // Dynamic navigation based on role
  const navItems = user?.role === 'owner'
    ? [{ to: '/owner/dashboard', label: 'Analytics', icon: '📈', exact: true }]
    : [
      { to: '/', label: 'Overview', icon: '📊', exact: true },
      { to: '/invoices', label: 'Invoices', icon: '📋' },
      { to: '/payments', label: 'Payments', icon: '💰' },
      { to: '/pdf-settings', label: 'PDF Settings', icon: '⚙️' },
    ];

  if (!ready) {
    return <div className="content loading-screen">Loading workspace...</div>;
  }

  if (!token) {
    return <Navigate to="/login" replace />;
  }

  const userInitial = user?.email ? user.email.charAt(0).toUpperCase() : 'A';
  const userName = user?.name || user?.role || 'User';

  // Determine page title
  let pageTitle = PAGE_TITLES[location.pathname] || 'Page';
  if (location.pathname.includes('/invoices/') && location.pathname.includes('/view')) {
    pageTitle = 'Invoice PDF';
  }
  if (location.pathname.includes('/payments/') && location.pathname.includes('/receipt')) {
    pageTitle = 'Receipt';
  }

  return (
    <div className={`app-shell${sidebarCollapsed ? ' sidebar-collapsed' : ''}`}>
      {/* Sidebar */}
      <aside className="sidebar" id="sidebar">
        <div className="sidebar-brand">
          <img src="/set.png" alt="CIRQON" className="sidebar-brand-logo" />
          {!sidebarCollapsed && (
            <div className="sidebar-brand-text">
              <span className="sidebar-brand-name">CIRQON</span>
              <span className="sidebar-brand-sub">Electronics</span>
            </div>
          )}
          <button
            type="button"
            className="sidebar-toggle"
            onClick={() => setSidebarCollapsed((prev) => !prev)}
            aria-label={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
          >
            {sidebarCollapsed ? '▶' : '◀'}
          </button>
          <button type="button" onClick={logout} className="mobile-logout-btn">
            Log Out
          </button>
        </div>

        <nav className="sidebar-nav" id="sidebar-nav">
          {!sidebarCollapsed && <div className="sidebar-nav-section">Main</div>}
          {navItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.exact}
              title={item.label}
              className={({ isActive }) => (isActive ? 'active' : '')}
            >
              <span className="nav-icon">{item.icon}</span>
              {!sidebarCollapsed && <span className="nav-label">{item.label}</span>}
            </NavLink>
          ))}
        </nav>

        <div className="sidebar-footer">
          <div className="sidebar-user-info">
            <div className="sidebar-user-avatar">{userInitial}</div>
            {!sidebarCollapsed && (
              <div className="sidebar-user-email">{user?.email}</div>
            )}
          </div>
          <button type="button" onClick={logout} className="sidebar-logout-btn" id="sidebar-logout">
            {sidebarCollapsed ? '🚪' : '← Sign Out'}
          </button>
        </div>
      </aside>

      {/* Top Navbar */}
      <header className="top-navbar" id="top-navbar">
        <h1 className="navbar-page-title">{pageTitle}</h1>

        <div className="navbar-right">
          <button type="button" className="navbar-icon-btn" title="Notifications" id="notifications-btn">
            🔔
            <span className="navbar-notification-dot"></span>
          </button>

          <button type="button" className="navbar-user-btn" id="user-menu-btn">
            <div className="navbar-user-avatar">{userInitial}</div>
            <span className="navbar-user-name">{userName}</span>
          </button>
        </div>
      </header>

      {/* Main Content */}
      <main className="content" id="main-content">
        <Outlet />
      </main>
    </div>
  );
}

export default ProtectedLayout;
