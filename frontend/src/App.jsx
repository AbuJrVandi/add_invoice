import { Navigate, Route, Routes } from 'react-router-dom';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import InvoiceList from './pages/InvoiceList';
import CreateInvoice from './pages/CreateInvoice';
import PdfSettings from './pages/PdfSettings';
import ProtectedLayout from './layouts/ProtectedLayout';
import { useAuth } from './context/AuthContext';

function App() {
  const { token, ready } = useAuth();

  if (!ready) {
    return <div className="centered-page loading-screen">Loading workspace...</div>;
  }

  return (
    <Routes>
      <Route path="/login" element={token ? <Navigate to="/" replace /> : <Login />} />

      <Route element={<ProtectedLayout />}>
        <Route path="/" element={<Dashboard />} />
        <Route path="/invoices" element={<InvoiceList />} />
        <Route path="/invoices/create" element={<CreateInvoice />} />
        <Route path="/pdf-settings" element={<PdfSettings />} />
      </Route>

      <Route path="*" element={<Navigate to={token ? '/' : '/login'} replace />} />
    </Routes>
  );
}

export default App;
