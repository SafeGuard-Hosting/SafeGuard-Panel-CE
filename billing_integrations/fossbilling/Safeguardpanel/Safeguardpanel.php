<?php
/**
 * SafeGuard Panel — FOSSBilling server manager.
 *
 * Install: copy this file to {fossbilling}/library/Server/Manager/Safeguardpanel.php
 * Server config in FOSSBilling: Hostname = panel host, Access Hash = the
 * provisioning key from SafeGuard Panel → Owner → Billing → Provisioning API Key.
 *
 * Status: reference implementation — follows the documented Server_Manager
 * contract; test in staging before production.
 */

class Server_Manager_Safeguardpanel extends Server_Manager
{
    public static function getForm(): array
    {
        return [
            'label' => 'SafeGuard Panel',
            'form' => [
                'credentials' => [
                    'fields' => [
                        [
                            'name' => 'accesshash',
                            'type' => 'text',
                            'label' => 'Provisioning API key (sgb_…)',
                            'placeholder' => 'sgb_…',
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function init()
    {
        if (empty($this->_config['host'])) {
            throw new Server_Exception('SafeGuard Panel: hostname is required');
        }
        if (empty($this->_config['accesshash'])) {
            throw new Server_Exception('SafeGuard Panel: the provisioning API key (Access Hash field) is required');
        }
    }

    public function getLoginUrl(?Server_Account $account = null): string
    {
        return 'https://' . $this->_config['host'] . ':2087';
    }

    public function getResellerLoginUrl(?Server_Account $account = null): string
    {
        return $this->getLoginUrl($account);
    }

    public function testConnection(): bool
    {
        $res = $this->request('GET', '/api/health');
        if (empty($res['success'])) {
            throw new Server_Exception('SafeGuard Panel is not reachable');
        }
        return true;
    }

    public function synchronizeAccount(Server_Account $account): Server_Account
    {
        return $account; // the panel is the source of truth; nothing to pull
    }

    public function createAccount(Server_Account $account): bool
    {
        $client = $account->getClient();
        $package = $account->getPackage();
        $res = $this->request('POST', '/api/billing/provision', [
            'username' => $account->getUsername(),
            'email' => $client->getEmail(),
            'password' => $account->getPassword(),
            'domain' => $account->getDomain(),
            'package' => $package ? $package->getName() : '',
        ]);
        return $this->okOrThrow($res, 'create account');
    }

    public function suspendAccount(Server_Account $account): bool
    {
        return $this->okOrThrow(
            $this->request('POST', '/api/billing/suspend', ['username' => $account->getUsername()]),
            'suspend account'
        );
    }

    public function unsuspendAccount(Server_Account $account): bool
    {
        return $this->okOrThrow(
            $this->request('POST', '/api/billing/unsuspend', ['username' => $account->getUsername()]),
            'unsuspend account'
        );
    }

    public function cancelAccount(Server_Account $account): bool
    {
        return $this->okOrThrow(
            $this->request('POST', '/api/billing/terminate', ['username' => $account->getUsername()]),
            'terminate account'
        );
    }

    public function changeAccountPackage(Server_Account $account, Server_Package $package): bool
    {
        return $this->okOrThrow(
            $this->request('POST', '/api/billing/change-package', [
                'username' => $account->getUsername(),
                'package' => $package->getName(),
            ]),
            'change package'
        );
    }

    public function changeAccountUsername(Server_Account $account, $newUsername): bool
    {
        throw new Server_Exception('SafeGuard Panel does not support renaming accounts');
    }

    public function changeAccountDomain(Server_Account $account, $newDomain): bool
    {
        throw new Server_Exception('Change the domain from inside SafeGuard Panel');
    }

    public function changeAccountPassword(Server_Account $account, $newPassword): bool
    {
        throw new Server_Exception('Change the password from inside SafeGuard Panel');
    }

    public function changeAccountIp(Server_Account $account, $newIp): bool
    {
        throw new Server_Exception('Change the IP from inside SafeGuard Panel');
    }

    /** Signed REST call to the panel's billing hooks. */
    private function request(string $method, string $path, array $body = []): array
    {
        $url = 'https://' . $this->_config['host'] . ':2087' . $path;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->_config['accesshash'],
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

    private function okOrThrow(array $res, string $what): bool
    {
        if (empty($res['success'])) {
            throw new Server_Exception('SafeGuard Panel could not ' . $what . ': ' . ($res['error'] ?? 'unknown error'));
        }
        return true;
    }
}
