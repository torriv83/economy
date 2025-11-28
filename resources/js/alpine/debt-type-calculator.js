// Debt type calculator Alpine.js component
// Calculates minimum payment based on debt type, balance, and interest rate
//
// Configuration is passed from Laravel's config/debt.php via Blade templates.
// This avoids hardcoding magic numbers in JavaScript.

import { Alpine } from '../../../vendor/livewire/livewire/dist/livewire.esm';

// Default config values (should match config/debt.php)
const DEFAULT_CONFIG = {
    kredittkort: {
        percentage: 0.03,      // 3% of balance
        minimumAmount: 300,    // 300 NOK minimum
    },
    forbrukslån: {
        payoffMonths: 60,      // Must be paid off within 5 years (Utlånsforskriften)
    },
};

Alpine.data('debtTypeCalculator', (config = {}) => ({
    type: config.type || 'kredittkort',
    balance: config.balance || 0,
    interestRate: config.interestRate || 0,
    calculatedMinimum: 0,

    // Config values (can be overridden from Blade templates)
    kredittkortPercentage: config.kredittkortPercentage ?? DEFAULT_CONFIG.kredittkort.percentage,
    kredittkortMinimum: config.kredittkortMinimum ?? DEFAULT_CONFIG.kredittkort.minimumAmount,
    forbrukslånPayoffMonths: config.forbrukslånPayoffMonths ?? DEFAULT_CONFIG.forbrukslån.payoffMonths,

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
            // Credit card: percentage of balance or minimum amount
            this.calculatedMinimum = Math.ceil(
                Math.max(balance * this.kredittkortPercentage, this.kredittkortMinimum)
            );
        } else {
            // Consumer loan: Calculate payment that pays off debt in 60 months
            // Using amortization formula: P = (r * PV) / (1 - (1 + r)^-n)
            const monthlyRate = (interestRate / 100) / 12;
            const numberOfMonths = this.forbrukslånPayoffMonths;

            if (monthlyRate === 0) {
                // If no interest, simply divide balance by number of months
                this.calculatedMinimum = Math.ceil(balance / numberOfMonths);
            } else {
                const payment = (monthlyRate * balance) / (1 - Math.pow(1 + monthlyRate, -numberOfMonths));
                this.calculatedMinimum = Math.ceil(payment);
            }
        }
    },

    formatCurrency(value) {
        return new Intl.NumberFormat('no-NO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    }
}));
