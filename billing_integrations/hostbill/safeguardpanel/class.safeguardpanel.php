<?php
/**
 * SafeGuard Panel — HostBill hosting module.
 *
 * Install: copy this directory to {hostbill}/includes/modules/Hosting/safeguardpanel/
 * Server config in HostBill: Hostname = panel host, Hash = the provisioning
 * key from SafeGuard Panel → Owner → Billing → Provisioning API Key.
 *
 * Status: reference implementation — follows the HostingModule contract;
 * test in staging before production. (HostBill can also run WHMCS-format
 * modules through its compatibility layer — ../whmcs/ is an alternative.)
 */

class Safeguardpanel extends HostingModule
{
    protected $modname = 'SafeGuard Panel';
    protected $description = 'Provision hosting accounts on SafeGuard Panel';
    protected $version = '1.0.0';

    protected $serverFields = [
        'hostname' => true,
        'hash' => true,   // the sgb_… provisioning key
    ];

    protected $serverFieldsDescription = [
        'hash' => 'Provisioning API key (sgb_…) from SafeGuard Panel → Owner → Billing',
    ];

    protected $options = [
        'package' => [
            'name' => 'Panel package name',
            'value' => '',
            'type' => 'input',
            'description' => 'Must exactly match a user package in SafeGuard Panel (Owner → Packages)',
        ],
    ];

    protected $details = [
        'option1' => ['name' => 'username', 'value' => false, 'type' => 'input', 'default' => false],
        'option2' => ['name' => 'password', 'value' => false, 'type' => 'input', 'default' => false],
        'option3' => ['name' => 'domain', 'value' => false, 'type' => 'input', 'default' => false],
    ];

    public function testConnection()
    {
        $res = $this->call('GET', '/api/health');
        if (empty($res['success'])) {
            $this->addError('SafeGuard Panel is not reachable');
            return false;
        }
        $this->addInfo('Connection OK');
        return true;
    }

    public function Create()
    {
        $res = $this->call('POST', '/api/billing/provision', [
            'username' => $this->options['option1']['value'],
            'email' => $this->client_data['email'],
            'password' => $this->options['option2']['value'],
            'domain' => $this->options['option3']['value'],
            'package' => $this->product_details['options']['package'] ?? '',
        ]);
        return $this->okOrError($res, 'create');
    }

    public function Suspend()
    {
        return $this->okOrError(
            $this->call('POST', '/api/billing/suspend', ['username' => $this->options['option1']['value']]),
            'suspend'
        );
    }

    public function Unsuspend()
    {
        return $this->okOrError(
            $this->call('POST', '/api/billing/unsuspend', ['username' => $this->options['option1']['value']]),
            'unsuspend'
        );
    }

    public function Terminate()
    {
        return $this->okOrError(
            $this->call('POST', '/api/billing/terminate', ['username' => $this->options['option1']['value']]),
            'terminate'
        );
    }

    public function ChangePackage()
    {
        return $this->okOrError(
            $this->call('POST', '/api/billing/change-package', [
                'username' => $this->options['option1']['value'],
                'package' => $this->product_details['options']['package'] ?? '',
            ]),
            'change package for'
        );
    }

    /** Signed REST call to the panel's billing hooks. */
    private function call($method, $path, array $body = [])
    {
        $host = $this->connection['hostname'];
        $key = $this->connection['hash'];
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

    private function okOrError($res, $what)
    {
        if (empty($res['success'])) {
            $this->addError('SafeGuard Panel could not ' . $what . ' the account: ' . ($res['error'] ?? 'unknown error'));
            return false;
        }
        return true;
    }
}
