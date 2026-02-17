import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import { useAuth } from '../context/AuthContext';

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

function formatCurrencyAmount(value) {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(value || 0));
}

function formatCompact(value) {
  return new Intl.NumberFormat('en-US', {
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(Number(value || 0));
}

function formatPercent(value) {
  return `${Number(value || 0).toFixed(2)}%`;
}

function formatDateTime(value) {
  if (!value) {
    return '-';
  }

  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value));
}

function formatTrend(value) {
  if (value === null || value === undefined) {
    return { label: 'No baseline', tone: 'neutral', icon: '•' };
  }

  if (value > 0) {
    return { label: `+${Number(value).toFixed(2)}%`, tone: 'up', icon: '↑' };
  }

  if (value < 0) {
    return { label: `${Number(value).toFixed(2)}%`, tone: 'down', icon: '↓' };
  }

  return { label: '0.00%', tone: 'neutral', icon: '→' };
}

function buildSparkPath(values, width, height, padding) {
  if (!Array.isArray(values) || values.length === 0) {
    return '';
  }

  const max = Math.max(...values, 1);
  const stepX = values.length > 1 ? (width - padding * 2) / (values.length - 1) : 0;

  return values
    .map((value, index) => {
      const x = padding + index * stepX;
      const y = height - padding - ((Number(value) || 0) / max) * (height - padding * 2);
      return `${index === 0 ? 'M' : 'L'} ${x.toFixed(2)} ${y.toFixed(2)}`;
    })
    .join(' ');
}

function invoiceStatusChipClass(status) {
  if (status === 'completed' || status === 'paid') {
    return 'owner-ops-chip good';
  }

  if (status === 'due') {
    return 'owner-ops-chip warning';
  }

  if (status === 'pending') {
    return 'owner-ops-chip danger';
  }

  return 'owner-ops-chip neutral';
}

export default function OwnerDashboard() {
  const { user } = useAuth();

  const [analytics, setAnalytics] = useState({
    generated_at: null,
    overview: {
      total_invoices: 0,
      total_receipts: 0,
      pending_invoices: 0,
      due_invoices: 0,
      completed_invoices: 0,
      overdue_invoices: 0,
      invoice_completion_rate: 0,
      receipt_coverage_rate: 0,
      average_receipt_amount: 0,
      invoices_created_this_month: 0,
      receipts_issued_this_month: 0,
    },
    liquidity: {
      today: 0,
      this_month: 0,
      avg_last_30_days: 0,
    },
    performance: {
      earned_revenue: 0,
      profit: 0,
      profit_margin: 0,
      avg_profit_per_sale: 0,
    },
    risk: {
      outstanding_balance: 0,
      overdue_invoices: 0,
      partially_paid_invoices: 0,
    },
    trends: {
      liquidity: {
        today_vs_yesterday: null,
        this_month_vs_last_month: null,
      },
      performance: {
        revenue_30_days: null,
        profit_30_days: null,
      },
      operations: {
        invoices_7_days: null,
        receipts_7_days: null,
      },
    },
    series: {
      labels: [],
      cash: [],
      revenue: [],
      profit: [],
    },
    weekly_activity: {
      labels: [],
      invoices_created: [],
      receipts_issued: [],
      collected_cash: [],
    },
    distributions: {
      invoice_status: [],
      payment_methods: [],
    },
    recent: {
      invoices: [],
      receipts: [],
    },
  });

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');
  const [lastSync, setLastSync] = useState(null);
  const [manualRefreshKey, setManualRefreshKey] = useState(0);

  useEffect(() => {
    let mounted = true;

    const fetchDashboard = async (silent = false) => {
      if (!silent) {
        setLoading(true);
      }
      if (silent) {
        setRefreshing(true);
      }

      try {
        const response = await api.get('/owner/analytics');

        if (!mounted) {
          return;
        }

        if (response.data) {
          setAnalytics((prev) => ({ ...prev, ...response.data }));
        }

        setError('');
        setLastSync(new Date());
      } catch (err) {
        if (!mounted) {
          return;
        }

        if (!silent) {
          setError(err.response?.status === 403
            ? 'Access denied. Owner role required.'
            : 'Failed to load owner analytics.');
        }
      } finally {
        if (mounted) {
          if (!silent) {
            setLoading(false);
          }
          if (silent) {
            setRefreshing(false);
          }
        }
      }
    };

    fetchDashboard(false);

    const timer = window.setInterval(() => {
      fetchDashboard(true);
    }, 30000);

    return () => {
      mounted = false;
      window.clearInterval(timer);
    };
  }, [manualRefreshKey]);

  const sparklinePath = useMemo(
    () => buildSparkPath(analytics.weekly_activity.collected_cash, 460, 118, 8),
    [analytics.weekly_activity.collected_cash]
  );

  const statusDonutStyle = useMemo(() => {
    const statuses = Array.isArray(analytics.distributions.invoice_status)
      ? analytics.distributions.invoice_status
      : [];

    const pending = Number(statuses.find((item) => item.key === 'pending')?.value || 0);
    const due = Number(statuses.find((item) => item.key === 'due')?.value || 0);
    const completed = Number(statuses.find((item) => item.key === 'completed')?.value || 0);
    const draft = Number(statuses.find((item) => item.key === 'draft')?.value || 0);

    const total = Math.max(pending + due + completed + draft, 1);
    const p1 = (completed / total) * 360;
    const p2 = (pending / total) * 360;
    const p3 = (due / total) * 360;

    return {
      background: `conic-gradient(
        #202a64 0deg ${p1}deg,
        #ff8f4e ${p1}deg ${p1 + p2}deg,
        #f45262 ${p1 + p2}deg ${p1 + p2 + p3}deg,
        #c9d1e2 ${p1 + p2 + p3}deg 360deg
      )`,
    };
  }, [analytics.distributions.invoice_status]);

  const statusRows = Array.isArray(analytics.distributions.invoice_status)
    ? analytics.distributions.invoice_status
    : [];

  const paymentMethodRows = Array.isArray(analytics.distributions.payment_methods)
    ? analytics.distributions.payment_methods
    : [];

  const weeklyMaxVolume = useMemo(() => {
    const maxInvoices = Math.max(...(analytics.weekly_activity.invoices_created || [0]));
    const maxReceipts = Math.max(...(analytics.weekly_activity.receipts_issued || [0]));
    return Math.max(maxInvoices, maxReceipts, 1);
  }, [analytics.weekly_activity.invoices_created, analytics.weekly_activity.receipts_issued]);

  const activityRows = useMemo(() => {
    const invoiceRows = (analytics.recent?.invoices || []).map((invoice) => ({
      id: `inv-${invoice.id}`,
      type: 'invoice',
      reference: invoice.invoice_number,
      counterpart: invoice.customer_name,
      status: invoice.status,
      amount: Number(invoice.total || 0),
      timestamp: invoice.created_at,
      details: invoice.organization || 'Invoice issued',
      actionLink: `/invoices/${invoice.id}/view`,
      actionLabel: 'Open Invoice',
      external: false,
    }));

    const receiptRows = (analytics.recent?.receipts || []).map((receipt) => ({
      id: `rcp-${receipt.id}`,
      type: 'receipt',
      reference: receipt.receipt_number,
      counterpart: receipt.invoice?.customer_name || 'Unknown Customer',
      status: receipt.payment_method,
      amount: Number(receipt.amount_paid || 0),
      timestamp: receipt.paid_at,
      details: receipt.invoice?.invoice_number
        ? `For ${receipt.invoice.invoice_number}`
        : 'Payment recorded',
      actionLink: receipt.receipt_url || '#',
      actionLabel: 'Open Receipt',
      external: true,
    }));

    return [...invoiceRows, ...receiptRows]
      .sort((a, b) => new Date(b.timestamp || 0).getTime() - new Date(a.timestamp || 0).getTime())
      .slice(0, 10);
  }, [analytics.recent]);

  if (loading) {
    return <div className="loading-state">Loading owner analytics...</div>;
  }

  if (error) {
    return <div className="empty-state" style={{ color: 'var(--error)' }}>{error}</div>;
  }

  const trendInvoices = formatTrend(analytics.trends?.operations?.invoices_7_days);
  const trendReceipts = formatTrend(analytics.trends?.operations?.receipts_7_days);
  const trendCashMonth = formatTrend(analytics.trends?.liquidity?.this_month_vs_last_month);

  return (
    <div className="owner-ops-page">
      <header className="owner-ops-header">
        <div>
          <h1>Analytics</h1>
          <p>Live owner dashboard for invoices, receipts, cash and risk.</p>
        </div>

        <div className="owner-ops-header-right">
          <div className="owner-ops-search" aria-hidden="true">⌕ Invoice, receipt, customer</div>
          <button
            type="button"
            className="owner-ops-refresh"
            onClick={() => setManualRefreshKey((previous) => previous + 1)}
          >
            Refresh
          </button>
          <div className="owner-ops-user-pill">
            <span className="avatar">{(user?.name || 'O').charAt(0).toUpperCase()}</span>
            <span>{user?.name || 'Owner'}</span>
          </div>
        </div>
      </header>

      <p className="owner-ops-sync-note">
        {refreshing ? 'Syncing latest invoices and receipts...' : `Last synced ${lastSync ? formatDateTime(lastSync) : 'just now'} · auto-refresh every 30 seconds`}
      </p>

      <section className="owner-ops-top-grid">
        <article className="owner-ops-card owner-ops-card-gradient">
          <div className="owner-ops-card-head">
            <h3>Total Invoices</h3>
            <span className="owner-ops-card-tag">Live</span>
          </div>

          <div className="owner-ops-total-number">{formatCompact(analytics.overview.total_invoices)}</div>

          <svg viewBox="0 0 460 118" className="owner-ops-sparkline" role="img" aria-label="Cash collection trend">
            <path d={sparklinePath} className="owner-ops-sparkline-path" />
          </svg>

          <div className="owner-ops-inline-stats">
            <div>
              <span>Pending</span>
              <strong>{analytics.overview.pending_invoices}</strong>
            </div>
            <div>
              <span>Due</span>
              <strong>{analytics.overview.due_invoices}</strong>
            </div>
            <div>
              <span>Completed</span>
              <strong>{analytics.overview.completed_invoices}</strong>
            </div>
          </div>
        </article>

        <article className="owner-ops-card">
          <div className="owner-ops-card-head">
            <h3>Collection Health</h3>
            <span className={`owner-trend owner-trend-${trendCashMonth.tone}`}>
              {trendCashMonth.icon} {trendCashMonth.label}
            </span>
          </div>

          <div className="owner-ops-health-grid">
            <div className="owner-ops-status-donut" style={statusDonutStyle}>
              <div className="owner-ops-status-donut-center">{formatPercent(analytics.overview.invoice_completion_rate)}</div>
            </div>
            <div className="owner-ops-metrics-list">
              <p className="owner-ops-metric-row">
                <span className="owner-ops-metric-label">Cash today</span>
                <strong className="owner-ops-metric-value">
                  <span className="owner-ops-metric-currency">NLe</span>
                  <span className="owner-ops-metric-amount">{formatCurrencyAmount(analytics.liquidity.today)}</span>
                </strong>
              </p>
              <p className="owner-ops-metric-row">
                <span className="owner-ops-metric-label">Cash this month</span>
                <strong className="owner-ops-metric-value">
                  <span className="owner-ops-metric-currency">NLe</span>
                  <span className="owner-ops-metric-amount">{formatCurrencyAmount(analytics.liquidity.this_month)}</span>
                </strong>
              </p>
              <p className="owner-ops-metric-row">
                <span className="owner-ops-metric-label">Outstanding</span>
                <strong className="owner-ops-metric-value">
                  <span className="owner-ops-metric-currency">NLe</span>
                  <span className="owner-ops-metric-amount">{formatCurrencyAmount(analytics.risk.outstanding_balance)}</span>
                </strong>
              </p>
            </div>
          </div>
        </article>

        <div className="owner-ops-side-stack">
          <article className="owner-ops-card owner-ops-mini-card">
            <h3>Receipts Issued</h3>
            <div className="owner-ops-mini-value">{formatCompact(analytics.overview.total_receipts)}</div>
            <p>{analytics.overview.receipts_issued_this_month} this month</p>
            <div className="owner-ops-progress">
              <span style={{ width: `${Math.min(Number(analytics.overview.receipt_coverage_rate || 0), 100)}%` }}></span>
            </div>
          </article>

          <article className="owner-ops-card owner-ops-mini-card">
            <h3>Avg Receipt Amount</h3>
            <div className="owner-ops-mini-value">{formatCurrency(analytics.overview.average_receipt_amount)}</div>
            <p>{analytics.overview.overdue_invoices} overdue invoices</p>
            <div className="owner-ops-mini-row">
              <span>Partially paid</span>
              <strong>{analytics.risk.partially_paid_invoices}</strong>
            </div>
          </article>
        </div>
      </section>

      <section className="owner-ops-middle-grid">
        <article className="owner-ops-card owner-ops-volume-card">
          <div className="owner-ops-card-head">
            <h3>Invoice & Receipt Volume</h3>
            <span className="owner-ops-card-tag">Last 7 days</span>
          </div>

          <div className="owner-ops-volume-bars">
            {(analytics.weekly_activity.labels || []).map((label, index) => {
              const invoiceValue = Number(analytics.weekly_activity.invoices_created?.[index] || 0);
              const receiptValue = Number(analytics.weekly_activity.receipts_issued?.[index] || 0);

              return (
                <div className="owner-ops-volume-col" key={label + index}>
                  <div className="owner-ops-volume-rails">
                    <span className="invoice" style={{ height: `${(invoiceValue / weeklyMaxVolume) * 100}%` }}></span>
                    <span className="receipt" style={{ height: `${(receiptValue / weeklyMaxVolume) * 100}%` }}></span>
                  </div>
                  <small>{label}</small>
                </div>
              );
            })}
          </div>

          <div className="owner-ops-volume-legend">
            <span><i className="invoice"></i>Invoices</span>
            <span><i className="receipt"></i>Receipts</span>
            <span className={`owner-trend owner-trend-${trendInvoices.tone}`}>{trendInvoices.icon} {trendInvoices.label}</span>
            <span className={`owner-trend owner-trend-${trendReceipts.tone}`}>{trendReceipts.icon} {trendReceipts.label}</span>
          </div>
        </article>

        <article className="owner-ops-card owner-ops-breakdown-card">
          <div className="owner-ops-card-head">
            <h3>Status & Methods</h3>
            <span className="owner-ops-card-tag">Realtime mix</span>
          </div>

          <div className="owner-ops-breakdown-block">
            <h4>Invoice Status</h4>
            <ul>
              {statusRows.map((item) => {
                const total = Math.max(Number(analytics.overview.total_invoices || 0), 1);
                const width = (Number(item.value || 0) / total) * 100;

                return (
                  <li key={item.key}>
                    <div className="owner-ops-breakdown-row">
                      <span>{item.label}</span>
                      <strong>{item.value}</strong>
                    </div>
                    <div className="owner-ops-progress"><span style={{ width: `${width}%` }}></span></div>
                  </li>
                );
              })}
            </ul>
          </div>

          <div className="owner-ops-breakdown-block">
            <h4>Receipt Methods</h4>
            <ul className="owner-ops-method-list">
              {paymentMethodRows.map((method) => (
                <li key={method.key}>
                  <span>{PAYMENT_METHOD_LABELS[method.key] || method.label}</span>
                  <strong>{method.value}</strong>
                </li>
              ))}
            </ul>
          </div>
        </article>
      </section>

      <section className="owner-ops-card owner-ops-activity-card">
        <div className="owner-ops-card-head">
          <h3>Live Owner Activity</h3>
          <span className="owner-ops-card-tag">Invoices + Receipts</span>
        </div>

        <div className="table-wrap">
          <table className="table owner-ops-table">
            <thead>
              <tr>
                <th>Type</th>
                <th>Reference</th>
                <th>Customer</th>
                <th>Status / Method</th>
                <th>Amount</th>
                <th>Time</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              {activityRows.length === 0 ? (
                <tr>
                  <td colSpan="7" className="empty-state">No invoices or receipts recorded yet.</td>
                </tr>
              ) : (
                activityRows.map((row) => (
                  <tr key={row.id}>
                    <td>{row.type === 'invoice' ? 'Invoice' : 'Receipt'}</td>
                    <td>
                      <strong>{row.reference}</strong>
                      <span className="owner-ops-subtext">{row.details}</span>
                    </td>
                    <td>{row.counterpart}</td>
                    <td>
                      {row.type === 'invoice' ? (
                        <span className={invoiceStatusChipClass(row.status)}>{row.status}</span>
                      ) : (
                        <span className="owner-ops-chip neutral">{PAYMENT_METHOD_LABELS[row.status] || row.status}</span>
                      )}
                    </td>
                    <td>{formatCurrency(row.amount)}</td>
                    <td>{formatDateTime(row.timestamp)}</td>
                    <td>
                      {row.external ? (
                        <a href={row.actionLink} target="_blank" rel="noreferrer" className="owner-ops-action-link">
                          {row.actionLabel}
                        </a>
                      ) : (
                        <Link to={row.actionLink} className="owner-ops-action-link">
                          {row.actionLabel}
                        </Link>
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );
}
