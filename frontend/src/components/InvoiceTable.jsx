import { useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../services/api';
import useResponsive from '../hooks/useResponsive';

function formatDate(value) {
  if (!value) {
    return '-';
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return value;
  }

  return parsed.toLocaleDateString('en-GB');
}

function InvoiceTable({
  invoices,
  loading,
  hasMore = false,
  onLoadMore = () => {},
  onDataChanged = () => {},
  createPath = '/invoices/create',
  paymentPath = '/payments',
}) {
  const navigate = useNavigate();
  const { isMobile, isTablet } = useResponsive();
  const [activeInvoice, setActiveInvoice] = useState(null);
  const [deleteError, setDeleteError] = useState('');
  const [deletingInvoiceId, setDeletingInvoiceId] = useState(null);

  const rows = useMemo(() => (Array.isArray(invoices) ? invoices : []), [invoices]);

  if (loading) return <div className="loading">Loading invoices...</div>;

  const handlePay = (invoice, event) => {
    if (event) {
      event.stopPropagation();
      event.preventDefault();
    }
    navigate(`${paymentPath}?invoice=${invoice.id}`);
  };

  const handleEdit = (invoice, event) => {
    if (event) {
      event.stopPropagation();
      event.preventDefault();
    }
    navigate(`${createPath}?clone=${invoice.id}`);
  };

  const handleDelete = async (invoice, event) => {
    if (event) {
      event.stopPropagation();
      event.preventDefault();
    }

    const canDelete = window.confirm(`Delete invoice ${invoice.invoice_number}? This action cannot be undone.`);
    if (!canDelete) return;

    setDeleteError('');
    setDeletingInvoiceId(invoice.id);

    try {
      await api.delete(`/invoices/${invoice.id}`);
      if (activeInvoice?.id === invoice.id) {
        setActiveInvoice(null);
      }
      onDataChanged();
    } catch (error) {
      setDeleteError(error?.response?.data?.message || 'Unable to delete this invoice.');
    } finally {
      setDeletingInvoiceId(null);
    }
  };

  const renderMobile = () => (
    <>
      {rows.length === 0 ? (
        <div className="empty-state">No invoices match your search.</div>
      ) : (
        <div className="invoice-mobile-list">
          {rows.map((inv) => (
            <div className="invoice-mobile-swipe" key={inv.id}>
              <article
                className="invoice-mobile-card"
                onClick={() => {
                  setDeleteError('');
                  setActiveInvoice(inv);
                }}
                role="button"
                tabIndex={0}
                onKeyDown={(event) => {
                  if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    setDeleteError('');
                    setActiveInvoice(inv);
                  }
                }}
              >
                <div className="invoice-mobile-row-top">
                  <strong>{inv.invoice_number}</strong>
                  <span className={`status-badge status-${String(inv.status || 'pending').toLowerCase()}`}>
                    {inv.status || 'Pending'}
                  </span>
                </div>
                <p>{inv.customer_name}</p>
                <div className="invoice-mobile-kpis">
                  <div>
                    <small>Amount</small>
                    <strong>NLe {Number(inv.total).toFixed(2)}</strong>
                  </div>
                  <div>
                    <small>Due date</small>
                    <strong>{formatDate(inv.due_date || inv.invoice_date)}</strong>
                  </div>
                  <div>
                    <small>Balance</small>
                    <strong className={Number(inv.balance_remaining) > 0 ? 'text-danger' : 'text-success'}>
                      NLe {Number(inv.balance_remaining).toFixed(2)}
                    </strong>
                  </div>
                </div>
                <button
                  type="button"
                  className="button button-outline"
                  onClick={(event) => {
                    event.stopPropagation();
                    setDeleteError('');
                    setActiveInvoice(inv);
                  }}
                >
                  Open Actions
                </button>
              </article>

              <div className="invoice-mobile-swipe-actions">
                <button type="button" className="swipe-action pay" onClick={(event) => handlePay(inv, event)}>Pay</button>
                <button type="button" className="swipe-action edit" onClick={(event) => handleEdit(inv, event)}>Edit</button>
                <button type="button" className="swipe-action delete" onClick={(event) => handleDelete(inv, event)}>
                  {deletingInvoiceId === inv.id ? '...' : 'Delete'}
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {hasMore ? (
        <button type="button" className="button button-outline mobile-load-more" onClick={onLoadMore}>
          Load More Invoices
        </button>
      ) : null}

      {activeInvoice ? (
        <div className="mobile-drawer-overlay" onClick={() => setActiveInvoice(null)}>
          <section className="mobile-full-drawer" onClick={(event) => event.stopPropagation()}>
            <header className="mobile-full-drawer-head">
              <div>
                <h3>{activeInvoice.invoice_number}</h3>
                <p>{activeInvoice.customer_name}</p>
              </div>
              <button type="button" className="close-btn" onClick={() => setActiveInvoice(null)} aria-label="Close">
                &times;
              </button>
            </header>

            <div className="mobile-full-drawer-body">
              <div className="drawer-detail-row"><span>Status</span><strong>{activeInvoice.status || 'Pending'}</strong></div>
              <div className="drawer-detail-row"><span>Organization</span><strong>{activeInvoice.organization || '-'}</strong></div>
              <div className="drawer-detail-row"><span>Total</span><strong>NLe {Number(activeInvoice.total).toFixed(2)}</strong></div>
              <div className="drawer-detail-row"><span>Balance</span><strong>NLe {Number(activeInvoice.balance_remaining).toFixed(2)}</strong></div>
              <div className="drawer-detail-row"><span>Due date</span><strong>{formatDate(activeInvoice.due_date || activeInvoice.invoice_date)}</strong></div>

              {deleteError ? <p className="error">{deleteError}</p> : null}
            </div>

            <footer className="mobile-sticky-footer">
              <button type="button" className="button button-outline" onClick={() => navigate(`/invoices/${activeInvoice.id}/view`)}>
                View PDF
              </button>
              <button type="button" className="button button-outline" onClick={(event) => handleEdit(activeInvoice, event)}>
                Edit
              </button>
              <button type="button" className="button" onClick={(event) => handlePay(activeInvoice, event)}>
                Pay
              </button>
              <button
                type="button"
                className="button button-danger"
                onClick={(event) => handleDelete(activeInvoice, event)}
                disabled={deletingInvoiceId === activeInvoice.id}
              >
                {deletingInvoiceId === activeInvoice.id ? 'Deleting...' : 'Delete'}
              </button>
            </footer>
          </section>
        </div>
      ) : null}
    </>
  );

  return (
    <div className="table-wrap invoice-table-wrap">
      {isMobile ? renderMobile() : (
        rows.length === 0 ? (
          <div className="empty-state">No invoices match your search.</div>
        ) : (
          <table className={`table ${isTablet ? 'table-tablet-compact' : ''}`}>
            <thead>
              <tr>
                <th>Status</th>
                <th>Invoice #</th>
                <th>Customer</th>
                <th>Balance</th>
                <th>Total</th>
                <th>Due Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((inv) => (
                <tr key={inv.id}>
                  <td>
                    <span className={`status-badge status-${String(inv.status || 'pending').toLowerCase()}`}>
                      {inv.status || 'pending'}
                    </span>
                  </td>
                  <td>
                    <Link className="invoice-number-link" to={`/invoices/${inv.id}/view`}>
                      {inv.invoice_number}
                    </Link>
                  </td>
                  <td>
                    <div className="cell-content">
                      {inv.customer_name}
                      {inv.organization && <span className="sub-text">{inv.organization}</span>}
                    </div>
                  </td>
                  <td>
                    <span className={Number(inv.balance_remaining) > 0 ? 'text-danger' : 'text-success'}>
                      NLe {Number(inv.balance_remaining).toFixed(2)}
                    </span>
                  </td>
                  <td>NLe {Number(inv.total).toFixed(2)}</td>
                  <td>{formatDate(inv.due_date || inv.invoice_date)}</td>
                  <td>
                    <div className="actions">
                      <Link className="table-action-link" to={`/invoices/${inv.id}/view`}>
                        View
                      </Link>
                      <button type="button" className="table-action-link" onClick={(event) => handleEdit(inv, event)}>
                        Edit
                      </button>
                      {Number(inv.balance_remaining) > 0 && (
                        <button type="button" className="table-action-link" onClick={(event) => handlePay(inv, event)}>
                          Pay
                        </button>
                      )}
                      <button type="button" className="table-action-link text-danger" onClick={(event) => handleDelete(inv, event)}>
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )
      )}
    </div>
  );
}

export default InvoiceTable;
