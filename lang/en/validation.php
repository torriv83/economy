<?php

return [
    'minimum_payment_must_cover_interest' => 'Minimum payment must be at least :interest kr to cover monthly interest.',

    'debt' => [
        'name_required' => 'Name is required.',
        'name_string' => 'Name must be text.',
        'name_max' => 'Name cannot be longer than 255 characters.',
        'type_required' => 'Debt type is required.',
        'type_in' => 'Debt type must be either consumer loan or credit card.',
        'balance_required' => 'Balance is required.',
        'balance_numeric' => 'Balance must be a number.',
        'balance_min' => 'Balance must be at least 0.01 kr.',
        'interest_rate_required' => 'Interest rate is required.',
        'interest_rate_numeric' => 'Interest rate must be a number.',
        'interest_rate_min' => 'Interest rate cannot be negative.',
        'interest_rate_max' => 'Interest rate cannot exceed 100%.',
        'minimum_payment_required' => 'Minimum payment is required.',
        'minimum_payment_numeric' => 'Minimum payment must be a number.',
        'minimum_payment_min' => 'Minimum payment must be at least 0.01 kr.',
    ],
];
