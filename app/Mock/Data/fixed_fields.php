<?php

// Global fixed lead fields - Admin can toggle required/optional (not removable).
return [
    ['id' => 'business_name', 'label' => 'Business name', 'type' => 'text', 'required' => true, 'locked_required' => true],
    ['id' => 'business_email', 'label' => 'Business email address', 'type' => 'email', 'required' => true],
    ['id' => 'business_phone', 'label' => 'Business contact number', 'type' => 'phone', 'required' => true],
    ['id' => 'business_address', 'label' => 'Business address', 'type' => 'text', 'required' => true],
    ['id' => 'website', 'label' => 'Website', 'type' => 'url', 'required' => false],
    ['id' => 'contact_name', 'label' => 'Contact name', 'type' => 'text', 'required' => true, 'group' => 'Contact'],
    ['id' => 'contact_designation', 'label' => 'Contact designation', 'type' => 'text', 'required' => true, 'group' => 'Contact'],
    ['id' => 'contact_email', 'label' => 'Contact email', 'type' => 'email', 'required' => false, 'group' => 'Contact'],
    ['id' => 'contact_phone', 'label' => 'Contact phone', 'type' => 'phone', 'required' => false, 'group' => 'Contact'],
];
