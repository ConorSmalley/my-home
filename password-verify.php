<?php
$password = '4 digit code';

$hash = password_hash($password, PASSWORD_DEFAULT);
// echo $hash;
// $hash = '';
if (password_verify($password, $hash)) {
    echo 'Password correct';
} else {
    echo 'Incorrect password';
}