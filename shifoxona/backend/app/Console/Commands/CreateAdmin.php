<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'shifo:create-admin';

    protected $description = 'Yangi administrator hisobini yaratish';

    public function handle(): int
    {
        $data = [
            'name' => trim((string) $this->ask('Ism va familiya')),
            'email' => strtolower(trim((string) $this->ask('Email'))),
            'password' => (string) $this->secret('Parol (kamida 12 belgi)'),
            'password_confirmation' => (string) $this->secret('Parolni takrorlang'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'admin',
        ]);

        $this->info('Admin yaratildi: '.$data['email']);

        return self::SUCCESS;
    }
}
