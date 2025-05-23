<?php
$hashedPassword = '$2y$10$mgIiMgBgPdNk8vASJCzz9.MzoSBrN28p1XWSIpWrIPsz0LL/oCBfe';
$plaintextPassword = '757999ab56a1587e'; // Replace with the password you want to check

if (password_verify($plaintextPassword, $hashedPassword)) {
    echo "Password is correct!";
} else {
    echo "Password is incorrect!";
}  
?>