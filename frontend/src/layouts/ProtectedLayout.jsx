import { Navigate, NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

function ProtectedLayout() {
  const { token, logout, user } = useAuth();

  if (!token) {
    return <Navigate to="/login" replace />;
  }

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <h1>Invoice System</h1>
        <nav>
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
