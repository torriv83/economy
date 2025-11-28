// Debt type calculator Alpine.js component
// Calculates minimum payment based on debt type, balance, and interest rate

import { Alpine } from '../../../vendor/livewire/livewire/dist/livewire.esm';

Alpine.data('debtTypeCalculator', (config = {}) => ({
    type: config.type || 'kredittkort',
    balance: config.balance || 0,
    interestRate: config.interestRate || 0,
    calculatedMinimum: 0,

    init() {
        this.updateCalculatedMinimum();
        this.$watch('type', () => this.updateCalculatedMinimum());
        this.$watch('balance', () => this.updateCalculatedMinimum());
        this.$watch('interestRate', () => this.updateCalculatedMinimum());
    },

    updateCalculatedMinimum() {
        const balance = parseFloat(this.balance) || 0;
        const interestRate = parseFloat(this.interestRate) || 0;

        if (balance <= 0) {
            this.calculatedMinimum = 0;
            return;
        }

        if (this.type === 'kredittkort') {
            // 3% of balance or 300 kr minimum
            this.calculatedMinimum = Math.ceil(Math.max(balance * 0.03, 300));
        } else {
            // For forbrukslaan: monthly interest + 10% buffer
            const monthlyInterest = (balance * (interestRate / 100)) / 12;
            this.calculatedMinimum = Math.ceil(monthlyInterest * 1.1);
        }
    },

    formatCurrency(value) {
        return new Intl.NumberFormat('no-NO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    }
}));
