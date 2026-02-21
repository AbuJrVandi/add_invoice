import { Navigate, Route, Routes } from 'react-router-dom';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import OwnerDashboard from './pages/OwnerDashboard';
import OwnerAdminCredentials from './pages/OwnerAdminCredentials';
import OwnerAdminActivity from './pages/OwnerAdminActivity';
import InvoiceList from './pages/InvoiceList';
import CreateInvoice from './pages/CreateInvoice';
import Payments from './pages/Payments';
import ReceiptViewer from './pages/ReceiptViewer';
import PdfSettings from './pages/PdfSettings';
import InvoicePdfViewer from './pages/InvoicePdfViewer';
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
        <Route path="/" element={<HomeRedirect />} />
        <Route path="/owner/dashboard" element={<OwnerOnly><OwnerDashboard /></OwnerOnly>} />
        <Route path="/owner/operations/invoices" element={<OwnerOnly><InvoiceList mode="owner" /></OwnerOnly>} />
        <Route path="/owner/operations/invoices/create" element={<OwnerOnly><CreateInvoice mode="owner" /></OwnerOnly>} />
        <Route path="/owner/operations/payments" element={<OwnerOnly><Payments mode="owner" /></OwnerOnly>} />
        <Route path="/owner/pdf-settings" element={<OwnerOnly><PdfSettings /></OwnerOnly>} />
        <Route path="/owner/admin-activity" element={<OwnerOnly><OwnerAdminActivity /></OwnerOnly>} />
        <Route path="/owner/admin-credentials" element={<OwnerOnly><OwnerAdminCredentials /></OwnerOnly>} />
        <Route path="/invoices" element={<AdminOnly><InvoiceList /></AdminOnly>} />
        <Route path="/invoices/create" element={<AdminOnly><CreateInvoice /></AdminOnly>} />
        <Route path="/invoices/:invoiceId/view" element={<InvoicePdfViewer />} />
        <Route path="/payments" element={<AdminOnly><Payments /></AdminOnly>} />
        <Route path="/payments/:paymentId/receipt" element={<ReceiptViewer />} />
      </Route>

      <Route path="*" element={<Navigate to={token ? '/' : '/login'} replace />} />
    </Routes>
  );
}

function HomeRedirect() {
  const { user } = useAuth();
  if (user?.role === 'owner') {
    return <Navigate to="/owner/dashboard" replace />;
  }
  return <Dashboard />;
}

function AdminOnly({ children }) {
  const { user } = useAuth();
  if (user?.role === 'owner') {
    return <Navigate to="/owner/dashboard" replace />;
  }
  return children;
}

function OwnerOnly({ children }) {
  const { user } = useAuth();
  if (user?.role !== 'owner') {
    return <Navigate to="/" replace />;
  }
  return children;
}

export default App;
