<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Minimum Payment Configuration
    |--------------------------------------------------------------------------
    |
    | These values define the regulatory minimum payment requirements for
    | different debt types in Norway. They are used for validation and
    | calculation of minimum payments.
    |
    */

    'minimum_payment' => [
        'kredittkort' => [
            'percentage' => 0.03,  // 3% of balance
            'minimum_amount' => 300,  // 300 NOK minimum
        ],
        'forbrukslån' => [
            'buffer_percentage' => 1.1,  // 10% above monthly interest
        ],
    ],
];
