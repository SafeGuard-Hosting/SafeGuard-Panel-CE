<?php
/**
 * SafeGuard Panel — WHMCS Server Module (spec Section 10)
 *
 * Install: copy this directory to {whmcs}/modules/servers/safeguardpanel/
 * Server config in WHMCS: hostname = panel host, Access Hash = the shared
 * integration key from SafeGuard Panel → Owner → Billing → Provisioning API key.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

function safeguardpanel_MetaData()
{
    return [
        'DisplayName' => 'SafeGuard Panel',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '2087',
        'DefaultSSLPort' => '2087',
    ];
}

function safeguardpanel_ConfigOptions()
{
    return [
        'package' => [
            'FriendlyName' => 'Panel Package Name',
            'Type' => 'text',
            'Size' => '30',
            'Description' => 'Must match a user package name in SafeGuard Panel',
        ],
    ];
}

/** Shared HTTP helper: signed call to the panel API. */
function safeguardpanel_apiCall(array $params, string $method, string $path, array $body = [])
{
    $scheme = !empty($params['serversecure']) ? 'https' : 'http';
    $url = $scheme . '://' . $params['serverhostname'] . ':2087' . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $params['serveraccesshash'],
            'Content-Type: application/json',
        ],
    ]);
    if (!empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
        return ['success' => false, 'error' => $err];
    }
    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : ['success' => false, 'error' => 'invalid response'];
}

function safeguardpanel_CreateAccount(array $params)
{
    $res = safeguardpanel_apiCall($params, 'POST', '/api/billing/provision', [
        'username' => $params['username'],
        'email' => $params['clientsdetails']['email'],
        'password' => $params['password'],
        'domain' => $params['domain'],
        'package' => $params['configoption1'],
    ]);
    return $res['success'] ? 'success' : ('Provision failed: ' . ($res['error'] ?? 'unknown'));
}

function safeguardpanel_SuspendAccount(array $params)
{
    $res = safeguardpanel_apiCall($params, 'POST', '/api/billing/suspend', ['username' => $params['username']]);
    return $res['success'] ? 'success' : ('Suspend failed: ' . ($res['error'] ?? 'unknown'));
}

function safeguardpanel_UnsuspendAccount(array $params)
{
    $res = safeguardpanel_apiCall($params, 'POST', '/api/billing/unsuspend', ['username' => $params['username']]);
    return $res['success'] ? 'success' : ('Unsuspend failed: ' . ($res['error'] ?? 'unknown'));
}

function safeguardpanel_TerminateAccount(array $params)
{
    $res = safeguardpanel_apiCall($params, 'POST', '/api/billing/terminate', ['username' => $params['username']]);
    return $res['success'] ? 'success' : ('Terminate failed: ' . ($res['error'] ?? 'unknown'));
}

function safeguardpanel_ChangePackage(array $params)
{
    $res = safeguardpanel_apiCall($params, 'POST', '/api/billing/change-package', [
        'username' => $params['username'],
        'package' => $params['configoption1'],
    ]);
    return $res['success'] ? 'success' : ('Package change failed: ' . ($res['error'] ?? 'unknown'));
}

function safeguardpanel_sso(array $params)
{
    $res = safeguardpanel_apiCall($params, 'GET', '/api/billing/sso?username=' . urlencode($params['username']));
    if (!empty($res['success']) && !empty($res['data']['url'])) {
        $scheme = !empty($params['serversecure']) ? 'https' : 'http';
        return [
            'success' => true,
            'redirectTo' => $scheme . '://' . $params['serverhostname'] . ':2087' . $res['data']['url'],
        ];
    }
    return ['success' => false, 'errorMsg' => $res['error'] ?? 'SSO failed'];
}

function safeguardpanel_AdminSingleSignOn(array $params)
{
    return safeguardpanel_sso($params);
}

function safeguardpanel_ClientAreaSingleSignOn(array $params)
{
    return safeguardpanel_sso($params);
}
