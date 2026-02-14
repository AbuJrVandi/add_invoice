import { useState } from 'react';
import { Navigate, NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

function ProtectedLayout() {
  const { token, logout, user, ready } = useAuth();
  const [menuOpen, setMenuOpen] = useState(false);

  if (!ready) {
    return <div className="content loading-screen">Loading workspace...</div>;
  }

  if (!token) {
    return <Navigate to="/login" replace />;
  }

  return (
    <div className="app-shell">
      <aside className={`sidebar ${menuOpen ? 'open' : ''}`}>
        <div className="sidebar-head">
          <h1>Invoice System</h1>
          <button
            type="button"
            className="sidebar-toggle"
            aria-expanded={menuOpen}
            onClick={() => setMenuOpen((prev) => !prev)}
          >
            {menuOpen ? 'Close' : 'Menu'}
          </button>
        </div>

        <nav className="sidebar-nav">
          <NavLink to="/" onClick={() => setMenuOpen(false)}>Dashboard</NavLink>
          <NavLink to="/invoices" onClick={() => setMenuOpen(false)}>Invoices</NavLink>
          <NavLink to="/invoices/create" onClick={() => setMenuOpen(false)}>Create Invoice</NavLink>
          <NavLink to="/pdf-settings" onClick={() => setMenuOpen(false)}>PDF Settings</NavLink>
        </nav>

        <div className="sidebar-footer">
          <p>{user?.email}</p>
          <button type="button" onClick={logout} className="button button-outline">Logout</button>
        </div>
      </aside>

      <main className="content">
        <Outlet />
      </main>
    </div>
  );
}

export default ProtectedLayout;
