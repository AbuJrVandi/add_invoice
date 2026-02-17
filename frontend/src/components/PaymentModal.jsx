import { useState, useEffect } from 'react';
import api from '../services/api';
import useResponsive from '../hooks/useResponsive';

export default function PaymentModal({ invoice, onClose, onSuccess }) {
    const { isMobile } = useResponsive();
    const [amount, setAmount] = useState('');
    const [method, setMethod] = useState('cash');
    const [notes, setNotes] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    // Initialize amount with remaining balance
    useEffect(() => {
        if (invoice) {
            setAmount(invoice.balance_remaining);
        }
    }, [invoice]);

    if (!invoice) return null;

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setLoading(true);

        try {
            const response = await api.post('/payments', {
                invoice_id: invoice.id,
                amount_paid: amount,
                payment_method: method,
                notes: notes
            });

            // Close and trigger success callback (which should handle printing)
            onSuccess(response.data.data);
            onClose();
        } catch (err) {
            setError(err.response?.data?.message || 'Payment failed. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    const remaining = parseFloat(invoice.balance_remaining);
    const paying = parseFloat(amount) || 0;
    const newBalance = Math.max(0, remaining - paying);

    return (
        <div className="modal-overlay">
            <div className={`modal-content ${isMobile ? 'modal-content-mobile' : ''}`} style={isMobile ? undefined : { maxWidth: '500px' }}>
                <div className="modal-header">
                    <h2>Record Payment</h2>
                    <button onClick={onClose} className="close-btn">&times;</button>
                </div>

                <div className="invoice-summary-card">
                    <div className="row-between">
                        <span>Invoice:</span>
                        <strong>{invoice.invoice_number}</strong>
                    </div>
                    <div className="row-between">
                        <span>Customer:</span>
                        <strong>{invoice.customer_name}</strong>
                    </div>
                    <div className="divider"></div>
                    <div className="row-between">
                        <span>Total Amount:</span>
                        <span>NLe {Number(invoice.total).toFixed(2)}</span>
                    </div>
                    <div className="row-between">
                        <span>Already Paid:</span>
                        <span>NLe {Number(invoice.amount_paid).toFixed(2)}</span>
                    </div>
                    <div className="row-between highlight">
                        <span>Balance Due:</span>
                        <strong>NLe {Number(invoice.balance_remaining).toFixed(2)}</strong>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="payment-form">
                    <div className="form-group">
                        <label>Amount to Pay (NLe)</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            max={remaining}
                            required
                            value={amount}
                            onChange={(e) => setAmount(e.target.value)}
                            className="input-lg"
                            inputMode="decimal"
                        />
                    </div>

                    <div className="form-group">
                        <label>Payment Method</label>
                        <div className="method-grid">
                            {['cash', 'mobile_money', 'card', 'transfer'].map((m) => (
                                <button
                                    key={m}
                                    type="button"
                                    className={`method-btn ${method === m ? 'active' : ''}`}
                                    onClick={() => setMethod(m)}
                                >
                                    {m.replace('_', ' ')}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="form-group">
                        <label>Notes (Optional)</label>
                        <textarea
                            rows="2"
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            placeholder="Transaction ID, remarks..."
                        />
                    </div>

                    <div className="balance-preview">
                        <span>New Balance:</span>
                        <span className={newBalance === 0 ? 'success-text' : ''}>
                            NLe {newBalance.toFixed(2)}
                        </span>
                    </div>

                    {error && <div className="error-message">{error}</div>}

                    <div className={`modal-actions ${isMobile ? 'modal-actions-sticky' : ''}`}>
                        <button type="button" onClick={onClose} className="btn-secondary">Cancel</button>
                        <button type="submit" className="button btn-primary" disabled={loading}>
                            {loading ? 'Processing...' : `Confirm Payment`}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    );
}
