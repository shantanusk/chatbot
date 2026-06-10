<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\CLI\CLI;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $username = CLI::prompt('Admin username');
        $email    = CLI::prompt('Admin email');
        $password = CLI::prompt('Admin password');

        $this->db->table('users')->insert([
            'username' => $username,
            'email'    => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'is_admin' => 1,
        ]);

        echo "Admin user '{$username}' created successfully.\n";
    }
}
