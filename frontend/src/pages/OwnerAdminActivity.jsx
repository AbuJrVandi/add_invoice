import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import useResponsive from '../hooks/useResponsive';

const PAYMENT_METHOD_LABELS = {
  cash: 'Cash',
  transfer: 'Transfer',
  mobile_money: 'Mobile Money',
  card: 'Card',
};

function formatCurrency(value) {
  return `NLe ${new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(value || 0))}`;
}

function formatDateTime(value) {
  if (!value) {
    return '-';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '-';
  }

  return date.toLocaleString();
}

function formatShortDate(value) {
  if (!value) {
    return '-';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '-';
  }

  return date.toLocaleDateString();
}

function EmptyAdminState() {
  return (
    <div className="panel owner-admin-activity-empty">
      <h3>No admin accounts found</h3>
      <p>Create an admin account first to view invoice and receipt activity.</p>
      <Link className="button" to="/owner/admin-credentials">Go to Admin Credentials</Link>
    </div>
  );
}

function Pager({ paginator, onPrev, onNext }) {
  const currentPage = Number(paginator?.current_page || 1);
  const lastPage = Number(paginator?.last_page || 1);
  const total = Number(paginator?.total || 0);

  return (
    <div className="owner-admin-activity-pager">
      <span>
        Page {currentPage} of {lastPage} · {total} records
      </span>
      <div>
        <button
          type="button"
          className="button btn-sm button-outline"
          onClick={onPrev}
          disabled={currentPage <= 1}
        >
          Prev
        </button>
        <button
          type="button"
          className="button btn-sm button-outline"
          onClick={onNext}
          disabled={currentPage >= lastPage}
        >
          Next
        </button>
      </div>
    </div>
  );
}

export default function OwnerAdminActivity() {
  const { isMobile } = useResponsive();

  const [admins, setAdmins] = useState([]);
  const [loadingAdmins, setLoadingAdmins] = useState(true);
  const [loadingDetail, setLoadingDetail] = useState(false);
  const [error, setError] = useState('');
  const [detailError, setDetailError] = useState('');
  const [selectedAdminId, setSelectedAdminId] = useState(null);
  const [invoicesPage, setInvoicesPage] = useState(1);
  const [receiptsPage, setReceiptsPage] = useState(1);
  const [detail, setDetail] = useState({
    admin: null,
    invoices: { data: [], current_page: 1, last_page: 1, total: 0 },
    receipts: { data: [], current_page: 1, last_page: 1, total: 0 },
  });

  const fetchAdmins = useCallback(async () => {
    setLoadingAdmins(true);
    setError('');

    try {
      const response = await api.get('/owner/admin-activity');
      const rows = Array.isArray(response.data?.data) ? response.data.data : [];
      setAdmins(rows);
    } catch (requestError) {
      setError(requestError?.response?.data?.message || 'Unable to load admin activity overview.');
      setAdmins([]);
    } finally {
      setLoadingAdmins(false);
    }
  }, []);

  useEffect(() => {
    fetchAdmins();
  }, [fetchAdmins]);

  useEffect(() => {
    if (admins.length === 0) {
      setSelectedAdminId(null);
      return;
    }

    const stillExists = admins.some((admin) => admin.id === selectedAdminId);
    if (!stillExists) {
      setSelectedAdminId(admins[0].id);
      setInvoicesPage(1);
      setReceiptsPage(1);
    }
  }, [admins, selectedAdminId]);

  const fetchDetail = useCallback(async (adminId, nextInvoicesPage, nextReceiptsPage) => {
    if (!adminId) {
      return;
    }

    setLoadingDetail(true);
    setDetailError('');

    try {
      const response = await api.get(`/owner/admin-activity/${adminId}`, {
        params: {
          invoices_page: nextInvoicesPage,
          receipts_page: nextReceiptsPage,
          invoices_per_page: 10,
          receipts_per_page: 10,
        },
      });

      const payload = response.data || {};

      setDetail({
        admin: payload.admin || null,
        invoices: payload.invoices || { data: [], current_page: 1, last_page: 1, total: 0 },
        receipts: payload.receipts || { data: [], current_page: 1, last_page: 1, total: 0 },
      });
    } catch (requestError) {
      setDetailError(requestError?.response?.data?.message || 'Unable to load selected admin activity.');
      setDetail({
        admin: null,
        invoices: { data: [], current_page: 1, last_page: 1, total: 0 },
        receipts: { data: [], current_page: 1, last_page: 1, total: 0 },
      });
    } finally {
      setLoadingDetail(false);
    }
  }, []);

  useEffect(() => {
    fetchDetail(selectedAdminId, invoicesPage, receiptsPage);
  }, [fetchDetail, invoicesPage, receiptsPage, selectedAdminId]);

  const selectedAdminSummary = useMemo(
    () => admins.find((admin) => admin.id === selectedAdminId) || null,
    [admins, selectedAdminId]
  );

  const chooseAdmin = (adminId) => {
    setSelectedAdminId(adminId);
    setInvoicesPage(1);
    setReceiptsPage(1);
  };

  return (
    <div className="owner-admin-activity-page stacked">
      <section className="panel owner-admin-activity-header">
        <div>
          <h2>Admin Activity Center</h2>
          <p>Owner-only read access to each managed admin's invoices and receipts.</p>
        </div>
        <button type="button" className="button button-outline" onClick={fetchAdmins}>
          Refresh Admin List
        </button>
      </section>

      {error ? <p className="error">{error}</p> : null}

      {loadingAdmins ? (
        <div className="loading">Loading admin activity...</div>
      ) : admins.length === 0 ? (
        <EmptyAdminState />
      ) : (
        <section className="owner-admin-activity-grid">
          <aside className="panel owner-admin-activity-rail">
            <div className="owner-admin-activity-rail-head">
              <h3>Managed Admins</h3>
              <span>{admins.length}</span>
            </div>

            <div className="owner-admin-activity-rail-list">
              {admins.map((admin) => {
                const isActive = admin.id === selectedAdminId;
                const metrics = admin.metrics || {};

                return (
                  <button
                    key={admin.id}
                    type="button"
                    className={`owner-admin-activity-admin${isActive ? ' active' : ''}`}
                    onClick={() => chooseAdmin(admin.id)}
                  >
                    <div className="owner-admin-activity-admin-top">
                      <strong>{admin.name}</strong>
                      <span>{admin.is_active ? 'Active' : 'Inactive'}</span>
                    </div>
                    <p>{admin.email}</p>
                    <div className="owner-admin-activity-admin-metrics">
                      <span>{metrics.total_invoices || 0} invoices</span>
                      <span>{metrics.total_receipts || 0} receipts</span>
                    </div>
                  </button>
                );
              })}
            </div>
          </aside>

          <div className="owner-admin-activity-main">
            <section className="panel owner-admin-activity-summary">
              <div>
                <h3>{detail.admin?.name || selectedAdminSummary?.name || '-'}</h3>
                <p>{detail.admin?.email || selectedAdminSummary?.email || '-'}</p>
              </div>

              <div className="owner-admin-activity-kpis">
                <article>
                  <span>Total Invoices</span>
                  <strong>{selectedAdminSummary?.metrics?.total_invoices || 0}</strong>
                </article>
                <article>
                  <span>Total Receipts</span>
                  <strong>{selectedAdminSummary?.metrics?.total_receipts || 0}</strong>
                </article>
                <article>
                  <span>Outstanding</span>
                  <strong>{formatCurrency(selectedAdminSummary?.metrics?.outstanding_balance || 0)}</strong>
                </article>
                <article>
                  <span>Last Invoice</span>
                  <strong>{formatShortDate(selectedAdminSummary?.metrics?.last_invoice_created_at)}</strong>
                </article>
              </div>
            </section>

            {detailError ? <p className="error">{detailError}</p> : null}
            {loadingDetail ? (
              <div className="loading">Loading admin records...</div>
            ) : (
              <>
                <section className="panel owner-admin-activity-block">
                  <div className="owner-admin-activity-block-head">
                    <h4>Invoices</h4>
                    <span>View and download only</span>
                  </div>

                  <div className="table-wrap">
                    <table className="table owner-admin-activity-table">
                      <thead>
                        <tr>
                          <th>Invoice #</th>
                          <th>Customer</th>
                          {!isMobile ? <th>Status</th> : null}
                          <th>Total</th>
                          {!isMobile ? <th>Created</th> : null}
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        {Array.isArray(detail.invoices?.data) && detail.invoices.data.length > 0 ? (
                          detail.invoices.data.map((invoice) => (
                            <tr key={invoice.id}>
                              <td>{invoice.invoice_number}</td>
                              <td>
                                {invoice.customer_name}
                                {!isMobile && invoice.organization ? (
                                  <span className="owner-ops-subtext">{invoice.organization}</span>
                                ) : null}
                              </td>
                              {!isMobile ? (
                                <td>
                                  <span className={`owner-ops-chip ${String(invoice.status) === 'completed' ? 'good' : 'neutral'}`}>
                                    {invoice.status}
                                  </span>
                                </td>
                              ) : null}
                              <td>{formatCurrency(invoice.total)}</td>
                              {!isMobile ? <td>{formatDateTime(invoice.created_at)}</td> : null}
                              <td>
                                <Link
                                  className="owner-ops-action-link"
                                  to={`/invoices/${invoice.id}/view`}
                                  state={{ backTo: '/owner/admin-activity' }}
                                >
                                  View PDF
                                </Link>
                              </td>
                            </tr>
                          ))
                        ) : (
                          <tr>
                            <td colSpan={isMobile ? 4 : 6} className="empty-state">
                              No invoices created by this admin.
                            </td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                  </div>

                  <Pager
                    paginator={detail.invoices}
                    onPrev={() => setInvoicesPage((prev) => Math.max(prev - 1, 1))}
                    onNext={() => setInvoicesPage((prev) => prev + 1)}
                  />
                </section>

                <section className="panel owner-admin-activity-block">
                  <div className="owner-admin-activity-block-head">
                    <h4>Receipts</h4>
                    <span>View receipt and download PDF</span>
                  </div>

                  <div className="table-wrap">
                    <table className="table owner-admin-activity-table">
                      <thead>
                        <tr>
                          <th>Receipt #</th>
                          {!isMobile ? <th>Invoice #</th> : null}
                          {!isMobile ? <th>Method</th> : null}
                          <th>Amount</th>
                          {!isMobile ? <th>Paid At</th> : null}
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        {Array.isArray(detail.receipts?.data) && detail.receipts.data.length > 0 ? (
                          detail.receipts.data.map((receipt) => (
                            <tr key={receipt.id}>
                              <td>{receipt.receipt_number}</td>
                              {!isMobile ? <td>{receipt.invoice?.invoice_number || '-'}</td> : null}
                              {!isMobile ? <td>{PAYMENT_METHOD_LABELS[receipt.payment_method] || receipt.payment_method || '-'}</td> : null}
                              <td>{formatCurrency(receipt.amount_paid)}</td>
                              {!isMobile ? <td>{formatDateTime(receipt.paid_at)}</td> : null}
                              <td>
                                <div className="owner-admin-activity-actions">
                                  <Link
                                    className="owner-ops-action-link"
                                    to={`/payments/${receipt.id}/receipt`}
                                    state={{ backTo: '/owner/admin-activity' }}
                                  >
                                    View
                                  </Link>
                                  {receipt.receipt_pdf_url ? (
                                    <a
                                      className="owner-ops-action-link"
                                      href={receipt.receipt_pdf_url}
                                      target="_blank"
                                      rel="noreferrer"
                                    >
                                      Download
                                    </a>
                                  ) : (
                                    <span className="owner-ops-subtext">No PDF</span>
                                  )}
                                </div>
                              </td>
                            </tr>
                          ))
                        ) : (
                          <tr>
                            <td colSpan={isMobile ? 3 : 6} className="empty-state">
                              No receipts created by this admin.
                            </td>
                          </tr>
                        )}
                      </tbody>
                    </table>
                  </div>

                  <Pager
                    paginator={detail.receipts}
                    onPrev={() => setReceiptsPage((prev) => Math.max(prev - 1, 1))}
                    onNext={() => setReceiptsPage((prev) => prev + 1)}
                  />
                </section>
              </>
            )}
          </div>
        </section>
      )}
    </div>
  );
}
