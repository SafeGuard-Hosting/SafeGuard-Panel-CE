<?php
/**
 * SafeGuard Panel — Blesta module.
 *
 * Install: copy this directory to {blesta}/components/modules/safeguard_panel/
 * Server config in Blesta: Hostname = panel host, API key = the provisioning
 * key from SafeGuard Panel → Owner → Billing → Provisioning API Key.
 *
 * Status: reference implementation — follows the Blesta Module contract;
 * test in staging before production.
 */

class SafeguardPanel extends Module
{
    public function __construct()
    {
        Language::loadLang('safeguard_panel', null, dirname(__FILE__) . DS . 'language' . DS);
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    // ─── Server (module row) management ───

    public function manageModule($module, array &$vars)
    {
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'safeguard_panel' . DS);
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        $this->view->set('module', $module);
        return $this->view->fetch();
    }

    public function manageAddRow(array &$vars)
    {
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'safeguard_panel' . DS);
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);
        $this->view->set('vars', (object) $vars);
        return $this->view->fetch();
    }

    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['server_name', 'host_name', 'api_key'];
        $this->Input->setRules($this->rowRules($vars));
        if ($this->Input->validates($vars)) {
            $meta = [];
            foreach ($vars as $key => $value) {
                if (in_array($key, $meta_fields)) {
                    $meta[] = (object) [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => $key === 'api_key' ? 1 : 0,
                    ];
                }
            }
            return $meta;
        }
    }

    public function editModuleRow($module_row, array &$vars)
    {
        return $this->addModuleRow($vars);
    }

    private function rowRules(array &$vars)
    {
        return [
            'host_name' => [
                'valid' => [
                    'rule' => ['matches', '/^[a-z0-9.\-]+$/i'],
                    'message' => Language::_('SafeguardPanel.!error.host_name.valid', true),
                ],
            ],
            'api_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('SafeguardPanel.!error.api_key.empty', true),
                ],
            ],
        ];
    }

    // ─── Package fields ───

    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);
        $fields = new ModuleFields();
        $package = $fields->label(Language::_('SafeguardPanel.package_fields.package', true), 'safeguard_package');
        $package->attach(
            $fields->fieldText('meta[package]', ($vars->meta['package'] ?? ''), ['id' => 'safeguard_package'])
        );
        $package->attach($fields->tooltip(Language::_('SafeguardPanel.package_fields.package_note', true)));
        $fields->setField($package);
        return $fields;
    }

    public function addPackage(array $vars = null)
    {
        $meta = [];
        if (isset($vars['meta'])) {
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = (object) ['key' => $key, 'value' => $value, 'encrypted' => 0];
            }
        }
        return $meta;
    }

    public function editPackage($package, array $vars = null)
    {
        return $this->addPackage($vars);
    }

    // ─── Service lifecycle ───

    public function addService($package, array $vars = null, $parent_package = null, $parent_service = null, $status = 'pending')
    {
        $row = $this->getModuleRow();
        $username = strtolower(preg_replace('/[^a-z0-9]/i', '', $vars['safeguard_username'] ?? strtok($vars['domain'] ?? 'user', '.')));
        $password = $vars['safeguard_password'] ?? $this->generatePassword();

        if ($vars['use_module'] ?? 'false' === 'true') {
            $res = $this->call($row, 'POST', '/api/billing/provision', [
                'username' => $username,
                'email' => $vars['client_email'] ?? ($vars['email'] ?? ''),
                'password' => $password,
                'domain' => strtolower($vars['domain'] ?? ''),
                'package' => $package->meta->package ?? '',
            ]);
            if (empty($res['success'])) {
                $this->Input->setErrors(['api' => ['request' => Language::_('SafeguardPanel.!error.api.request', true, $res['error'] ?? 'unknown')]]);
                return;
            }
        }

        return [
            ['key' => 'safeguard_username', 'value' => $username, 'encrypted' => 0],
            ['key' => 'safeguard_password', 'value' => $password, 'encrypted' => 1],
            ['key' => 'safeguard_domain', 'value' => strtolower($vars['domain'] ?? ''), 'encrypted' => 0],
        ];
    }

    public function suspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        return $this->lifecycle($service, 'suspend');
    }

    public function unsuspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        return $this->lifecycle($service, 'unsuspend');
    }

    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {
        return $this->lifecycle($service, 'terminate');
    }

    private function lifecycle($service, $action)
    {
        $row = $this->getModuleRow();
        $fields = $this->serviceFieldsToObject($service->fields);
        $res = $this->call($row, 'POST', '/api/billing/' . $action, [
            'username' => $fields->safeguard_username ?? '',
        ]);
        if (empty($res['success'])) {
            $this->Input->setErrors(['api' => ['request' => Language::_('SafeguardPanel.!error.api.request', true, $res['error'] ?? 'unknown')]]);
        }
        return null;
    }

    // ─── HTTP ───

    /** Signed REST call to the panel's billing hooks. */
    private function call($row, $method, $path, array $body = [])
    {
        $host = $row->meta->host_name;
        $key = $row->meta->api_key;
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
        $this->log($host . '|' . $path, json_encode($body), 'input', true);
        $this->log($host . '|' . $path, (string) $response, 'output', empty($err));
        if ($err) {
            return ['success' => false, 'error' => $err];
        }
        $decoded = json_decode((string) $response, true);
        return is_array($decoded) ? $decoded : ['success' => false, 'error' => 'invalid response'];
    }

    private function generatePassword($length = 16)
    {
        return substr(str_replace(['/', '+', '='], '', base64_encode(random_bytes(24))), 0, $length) . 'a1!';
    }
}
