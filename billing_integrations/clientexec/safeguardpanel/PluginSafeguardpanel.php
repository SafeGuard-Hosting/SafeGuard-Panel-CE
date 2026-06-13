<?php
/**
 * SafeGuard Panel — ClientExec server plugin.
 *
 * Install: copy this directory to {clientexec}/plugins/server/safeguardpanel/
 * Server config in ClientExec: Hostname = panel host, Access Hash = the
 * provisioning key from SafeGuard Panel → Owner → Billing → Provisioning API Key.
 *
 * Status: reference implementation — follows the ServerPlugin contract;
 * test in staging before production.
 */

require_once 'modules/admin/models/ServerPlugin.php';

class PluginSafeguardpanel extends ServerPlugin
{
    public $features = [
        'packageName' => true,
        'testConnection' => true,
        'showNameservers' => false,
        'directlink' => true,
    ];

    public function getVariables()
    {
        return [
            'Name' => [
                'type' => 'hidden',
                'description' => 'Plugin name',
                'value' => 'SafeGuard Panel',
            ],
            'Description' => [
                'type' => 'hidden',
                'description' => 'Plugin description',
                'value' => 'Provision hosting accounts on SafeGuard Panel',
            ],
            'Provisioning API Key' => [
                'type' => 'password',
                'description' => 'The sgb_… key from SafeGuard Panel → Owner → Billing → Provisioning API Key',
                'value' => '',
            ],
            'Actions' => [
                'type' => 'hidden',
                'description' => 'Supported actions',
                'value' => 'Create,Delete,Suspend,UnSuspend',
            ],
        ];
    }

    public function validateCredentials($args)
    {
        return $args['package']['username'] ?? '';
    }

    public function testConnection($args)
    {
        $res = $this->call($args, 'GET', '/api/health');
        if (empty($res['success'])) {
            throw new CE_Exception('SafeGuard Panel is not reachable');
        }
    }

    public function create($args)
    {
        $res = $this->call($args, 'POST', '/api/billing/provision', [
            'username' => $args['package']['username'],
            'email' => $args['customer']['email'],
            'password' => $args['package']['password'],
            'domain' => $args['package']['domain_name'],
            'package' => $args['package']['name_on_server'] ?? '',
        ]);
        $this->okOrThrow($res, 'create the account');
    }

    public function suspend($args)
    {
        $this->okOrThrow(
            $this->call($args, 'POST', '/api/billing/suspend', ['username' => $args['package']['username']]),
            'suspend the account'
        );
    }

    public function unsuspend($args)
    {
        $this->okOrThrow(
            $this->call($args, 'POST', '/api/billing/unsuspend', ['username' => $args['package']['username']]),
            'unsuspend the account'
        );
    }

    public function delete($args)
    {
        $this->okOrThrow(
            $this->call($args, 'POST', '/api/billing/terminate', ['username' => $args['package']['username']]),
            'terminate the account'
        );
    }

    public function getAvailableActions($userPackage)
    {
        return ['Create', 'Delete', 'Suspend', 'UnSuspend'];
    }

    public function getDirectLink($userPackage, $getRealLink = true, $fromAdmin = false, $isReseller = false)
    {
        $args = $this->buildParams($userPackage);
        $res = $this->call($args, 'GET', '/api/billing/sso?username=' . urlencode($args['package']['username']));
        $host = $args['server']['variables']['ServerHostName'] ?? '';
        if (!empty($res['success']) && !empty($res['data']['url'])) {
            return [
                'link' => 'https://' . $host . ':2087' . $res['data']['url'],
                'rawlink' => 'https://' . $host . ':2087' . $res['data']['url'],
                'form' => '',
            ];
        }
        return ['link' => 'https://' . $host . ':2087', 'rawlink' => 'https://' . $host . ':2087', 'form' => ''];
    }

    /** Signed REST call to the panel's billing hooks. */
    private function call($args, $method, $path, array $body = [])
    {
        $host = $args['server']['variables']['ServerHostName'] ?? '';
        $key = $args['server']['variables']['plugin_safeguardpanel_Provisioning_API_Key'] ?? '';
        $ch = curl_init('https://' . $host . ':2087' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $key,
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
        $decoded = json_decode((string) $response, true);
        return is_array($decoded) ? $decoded : ['success' => false, 'error' => 'invalid response'];
    }

    private function okOrThrow($res, $what)
    {
        if (empty($res['success'])) {
            throw new CE_Exception('SafeGuard Panel could not ' . $what . ': ' . ($res['error'] ?? 'unknown error'));
        }
    }
}
