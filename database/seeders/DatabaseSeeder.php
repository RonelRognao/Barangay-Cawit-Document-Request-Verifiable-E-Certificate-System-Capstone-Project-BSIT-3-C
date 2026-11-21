<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'full_name' => 'Barangay Admin',
            'email' => 'admin@barangaycawit.gov.ph',
            'password_hash' => Hash::make('Admin@12345'),
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        User::create([
            'full_name' => 'Juan Dela Cruz',
            'email' => 'resident@barangaycawit.gov.ph',
            'password_hash' => Hash::make('Resident@12345'),
            'role' => 'resident',
            'contact_number' => '09123456789',
            'address' => 'Purok 1, Barangay Cawit, Manito, Albay',
            'account_status' => 'active',
        ]);

        $types = [
            ['document_name' => 'Barangay Clearance', 'description' => 'General barangay clearance certificate', 'fee' => 50.00],
            ['document_name' => 'Certificate of Indigency', 'description' => 'For medical, educational, or financial assistance', 'fee' => 0.00],
            ['document_name' => 'Certificate of Residency', 'description' => 'Proof of residency in Barangay Cawit', 'fee' => 50.00],
            ['document_name' => 'Barangay Business Clearance', 'description' => 'Required for business permit applications', 'fee' => 150.00],
            ['document_name' => 'Certificate of Good Moral Character', 'description' => 'Attestation of good standing in the community', 'fee' => 50.00],
        ];

        foreach ($types as $type) {
            DocumentType::create($type + ['is_active' => true]);
        }
    }
}