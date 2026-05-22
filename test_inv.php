<?php
$ch = curl_init('http://localhost:8000/transactions-api.php?endpoint=inventory-report');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
echo curl_exec($ch);
