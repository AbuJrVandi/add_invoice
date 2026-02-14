import { Navigate, NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

function ProtectedLayout() {
  const { token, logout, user, ready } = useAuth();

  if (!ready) {
    return <div className="content loading-screen">Loading workspace...</div>;
  }

  if (!token) {
    return <Navigate to="/login" replace />;
  }

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <div className="sidebar-head">
          <h1>Invoice System</h1>
          <button type="button" onClick={logout} className="sidebar-logout">Log out</button>
        </div>

        <nav className="sidebar-nav">
          <NavLink to="/">Dashboard</NavLink>
          <NavLink to="/invoices">Invoices</NavLink>
          <NavLink to="/invoices/create">Create Invoice</NavLink>
          <NavLink to="/pdf-settings">PDF Settings</NavLink>
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
