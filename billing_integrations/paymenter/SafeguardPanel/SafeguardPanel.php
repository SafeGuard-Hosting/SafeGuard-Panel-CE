<?php
/**
 * SafeGuard Panel — Paymenter server extension.
 *
 * Install: copy this directory to {paymenter}/extensions/Servers/SafeguardPanel/
 * Config in Paymenter: Host = panel host, API Key = the provisioning key from
 * SafeGuard Panel → Owner → Billing → Provisioning API Key.
 *
 * Status: reference implementation — follows the Paymenter Server extension
 * contract; test in staging before production.
 */

namespace App\Extensions\Servers\SafeguardPanel;

use App\Classes\Extensions\Server;
use Illuminate\Support\Facades\Http;

class SafeguardPanel extends Server
{
    public function getMetadata()
    {
        return [
            'display_name' => 'SafeGuard Panel',
            'version' => '1.0.0',
            'author' => 'SafeGuard Hosting Inc.',
            'website' => 'https://safeguardpanel.ca',
        ];
    }

    public function getConfig()
    {
        return [
            [
                'name' => 'host',
                'friendlyName' => 'Panel hostname',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'api_key',
                'friendlyName' => 'Provisioning API key (sgb_…)',
                'type' => 'text',
                'required' => true,
            ],
        ];
    }

    public function getProductConfig($options)
    {
        return [
            [
                'name' => 'package',
                'friendlyName' => 'Panel package name (must match Owner → Packages)',
                'type' => 'text',
                'required' => true,
            ],
        ];
    }

    public function createServer($user, $params, $order, $product, $configurableOptions)
    {
        $username = strtolower(preg_replace('/[^a-z0-9]/', '', strtok($user->email, '@')) . $order->id);
        $res = $this->call('POST', '/api/billing/provision', [
            'username' => $username,
            'email' => $user->email,
            'password' => bin2hex(random_bytes(12)),
            'domain' => $params['domain'] ?? '',
            'package' => $params['config']['package'] ?? '',
        ]);
        if (empty($res['success'])) {
            throw new \Exception('SafeGuard Panel provision failed: ' . ($res['error'] ?? 'unknown'));
        }
        return true;
    }

    public function suspendServer($user, $params, $order, $product, $configurableOptions)
    {
        return $this->lifecycle('suspend', $params);
    }

    public function unsuspendServer($user, $params, $order, $product, $configurableOptions)
    {
        return $this->lifecycle('unsuspend', $params);
    }

    public function terminateServer($user, $params, $order, $product, $configurableOptions)
    {
        return $this->lifecycle('terminate', $params);
    }

    private function lifecycle(string $action, $params): bool
    {
        $res = $this->call('POST', '/api/billing/' . $action, [
            'username' => $params['username'] ?? '',
        ]);
        if (empty($res['success'])) {
            throw new \Exception("SafeGuard Panel {$action} failed: " . ($res['error'] ?? 'unknown'));
        }
        return true;
    }

    /** Signed REST call to the panel's billing hooks. */
    private function call(string $method, string $path, array $body = []): array
    {
        $host = $this->config('host');
        $key = $this->config('api_key');
        $response = Http::withToken($key)
            ->timeout(30)
            ->send($method, 'https://' . $host . ':2087' . $path, ['json' => $body]);
        $decoded = $response->json();
        return is_array($decoded) ? $decoded : ['success' => false, 'error' => 'invalid response'];
    }
}
