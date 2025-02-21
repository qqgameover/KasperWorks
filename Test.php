<?php

require_once 'vendor/autoload.php';

use Kasperworks\Models\User;

$user = User::find(13);
$user = $user->update(['email' => 'testeded@gmail.com']);

var_dump($user->toArray());

$user->delete();
