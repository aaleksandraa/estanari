<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@e-stanari.ba',
            'password' => Hash::make('password'),
        ]);
        UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);

        // Create accountant user
        $accountant = User::create([
            'name' => 'Računovodstvo',
            'email' => 'racunovodstvo@e-stanari.ba',
            'password' => Hash::make('password'),
        ]);
        UserRole::create(['user_id' => $accountant->id, 'role' => 'accountant']);

        // Create viewer user
        $viewer = User::create([
            'name' => 'Pregled User',
            'email' => 'pregled@e-stanari.ba',
            'password' => Hash::make('password'),
        ]);
        UserRole::create(['user_id' => $viewer->id, 'role' => 'viewer']);

        // Create suppliers
        $suppliers = [
            ['name' => 'Sarajevo Trade d.o.o.', 'email' => 'info@sarajevotrade.ba', 'phone' => '+387 33 123 456', 'address' => 'Maršala Tita 28, Sarajevo'],
            ['name' => 'Banja Luka Commerce', 'email' => 'office@blcommerce.ba', 'phone' => '+387 51 234 567', 'address' => 'Kralja Petra I 100, Banja Luka'],
            ['name' => 'Mostar Solutions d.o.o.', 'email' => 'kontakt@mostarsolutions.ba', 'phone' => '+387 36 345 678', 'address' => 'Fejićeva 15, Mostar'],
            ['name' => 'Tuzla Export Import', 'email' => 'sales@tuzlaexport.ba', 'phone' => '+387 35 456 789', 'address' => null, 'is_active' => false],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::create($supplierData);
        }

        // Create branches
        $branches = [
            ['supplier_id' => 1, 'name' => 'Centrala Sarajevo', 'address' => 'Maršala Tita 28'],
            ['supplier_id' => 1, 'name' => 'Poslovnica Ilidža', 'address' => 'Butmirska cesta 12'],
            ['supplier_id' => 2, 'name' => 'Glavna poslovnica', 'address' => 'Kralja Petra I 100'],
            ['supplier_id' => 2, 'name' => 'Poslovnica Laktaši', 'address' => 'Karađorđeva 50'],
            ['supplier_id' => 3, 'name' => 'Centrala Mostar', 'address' => 'Fejićeva 15'],
            ['supplier_id' => 4, 'name' => 'Poslovnica Tuzla', 'address' => 'Turalibegova 22', 'is_active' => false],
        ];

        foreach ($branches as $branchData) {
            Branch::create($branchData);
        }

        // Create payments
        $today = now();
        $payments = [
            ['supplier_id' => 1, 'branch_id' => 1, 'amount' => 2500.00, 'currency' => 'KM', 'status' => 'PLANNED', 'planned_date' => $today, 'description' => 'Mjesečna faktura - Januar', 'created_by' => $admin->id],
            ['supplier_id' => 2, 'branch_id' => 3, 'amount' => 1200.00, 'currency' => 'EUR', 'status' => 'PLANNED', 'planned_date' => $today, 'description' => 'Roba - Serija A', 'created_by' => $admin->id],
            ['supplier_id' => 3, 'branch_id' => 5, 'amount' => 850.00, 'currency' => 'KM', 'status' => 'PLANNED', 'planned_date' => $today->copy()->addDay(), 'description' => 'Usluge konsaltinga', 'created_by' => $admin->id],
            ['supplier_id' => 1, 'branch_id' => 2, 'amount' => 3200.00, 'currency' => 'KM', 'status' => 'PLANNED', 'planned_date' => $today->copy()->addDays(2), 'description' => 'Oprema - Batch 2', 'created_by' => $admin->id],
            ['supplier_id' => 2, 'branch_id' => 4, 'amount' => 750.00, 'currency' => 'EUR', 'status' => 'PLANNED', 'planned_date' => $today->copy()->addDays(3), 'description' => 'Materijal za proizvodnju', 'created_by' => $admin->id],
            ['supplier_id' => 3, 'branch_id' => 5, 'amount' => 4500.00, 'currency' => 'KM', 'status' => 'PLANNED', 'planned_date' => $today->copy()->addDays(5), 'description' => 'Godišnji ugovor - rata 1', 'created_by' => $admin->id],
            ['supplier_id' => 1, 'branch_id' => 1, 'amount' => 1800.00, 'currency' => 'EUR', 'status' => 'PLANNED', 'planned_date' => $today->copy()->addDays(6), 'description' => 'IT oprema', 'created_by' => $admin->id],
            ['supplier_id' => 2, 'branch_id' => 3, 'amount' => 5000.00, 'currency' => 'KM', 'status' => 'PAID', 'planned_date' => $today->copy()->subDays(3), 'paid_date' => $today->copy()->subDays(3), 'description' => 'Kvartalna faktura Q4', 'created_by' => $admin->id, 'paid_by' => $admin->id],
            ['supplier_id' => 1, 'branch_id' => 1, 'amount' => 2200.00, 'currency' => 'EUR', 'status' => 'PAID', 'planned_date' => $today->copy()->subDays(5), 'paid_date' => $today->copy()->subDays(4), 'description' => 'Provizija - Decembar', 'created_by' => $admin->id, 'paid_by' => $accountant->id],
            ['supplier_id' => 3, 'branch_id' => 5, 'amount' => 1500.00, 'currency' => 'KM', 'status' => 'PAID', 'planned_date' => $today->copy()->subDays(7), 'paid_date' => $today->copy()->subDays(7), 'description' => 'Održavanje sistema', 'created_by' => $admin->id, 'paid_by' => $admin->id],
        ];

        foreach ($payments as $paymentData) {
            Payment::create($paymentData);
        }
    }
}
