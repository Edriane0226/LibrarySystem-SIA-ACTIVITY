<?php
$hostname = gethostname(); // Get the hostname of the server
$local_ip = gethostbyname($hostname); // Resolve the hostname to its IP address

echo "Local IP Address: " . $local_ip;
?>