import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import FilterBar from '../components/FilterBar';
import InvoiceTable from '../components/InvoiceTable';
import useResponsive from '../hooks/useResponsive';

const defaultFilters = {
  invoice_number: '',
  customer_name: '',
  organization: '',
  date: '',
};

function InvoiceList() {
  const { isMobile } = useResponsive();
  const [filters, setFilters] = useState(defaultFilters);
  const [draftFilters, setDraftFilters] = useState(defaultFilters);
  const [invoices, setInvoices] = useState([]);
  const [visibleCount, setVisibleCount] = useState(10);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [lastUpdated, setLastUpdated] = useState(null);

  const fetchInvoices = useCallback(async (activeFilters) => {
    setLoading(true);
    setError('');

    try {
      const params = Object.fromEntries(
        Object.entries(activeFilters)
          .map(([key, value]) => [key, typeof value === 'string' ? value.trim() : value])
          .filter(([, value]) => value !== '')
      );

      const response = await api.get('/invoices', { params: { ...params, per_page: 20 } });
      const rows = Array.isArray(response.data?.data) ? response.data.data : [];
      setInvoices(rows);
      setLastUpdated(new Date());
    } catch (requestError) {
      setError(requestError?.response?.data?.message || 'Failed to load invoices. Please retry.');
      setInvoices([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchInvoices(filters);
  }, [fetchInvoices, filters]);

  useEffect(() => {
    setVisibleCount(10);
  }, [filters, invoices.length]);

  const handleChange = (field, value) => {
    setDraftFilters((prev) => ({ ...prev, [field]: value }));
  };

  const handleApply = () => {
    setFilters(draftFilters);
  };

  const handleReset = () => {
    setDraftFilters(defaultFilters);
    setFilters(defaultFilters);
  };

  const summary = useMemo(() => {
    const counts = {
      all: invoices.length,
      pending: 0,
      due: 0,
      completed: 0,
      outstandingBalance: 0,
    };

    invoices.forEach((invoice) => {
      const status = String(invoice?.status || '').toLowerCase();
      if (status === 'pending') {
        counts.pending += 1;
      }
      if (status === 'due') {
        counts.due += 1;
      }
      if (status === 'completed' || status === 'paid') {
        counts.completed += 1;
      }

      counts.outstandingBalance += Number(invoice?.balance_remaining || 0);
    });

    return counts;
  }, [invoices]);

  const visibleInvoices = useMemo(() => {
    if (!isMobile) {
      return invoices;
    }

    return invoices.slice(0, visibleCount);
  }, [invoices, isMobile, visibleCount]);

  const hasMoreMobileRows = isMobile && visibleCount < invoices.length;

  return (
    <div className="invoice-list-page stacked">
      <div className="invoice-list-header panel">
        <div>
          <h1>Invoices</h1>
          <p>Track issued invoices, balances, and payment readiness in one place.</p>
          <small>
            {lastUpdated
              ? `Last updated ${lastUpdated.toLocaleTimeString()}`
              : 'Loading latest invoices...'}
          </small>
        </div>

        <div className="invoice-list-header-actions">
          <button type="button" className="button button-outline" onClick={() => fetchInvoices(filters)}>
            Refresh
          </button>
          <Link to="/invoices/create" className="button">
            + Create New Invoice
          </Link>
        </div>
      </div>

      {error ? <div className="error">{error}</div> : null}

      <section className="invoice-list-summary">
        <article className="invoice-list-summary-card">
          <span>Total Loaded</span>
          <strong>{summary.all}</strong>
        </article>
        <article className="invoice-list-summary-card">
          <span>Pending</span>
          <strong>{summary.pending}</strong>
        </article>
        <article className="invoice-list-summary-card">
          <span>Due</span>
          <strong>{summary.due}</strong>
        </article>
        <article className="invoice-list-summary-card">
          <span>Completed</span>
          <strong>{summary.completed}</strong>
        </article>
        <article className="invoice-list-summary-card highlight">
          <span>Outstanding Balance</span>
          <strong>NLe {summary.outstandingBalance.toFixed(2)}</strong>
        </article>
      </section>

      <FilterBar
        filters={draftFilters}
        onChange={handleChange}
        onApply={handleApply}
        onReset={handleReset}
      />

      <div className="panel invoice-table-panel">
        <InvoiceTable
          invoices={visibleInvoices}
          loading={loading}
          hasMore={hasMoreMobileRows}
          onLoadMore={() => setVisibleCount((prev) => prev + 10)}
          onDataChanged={() => fetchInvoices(filters)}
        />
      </div>
    </div>
  );
}

export default InvoiceList;
