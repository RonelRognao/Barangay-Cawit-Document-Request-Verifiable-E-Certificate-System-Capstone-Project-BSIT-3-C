<?php

namespace Database\Seeders;

use App\Models\BarangayOfficial;
use App\Models\DocumentType;
use App\Models\Purok;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now('Asia/Manila');

        foreach (range(1, 7) as $num) {
            Purok::firstOrCreate(['name' => "Purok {$num}"], ['is_active' => true]);
        }
        $purokOne = Purok::where('name', 'Purok 1')->first();

        // 1. Staff & Admin Accounts (RBAC)
        $staffAccounts = [
            [
                'full_name' => 'Barangay Admin',
                'email' => 'admin@barangaycawit.gov.ph',
                'password' => 'Admin@12345',
                'role' => 'admin',
                'position_title' => 'System Administrator',
            ],
            [
                'full_name' => 'Sec. Elena Mendoza',
                'email' => 'secretary@barangaycawit.gov.ph',
                'password' => 'Secretary@12345',
                'role' => 'secretary',
                'position_title' => 'Barangay Secretary',
            ],
            [
                'full_name' => 'Treas. Roberto Ramos',
                'email' => 'treasurer@barangaycawit.gov.ph',
                'password' => 'Treasurer@12345',
                'role' => 'treasurer',
                'position_title' => 'Barangay Treasurer',
            ],
            [
                'full_name' => 'Clerk Liza Bautista',
                'email' => 'clerk@barangaycawit.gov.ph',
                'password' => 'Clerk@12345',
                'role' => 'clerk',
                'position_title' => 'Barangay Clerk',
            ],
        ];

        foreach ($staffAccounts as $staff) {
            User::updateOrCreate(
                ['email' => $staff['email']],
                [
                    'full_name' => $staff['full_name'],
                    'password_hash' => Hash::make($staff['password']),
                    'role' => $staff['role'],
                    'position_title' => $staff['position_title'],
                    'account_status' => 'active',
                    'kyc_status' => 'verified',
                    'kyc_verified_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        // 2. Sample Verified Resident Account (Eligible for all documents & RA 11261)
        User::updateOrCreate(
            ['email' => 'resident@barangaycawit.gov.ph'],
            [
                'full_name' => 'Juan Dela Cruz',
                'password_hash' => Hash::make('Resident@12345'),
                'role' => 'resident',
                'contact_number' => '09123456789',
                'address' => 'Purok 1, Barangay Cawit, Manito, Albay',
                'purok_id' => $purokOne?->id,
                'birth_date' => '1998-05-15',
                'sex' => 'Male',
                'civil_status' => 'Single',
                'residency_since' => '2018-01-10',
                'is_registered_voter' => true,
                'account_status' => 'active',
                'kyc_status' => 'verified',
                'kyc_verified_at' => $now,
                'created_at' => $now,
            ]
        );

        // 3. Default Primary Official Signatory
        if (BarangayOfficial::count() === 0) {
            BarangayOfficial::create([
                'full_name' => 'ERNISTO DAEN',
                'position' => 'Punong Barangay',
                'designation_title' => 'Punong Barangay / Barangay Captain',
                'is_primary_signatory' => true,
                'is_attest_signatory' => false,
                'is_active' => true,
            ]);
        }

        // 4. Document Types with Full Fee, Validity & Purpose Config
        $types = [
            [
                'code' => 'BARANGAY_CLEARANCE',
                'document_name' => 'Barangay Clearance',
                'description' => 'General barangay clearance certificate',
                'fee' => 50.00,
                'is_fee_exempt_default' => false,
                'validity_days' => 180,
                'requires_business_details' => false,
                'requires_supporting_doc' => false,
                'is_ra11261' => false,
                'allowed_purposes' => [
                    'Local Employment',
                    'Police / NBI Clearance Requirement',
                    'Postal / Government ID Application',
                    'Bank / Loan Application',
                    'Travel / Relocation Requirement',
                ],
            ],
            [
                'code' => 'INDIGENCY',
                'document_name' => 'Certificate of Indigency',
                'description' => 'For medical, educational, or financial assistance',
                'fee' => 0.00,
                'is_fee_exempt_default' => true,
                'validity_days' => 90,
                'requires_business_details' => false,
                'requires_supporting_doc' => false,
                'is_ra11261' => false,
                'allowed_purposes' => [
                    'Medical / Hospital Assistance (MAIP / DSWD)',
                    'Educational / Scholarship Assistance (CHED / LGU)',
                    'Burial / Financial Assistance',
                    'PAO / Free Legal Aid Requirement',
                ],
            ],
            [
                'code' => 'RESIDENCY',
                'document_name' => 'Certificate of Residency',
                'description' => 'Proof of residency in Barangay Cawit',
                'fee' => 50.00,
                'is_fee_exempt_default' => false,
                'validity_days' => 180,
                'requires_business_details' => false,
                'requires_supporting_doc' => false,
                'is_ra11261' => false,
                'allowed_purposes' => [
                    'School Enrollment / Transfer',
                    'Government Agency / PhilSys Verification',
                    'Utility Connection (Water / Electricity)',
                    'Employment / Bank Verification',
                ],
            ],
            [
                'code' => 'BUSINESS_CLEARANCE',
                'document_name' => 'Barangay Business Clearance',
                'description' => 'Required for business permit applications',
                'fee' => 150.00,
                'is_fee_exempt_default' => false,
                'validity_days' => 365,
                'requires_business_details' => true,
                'requires_supporting_doc' => true,
                'is_ra11261' => false,
                'allowed_purposes' => [
                    "New Business Permit (Mayor's Permit Application)",
                    'Annual Business Permit Renewal',
                    'Lending / Microfinance Business Registration',
                ],
            ],
            [
                'code' => 'GOOD_MORAL',
                'document_name' => 'Certificate of Good Moral Character',
                'description' => 'Attestation of good standing in the community',
                'fee' => 50.00,
                'is_fee_exempt_default' => false,
                'validity_days' => 180,
                'requires_business_details' => false,
                'requires_supporting_doc' => false,
                'is_ra11261' => false,
                'allowed_purposes' => [
                    'College / University Admission or Scholarship',
                    'Local / Overseas Employment Requirement',
                    'Professional License / Board Exam Application',
                ],
            ],
            [
                'code' => 'FIRST_TIME_JOBSEEKER',
                'document_name' => 'First-Time Jobseeker Certificate (RA 11261)',
                'description' => 'One-time fee-waived certification for qualified first-time jobseekers with 6+ months residency (RA 11261).',
                'fee' => 0.00,
                'is_fee_exempt_default' => true,
                'validity_days' => 365,
                'requires_business_details' => false,
                'requires_supporting_doc' => false,
                'is_ra11261' => true,
                'allowed_purposes' => [
                    'First-Time Local Employment Application (RA 11261 Fee Waiver)',
                    'NBI / Police / Pre-Employment Clearance Waiver (RA 11261)',
                ],
            ],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(
                ['code' => $type['code']],
                $type + ['is_active' => true]
            );
        }
    }
}