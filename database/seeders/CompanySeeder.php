<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Site;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample companies
        $companies = [
            [
                'name' => 'Woolworths Group',
                'email' => 'contact@woolworths.com.au',
                'phone' => '+61 2 8885 3000',
                'address' => '1 Woolworths Way, Bella Vista NSW 2153',
                'contact_person' => 'John Smith',
                'registration_number' => 'ACN 000 014 675',
                'branches' => [
                    [
                        'name' => 'NSW Operations',
                        'email' => 'nsw@woolworths.com.au',
                        'phone' => '+61 2 8885 3100',
                        'address' => 'Sydney Distribution Centre, NSW',
                        'contact_person' => 'Sarah Johnson',
                        'sites' => [
                            [
                                'name' => 'Sydney CBD Store',
                                'address' => '123 George Street, Sydney NSW 2000',
                                'contact_person' => 'Mike Wilson',
                                'phone' => '+61 2 8885 3200',
                                'email' => 'cbd001@woolworths.com.au',
                            ],
                            [
                                'name' => 'Bondi Junction Store',
                                'address' => '500 Oxford Street, Bondi Junction NSW 2022',
                                'contact_person' => 'Lisa Brown',
                                'phone' => '+61 2 8885 3201',
                                'email' => 'bj002@woolworths.com.au',
                            ],
                        ],
                    ],
                    [
                        'name' => 'VIC Operations',
                        'email' => 'vic@woolworths.com.au',
                        'phone' => '+61 3 8885 4100',
                        'address' => 'Melbourne Distribution Centre, VIC',
                        'contact_person' => 'David Lee',
                        'sites' => [
                            [
                                'name' => 'Melbourne Central Store',
                                'address' => '211 La Trobe Street, Melbourne VIC 3000',
                                'contact_person' => 'Emma Davis',
                                'phone' => '+61 3 8885 4200',
                                'email' => 'mc003@woolworths.com.au',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Coles Group',
                'email' => 'contact@coles.com.au',
                'phone' => '+61 3 9829 3111',
                'address' => '800 Toorak Road, Tooronga VIC 3146',
                'contact_person' => 'Robert Taylor',
                'registration_number' => 'ACN 004 089 936',
                'branches' => [
                    [
                        'name' => 'QLD Operations',
                        'email' => 'qld@coles.com.au',
                        'phone' => '+61 7 3829 5000',
                        'address' => 'Brisbane Distribution Centre, QLD',
                        'contact_person' => 'Amanda White',
                        'sites' => [
                            [
                                'name' => 'Brisbane City Store',
                                'address' => 'Queen Street Mall, Brisbane QLD 4000',
                                'contact_person' => 'James Green',
                                'phone' => '+61 7 3829 5100',
                                'email' => 'bc001@coles.com.au',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Waterfront Waste Services',
                'address' => 'Dockside Avenue, Cape Town Harbour',
                'contact_person' => 'Sipho Nkosi',
                'phone' => '+27 21 555 1111',
                'email' => 'dock@wws.co.za',
            ],
            [
                'name' => 'Brickworks Industrial',
                'address' => 'Industrial Road, Brackenfell',
                'contact_person' => 'Lindiwe Mkhize',
                'phone' => '+27 21 555 2222',
                'email' => 'industrial@wws.co.za',
            ],
            [
                'name' => 'ABC Company',
                'email' => 'contact@abccompany.com',
                'phone' => '+27 21 555 3333',
                'address' => '123 Main Street, Cape Town, 8001',
                'contact_person' => 'Jane Doe',
                'registration_number' => 'REG123456',
                'branches' => [
                    [
                        'name' => 'ABC Branch',
                        'email' => 'branch@abccompany.com',
                        'phone' => '+27 21 555 3334',
                        'address' => '456 Branch Road, Cape Town, 8001',
                        'contact_person' => 'John Smith',
                        'sites' => [
                            [
                                'name' => 'ABC Collection Point',
                                'address' => '789 Collection Street, Cape Town, 8001',
                                'contact_person' => 'Bob Johnson',
                                'phone' => '+27 21 555 3335',
                                'email' => 'collection@abccompany.com',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($companies as $companyData) {
            $branches = $companyData['branches'] ?? [];
            unset($companyData['branches']);
            
            $company = Company::create($companyData);
            
            if (!empty($branches)) {
                foreach ($branches as $branchData) {
                    $sites = $branchData['sites'] ?? [];
                    unset($branchData['sites']);
                    $branchData['company_id'] = $company->id;
                    
                    $branch = Branch::create($branchData);
                    
                    if (!empty($sites)) {
                        foreach ($sites as $siteData) {
                            $siteData['branch_id'] = $branch->id;
                            Site::create($siteData);
                        }
                    }
                }
            }
        }
    }
}