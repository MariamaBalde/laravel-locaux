<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+221774047668',
            'address' => 'Dakar, Sénégal',
            'statut' => 'actif',
            'country' => 'SN',
            'email_verified_at' => now(),
        ]);

        $vendeurs = [
            [
                'name' => 'Khady Baldé',
                'email' => 'khady@gmail.com',
                'phone' => '+22178 264 94 67',
                'address' => 'Médina, Dakar',
                'country' => 'SN',
            ],

            [
                'name' => 'Moussa Sall',
                'email' => 'moussa@plateforme.sn',
                'phone' => '+221773456789',
                'address' => 'Plateau, Dakar',
                'country' => 'CI',
            ],

            [
                'name' => 'Fatou Diallo',
                'email' => 'fatou@karite.ml',
                'phone' => '+223 76 456 78 90',
                'address' => 'Bamako, Mali',
                'country' => 'ML',
            ],

        ];

        foreach ($vendeurs as $vendeurData) {
            User::create([
                'name' => $vendeurData['name'],
                'email' => $vendeurData['email'],
                'password' => Hash::make('password'),
                'role' => 'vendeur',
                'phone' => $vendeurData['phone'],
                'address' => $vendeurData['address'],
                'country' => $vendeurData['country'],
                'statut' => 'actif',
                'email_verified_at' => now(),
            ]);
        }
        // Client
        $clients = [
            [
                'name' => 'Adama Ba',
                'email' => 'adama@gmail.com',
                'phone' => '+22178 107 82 63',
                'address' => 'Paris, France',
                'country' => 'France',
            ],
            [
                'name' => 'Marie Martin',
                'email' => 'marie.martin@gmail.com',
                'phone' => '+33 6 23 45 67 89',
                'address' => '25 Avenue de Lyon, Lyon',
                'country' => 'FR',
            ],
            [
                'name' => 'Mamadou Sow',
                'email' => 'mamadou@example.sn',
                'phone' => '+221 77 567 89 01',
                'address' => 'Pikine, Dakar',
                'country' => 'SN',
            ],
        ];

        foreach ($clients as $clientData) {
            User::create([
                'name' => $clientData['name'],
                'email' => $clientData['email'],
                'password' => Hash::make('password'),
                'role' => 'client',
                'phone' => $clientData['phone'],
                'address' => $clientData['address'],
                'country' => $clientData['country'],
                'statut' => 'actif',
                'email_verified_at' => now(),
            ]);
        }
    }
}
