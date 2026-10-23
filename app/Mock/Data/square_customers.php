<?php

use App\Mock\MockData as M;

return [
    ['id' => 'SQC-001', 'customer_id' => 'C-001', 'square_customer_id' => 'DEMO-CUST-AAA111', 'email' => 'helen@brightsidedental.example', 'status' => 'linked', 'linked_by' => 'U-02', 'linked_at' => M::dt(-41, '14:00'), 'card_on_file' => 'Card •••• 4242 (demo)'],
    ['id' => 'SQC-002', 'customer_id' => 'C-002', 'square_customer_id' => 'DEMO-CUST-BBB222', 'email' => 'gareth@harborpine.example', 'status' => 'linked', 'linked_by' => 'U-03', 'linked_at' => M::dt(-18, '11:00'), 'card_on_file' => 'Card •••• 1881 (demo)'],
    ['id' => 'SQC-003', 'customer_id' => 'C-020', 'square_customer_id' => 'DEMO-CUST-CCC333', 'email' => 'walter@fernhillgarden.example', 'status' => 'linked', 'linked_by' => 'U-02', 'linked_at' => M::dt(-97, '12:00'), 'card_on_file' => 'Card •••• 0019 (demo)'],
    ['id' => 'SQC-004', 'customer_id' => 'C-021', 'square_customer_id' => null, 'email' => 'greta@polarpeakcycles.example', 'status' => 'unmapped', 'linked_by' => null, 'linked_at' => null, 'card_on_file' => null],
    ['id' => 'SQC-005', 'customer_id' => 'C-003', 'square_customer_id' => null, 'email' => 'tom@northwindplumbing.example', 'status' => 'unmapped', 'linked_by' => null, 'linked_at' => null, 'card_on_file' => null],
    ['id' => 'SQC-006', 'customer_id' => 'C-023', 'square_customer_id' => null, 'email' => 'priyanka@mosaicdentalstudio.example', 'status' => 'unmapped', 'linked_by' => null, 'linked_at' => null, 'card_on_file' => null],
];
