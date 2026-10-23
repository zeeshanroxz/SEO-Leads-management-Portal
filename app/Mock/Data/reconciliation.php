<?php

return [
    ['id' => 'RC-01', 'customer_id' => 'C-001', 'agreement_id' => 'AG-3001', 'period' => 'Current month', 'expected' => 1200, 'received' => 1200, 'status' => 'matched', 'note' => null, 'resolved' => true],
    ['id' => 'RC-02', 'customer_id' => 'C-002', 'agreement_id' => 'AG-3002', 'period' => 'Current month', 'expected' => 90, 'received' => 0, 'status' => 'mismatch', 'note' => 'Card declined on PAY-6005; subscription past due. Awaiting retry.', 'resolved' => false],
    ['id' => 'RC-03', 'customer_id' => 'C-002', 'agreement_id' => 'AG-3002', 'period' => 'Deposit', 'expected' => 2250, 'received' => 2250, 'status' => 'matched', 'note' => null, 'resolved' => true],
    ['id' => 'RC-04', 'customer_id' => 'C-020', 'agreement_id' => 'AG-3004', 'period' => 'Final month', 'expected' => 700, 'received' => 0, 'status' => 'mismatch', 'note' => 'Refund of PAY-6009 recorded; expected amount should be adjusted via ledger entry.', 'resolved' => false],
    ['id' => 'RC-05', 'customer_id' => 'C-001', 'agreement_id' => 'AG-3001', 'period' => 'Setup fee', 'expected' => 800, 'received' => 800, 'status' => 'matched', 'note' => null, 'resolved' => true],
];
