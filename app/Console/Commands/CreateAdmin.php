<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'greenfood:create-admin {email} {--name=GreenFood Administrator}';

    protected $description = 'Create a new administrator with a hidden password prompt; never promote an existing user';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $name = $this->option('name');
        $validator = Validator::make(compact('email', 'name'), [
            'email' => 'required|email|max:255|unique:users,email',
            'name' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        if (! $this->input->isInteractive()) {
            $this->error('Run interactively to enter a hidden password.');

            return self::FAILURE;
        }
        $password = $this->secret('Password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');
        if (! is_string($password) || strlen($password) < 12 || $password !== $confirmation) {
            $this->error('Passwords must match and contain at least 12 characters.');

            return self::FAILURE;
        }
        User::create([
            'name' => $name, 'email' => $email, 'password' => Hash::make($password),
            'Role' => 1, 'Status' => 1,
        ]);
        $this->info('Administrator created.');

        return self::SUCCESS;
    }
}
