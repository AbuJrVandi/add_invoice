import { useEffect, useState } from 'react';
import { Link, Navigate, NavLink, Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import useResponsive from '../hooks/useResponsive';

const PAGE_TITLES = {
  '/': 'Dashboard',
  '/owner/dashboard': 'Owner Analytics',
  '/owner/admin-credentials': 'Admin Credentials',
  '/invoices': 'Invoices',
  '/invoices/create': 'Create Invoice',
  '/payments': 'Payments',
  '/pdf-settings': 'PDF Settings',
};

function resolvePageTitle(pathname) {
  if (pathname.includes('/invoices/') && pathname.includes('/view')) {
    return 'Invoice PDF';
  }
  if (pathname.includes('/payments/') && pathname.includes('/receipt')) {
    return 'Receipt';
  }

  return PAGE_TITLES[pathname] || 'Workspace';
}

function navConfigForRole(role) {
  if (role === 'owner') {
    return {
      sideNav: [
        { to: '/owner/dashboard', label: 'Analytics', icon: '📈', exact: true },
        { to: '/owner/admin-credentials', label: 'Admin Access', icon: '🔐' },
      ],
      mobileNav: [
        { to: '/owner/dashboard', label: 'Home', icon: '📈', exact: true },
        { to: '/owner/admin-credentials', label: 'Admins', icon: '🔐' },
      ],
    };
  }

  return {
    sideNav: [
      { to: '/', label: 'Overview', icon: '📊', exact: true },
      { to: '/invoices', label: 'Invoices', icon: '📋' },
      { to: '/payments', label: 'Payments', icon: '💰' },
      { to: '/pdf-settings', label: 'PDF Settings', icon: '⚙️' },
    ],
    mobileNav: [
      { to: '/', label: 'Home', icon: '🏠', exact: true },
      { to: '/invoices', label: 'Invoices', icon: '📋' },
      { to: '/payments', label: 'Payments', icon: '💳' },
      { to: '/pdf-settings', label: 'Settings', icon: '⚙️' },
    ],
  };
}

export default function ProtectedLayout() {
  const { token, logout, user, ready } = useAuth();
  const location = useLocation();
  const { isMobile, isTablet, isDesktop } = useResponsive();

  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

  useEffect(() => {
    if (isTablet) {
      setSidebarCollapsed(true);
    }
    if (isDesktop) {
      setSidebarCollapsed(false);
    }
  }, [isTablet, isDesktop]);

  if (!ready) {
    return <div className="content loading-screen">Loading workspace...</div>;
  }

  if (!token) {
    return <Navigate to="/login" replace />;
  }

  const pageTitle = resolvePageTitle(location.pathname);
  const userInitial = user?.email ? user.email.charAt(0).toUpperCase() : 'A';
  const userName = user?.name || user?.role || 'User';

  const nav = navConfigForRole(user?.role);
  const sideNav = nav.sideNav;
  const mobileNav = nav.mobileNav;

  if (isMobile) {
    return (
      <div className="mobile-app-shell">
        <header className="mobile-topbar">
          <div className="mobile-topbar-title">
            <h1>{pageTitle}</h1>
            <p>{user?.email}</p>
          </div>
          <button type="button" className="mobile-logout-icon" onClick={logout} aria-label="Sign out">
            ⎋
          </button>
        </header>

        <main className="mobile-content">
          <Outlet />
        </main>

        {user?.role !== 'owner' && location.pathname !== '/invoices/create' ? (
          <Link to="/invoices/create" className="mobile-fab" aria-label="Create Invoice">
            <span aria-hidden="true">＋</span>
            <strong>Create Invoice</strong>
          </Link>
        ) : null}

        <nav className="mobile-bottom-nav" aria-label="Mobile primary navigation">
          {mobileNav.map((item) => (
            <NavLink key={item.to} to={item.to} end={item.exact} className={({ isActive }) => (isActive ? 'active' : '')}>
              <span className="mobile-nav-icon" aria-hidden="true">{item.icon}</span>
              <span>{item.label}</span>
            </NavLink>
          ))}
        </nav>
      </div>
    );
  }

  return (
    <div className={`app-shell ${isTablet ? 'tablet-shell' : 'desktop-shell'}${sidebarCollapsed ? ' sidebar-collapsed' : ''}`}>
      <aside className="sidebar" id="sidebar">
        <div className="sidebar-brand">
          <img src="/logo.png" alt="CIRQON" className="sidebar-brand-logo" />
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
        </div>

        <nav className="sidebar-nav" id="sidebar-nav">
          {!sidebarCollapsed && <div className="sidebar-nav-section">Main</div>}
          {sideNav.map((item) => (
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
            {!sidebarCollapsed && <div className="sidebar-user-email">{user?.email}</div>}
          </div>
          <button type="button" onClick={logout} className="sidebar-logout-btn" id="sidebar-logout">
            {sidebarCollapsed ? '🚪' : '← Sign Out'}
          </button>
        </div>
      </aside>

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

      <main className="content" id="main-content">
        <Outlet />
      </main>
    </div>
  );
}
