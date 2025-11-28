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
        bufferPercentage: 1.1, // 10% buffer above monthly interest
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
    forbrukslånBuffer: config.forbrukslånBuffer ?? DEFAULT_CONFIG.forbrukslån.bufferPercentage,

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
            // Consumer loan: monthly interest + buffer
            const monthlyInterest = (balance * (interestRate / 100)) / 12;
            this.calculatedMinimum = Math.ceil(monthlyInterest * this.forbrukslånBuffer);
        }
    },

    formatCurrency(value) {
        return new Intl.NumberFormat('no-NO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    }
}));
