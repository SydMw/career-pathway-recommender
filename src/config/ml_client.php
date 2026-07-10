<?php
// Thin HTTP client for the Machine Learning Module API (report 3.4.2 / FR4).
define('ML_API_URL', 'http://127.0.0.1:5001/predict');
define('ML_RETRAIN_URL', 'http://127.0.0.1:5001/retrain');

function get_recommendation(array $payload): array
{
    $ch = curl_init(ML_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['error' => "Could not reach ML service: $error"];
    }

    $decoded = json_decode($response, true);
    if ($status !== 200) {
        return ['error' => $decoded['error'] ?? 'ML service returned an error'];
    }

    return $decoded;
}

// FR8: allows an authorized administrator to retrain the model.
function trigger_retrain(): array
{
    $ch = curl_init(ML_RETRAIN_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['error' => "Could not reach ML service: $error"];
    }

    $decoded = json_decode($response, true);
    if ($status !== 200) {
        return ['error' => $decoded['error'] ?? 'Retrain failed'];
    }

    return $decoded;
}
