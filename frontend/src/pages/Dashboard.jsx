import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';

function Dashboard() {
  const [data, setData] = useState({
    total_invoices: 0,
    cash_collected: 0,
    earned_revenue: 0,
    outstanding_invoices: 0,
    outstanding_amount: 0,
    total_sales: 0,
    today_cash_collected: 0,
    today_cash_count: 0,
    today_earned_revenue: 0,
    recent_invoices: []
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const load = async () => {
      try {
        const response = await api.get('/dashboard');
        setData(response.data);
      } finally {
        setLoading(false);
      }
    };
    load();
  }, []);

  if (loading) {
    return <div className="loading-state">Loading dashboard...</div>;
  }

  return (
    <div className="start-grid">
      {/* Stats Overview */}
      <div className="stats-grid">
        <article className="stat-card">
          <p>Cash Collected (Liquidity)</p>
          <h3 className="text-primary">NLe {Number(data.cash_collected).toFixed(2)}</h3>
          <span className="sub-stat">
            Today: NLe {Number(data.today_cash_collected).toFixed(2)} from {data.today_cash_count || 0} payments
          </span>
        </article>

        <article className="stat-card">
          <p>Earned Revenue (Completed)</p>
          <h3 className="text-success">NLe {Number(data.earned_revenue).toFixed(2)}</h3>
          <span className="sub-stat">{data.total_sales || 0} completed sales snapshots</span>
        </article>

        <article className="stat-card">
          <p>Outstanding Amount</p>
          <h3 className="text-secondary">NLe {Number(data.outstanding_amount).toFixed(2)}</h3>
          <span className="sub-stat">
            {data.outstanding_invoices} unpaid invoices | Today earned: NLe {Number(data.today_earned_revenue).toFixed(2)}
          </span>
        </article>

        <article className="stat-card highlight-card">
          <p>Quick Actions</p>
          <div className="action-buttons">
            <Link className="button btn-sm" to="/invoices/create">➕ New Invoice</Link>
            <Link className="button btn-sm btn-outline" to="/payments">💰 Record Payment</Link>
          </div>
        </article>
      </div>

      {/* Recent Activity Panel */}
      <section className="panel">
        <div className="panel-header">
          <h3>Recent Invoices</h3>
          <Link to="/invoices" className="view-all">View All</Link>
        </div>

        <div className="table-wrap">
          <table className="table table-mobile">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Total</th>
                <th>Balance</th>
              </tr>
            </thead>
            <tbody>
              {data.recent_invoices?.length === 0 ? (
                <tr>
                  <td colSpan="5" className="empty-state">
                    No recent invoices found.
                  </td>
                </tr>
              ) : (
                data.recent_invoices.map((inv) => (
                  <tr key={inv.id}>
                    <td data-label="Invoice #">
                      <strong className="text-dark">{inv.invoice_number}</strong>
                    </td>
                    <td data-label="Customer">{inv.customer_name}</td>
                    <td data-label="Status">
                      <span className={`status-badge status-${inv.status || 'pending'}`}>
                        {inv.status || 'Pending'}
                      </span>
                    </td>
                    <td data-label="Total">
                      <strong>NLe {Number(inv.total).toFixed(2)}</strong>
                    </td>
                    <td data-label="Balance">
                      <span className={Number(inv.balance_remaining) > 0 ? 'text-danger' : 'text-success'}>
                        NLe {Number(inv.balance_remaining).toFixed(2)}
                      </span>
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

export default Dashboard;
