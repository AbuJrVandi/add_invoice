import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import api from '../services/api';
import PaymentModal from '../components/PaymentModal';
import useResponsive from '../hooks/useResponsive';

export default function Payments() {
  const { isMobile, isTablet } = useResponsive();
  const [searchParams, setSearchParams] = useSearchParams();
  const [invoices, setInvoices] = useState([]);
  const [payments, setPayments] = useState([]);
  const [activeTab, setActiveTab] = useState('payments');
  const [loading, setLoading] = useState(false);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [showModal, setShowModal] = useState(false);
  const [selectedInvoice, setSelectedInvoice] = useState(null);

  const focusInvoiceId = Number(searchParams.get('invoice') || 0);

  const fetchData = useCallback(async () => {
    setLoading(true);
    try {
      const endpoint = activeTab === 'payments' ? '/payments' : '/payments/search-invoices';
      const params = {
        page,
        customer_name: search,
        invoice_number: search,
        per_page: 15,
      };

      const res = await api.get(endpoint, { params });
      const rows = Array.isArray(res.data?.data) ? res.data.data : [];

      if (activeTab === 'payments') {
        setPayments(rows);
      } else {
        setInvoices(rows);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [activeTab, page, search]);

  useEffect(() => {
    if (focusInvoiceId) {
      setActiveTab('new');
    }
  }, [focusInvoiceId]);

  useEffect(() => {
    const handler = setTimeout(() => {
      fetchData();
    }, 350);

    return () => clearTimeout(handler);
  }, [fetchData]);

  useEffect(() => {
    if (activeTab !== 'new' || !focusInvoiceId || !invoices.length) {
      return;
    }

    const matched = invoices.find((invoice) => Number(invoice.id) === focusInvoiceId);
    if (matched) {
      setSelectedInvoice(matched);
      setShowModal(true);
      const nextParams = new URLSearchParams(searchParams);
      nextParams.delete('invoice');
      setSearchParams(nextParams, { replace: true });
    }
  }, [activeTab, focusInvoiceId, invoices, searchParams, setSearchParams]);

  const handlePay = (invoice) => {
    setSelectedInvoice(invoice);
    setShowModal(true);
  };

  const handleSuccess = (responseData) => {
    fetchData();
    if (responseData?.payment) {
      printReceipt(responseData.payment);
    }
  };

  const printReceipt = (paymentOrId) => {
    let printUrl;
    if (typeof paymentOrId === 'object' && paymentOrId.receipt_url) {
      printUrl = paymentOrId.receipt_url;
    } else {
      const payment = payments.find((entry) => entry.id === paymentOrId);
      if (payment?.receipt_url) {
        printUrl = payment.receipt_url;
      }
    }

    if (!printUrl) {
      console.error('Receipt URL not found for printing');
      return;
    }

    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = printUrl;
    document.body.appendChild(iframe);

    iframe.onload = () => {
      try {
        iframe.contentWindow.print();
      } catch (error) {
        console.log('Using internal print script due to cross-origin restriction');
      }
      setTimeout(() => document.body.removeChild(iframe), 5000);
    };
  };

  const rows = useMemo(() => (activeTab === 'payments' ? payments : invoices), [activeTab, payments, invoices]);

  return (
    <div className="payments-page stacked">
      <div className="page-header">
        <h1>Payments & Receipts</h1>
        <div className="tabs">
          <button
            className={`tab ${activeTab === 'payments' ? 'active' : ''}`}
            onClick={() => setActiveTab('payments')}
            type="button"
          >
            Payment History
          </button>
          <button
            className={`tab ${activeTab === 'new' ? 'active' : ''}`}
            onClick={() => setActiveTab('new')}
            type="button"
          >
            New Payment
          </button>
        </div>
      </div>

      <div className="toolbar">
        <input
          type="text"
          placeholder="Search by Invoice # or Customer..."
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          className="search-input"
        />
      </div>

      {loading ? (
        <div className="loading">Loading records...</div>
      ) : isMobile ? (
        <div className="payments-mobile-list">
          {rows.length === 0 ? (
            <div className="empty-state">
              {activeTab === 'payments' ? 'No payments found.' : 'No unpaid invoices found.'}
            </div>
          ) : activeTab === 'payments' ? (
            rows.map((payment) => (
              <article key={payment.id} className="payments-mobile-card">
                <div className="invoice-mobile-row-top">
                  <strong>{payment.receipt_number}</strong>
                  <span className="status-badge status-completed">{payment.payment_method}</span>
                </div>
                <p>{payment.invoice?.customer_name || '-'}</p>
                <div className="invoice-mobile-kpis">
                  <div>
                    <small>Invoice</small>
                    <strong>{payment.invoice?.invoice_number || '-'}</strong>
                  </div>
                  <div>
                    <small>Amount</small>
                    <strong className="text-success">NLe {Number(payment.amount_paid).toFixed(2)}</strong>
                  </div>
                  <div>
                    <small>Date</small>
                    <strong>{new Date(payment.paid_at).toLocaleDateString()}</strong>
                  </div>
                </div>
                <div className="payments-mobile-actions">
                  <Link className="button button-outline" to={`/invoices/${payment.invoice_id}/view`}>Invoice</Link>
                  <Link className="button button-outline" to={`/payments/${payment.id}/receipt`}>Receipt</Link>
                  <button type="button" className="button" onClick={() => printReceipt(payment)}>Print</button>
                </div>
              </article>
            ))
          ) : (
            rows.map((invoice) => (
              <article key={invoice.id} className="payments-mobile-card">
                <div className="invoice-mobile-row-top">
                  <strong>{invoice.invoice_number}</strong>
                  <span className="status-badge status-pending">Balance Due</span>
                </div>
                <p>{invoice.customer_name}</p>
                <div className="invoice-mobile-kpis">
                  <div>
                    <small>Total</small>
                    <strong>NLe {Number(invoice.total).toFixed(2)}</strong>
                  </div>
                  <div>
                    <small>Paid</small>
                    <strong>NLe {Number(invoice.amount_paid).toFixed(2)}</strong>
                  </div>
                  <div>
                    <small>Balance</small>
                    <strong className="text-danger">NLe {Number(invoice.balance_remaining).toFixed(2)}</strong>
                  </div>
                </div>
                <div className="payments-mobile-actions">
                  <Link className="button button-outline" to={`/invoices/${invoice.id}/view`}>View PDF</Link>
                  <button type="button" className="button" onClick={() => handlePay(invoice)}>Pay Now</button>
                </div>
              </article>
            ))
          )}
        </div>
      ) : (
        <div className={`table-responsive ${isTablet ? 'table-tablet-scroll' : ''}`}>
          <table className="table">
            <thead>
              {activeTab === 'payments' ? (
                <tr>
                  <th>Receipt #</th>
                  <th>Invoice #</th>
                  <th>Customer</th>
                  <th>Method</th>
                  <th>Amount</th>
                  <th>Date</th>
                  <th>Action</th>
                </tr>
              ) : (
                <tr>
                  <th>Invoice #</th>
                  <th>Customer</th>
                  <th>Total</th>
                  <th>Paid</th>
                  <th>Balance</th>
                  <th>Action</th>
                </tr>
              )}
            </thead>
            <tbody>
              {activeTab === 'payments' ? (
                payments.length > 0 ? payments.map((payment) => (
                  <tr key={payment.id}>
                    <td>{payment.receipt_number}</td>
                    <td>{payment.invoice?.invoice_number}</td>
                    <td>{payment.invoice?.customer_name}</td>
                    <td>{payment.payment_method}</td>
                    <td className="amount">NLe {Number(payment.amount_paid).toFixed(2)}</td>
                    <td>{new Date(payment.paid_at).toLocaleDateString()}</td>
                    <td>
                      <div className="actions">
                        <Link className="btn-icon" to={`/invoices/${payment.invoice_id}/view`} title="View Invoice">
                          📄
                        </Link>
                        <Link className="btn-icon" to={`/payments/${payment.id}/receipt`} title="View Receipt">
                          🧾
                        </Link>
                        <button className="btn-icon" onClick={() => printReceipt(payment)} title="Reprint Receipt" type="button">
                          🖨️
                        </button>
                      </div>
                    </td>
                  </tr>
                )) : <tr><td colSpan="7" className="empty">No payments found.</td></tr>
              ) : (
                invoices.length > 0 ? invoices.map((invoice) => (
                  <tr key={invoice.id}>
                    <td>{invoice.invoice_number}</td>
                    <td>{invoice.customer_name}</td>
                    <td>NLe {Number(invoice.total).toFixed(2)}</td>
                    <td>NLe {Number(invoice.amount_paid).toFixed(2)}</td>
                    <td className="text-danger">NLe {Number(invoice.balance_remaining).toFixed(2)}</td>
                    <td>
                      <div className="actions">
                        <Link className="btn-icon" to={`/invoices/${invoice.id}/view`} title="View PDF">
                          📄
                        </Link>
                        <button className="button btn-sm" onClick={() => handlePay(invoice)} type="button">
                          Pay Now
                        </button>
                      </div>
                    </td>
                  </tr>
                )) : <tr><td colSpan="6" className="empty">No unpaid invoices found.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {showModal && selectedInvoice ? (
        <PaymentModal
          invoice={selectedInvoice}
          onClose={() => setShowModal(false)}
          onSuccess={handleSuccess}
        />
      ) : null}
    </div>
  );
}
