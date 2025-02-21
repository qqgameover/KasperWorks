<?php

require_once 'vendor/autoload.php';

use Kasperworks\Models\User;

//$user = $user->update(['email' => 'testeded@gmail.com']);

User::query()->update(['email' => 'HEHEHEHE@email.com'], ['id', '=', 13]);

$user = User::find(13);

var_dump($user->toArray());

//$user->delete();
