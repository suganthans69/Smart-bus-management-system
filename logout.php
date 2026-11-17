<?php
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Auth;

Auth::logout();
flash('info', 'You have been safely signed out. See you again!');
redirect(url('/'));

