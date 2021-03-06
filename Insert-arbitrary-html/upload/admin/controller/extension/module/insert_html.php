<?php

class ControllerExtensionModuleInsertHtml extends Controller {
    private $error = [];

    public function index() {
        $this->load->language('extension/module/insert_html');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/module');

        $queryStringWithUserTokenAndType = http_build_query([
            'user_token' => $this->session->data['user_token'],
            'type' => 'module'
        ]);

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if (!isset($this->request->get['module_id'])) {
                $this->model_setting_module->addModule('insert_html', $this->request->post);
            } else {
                $this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
            }

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link(
                'marketplace/extension',
                $queryStringWithUserTokenAndType,
                true
            ));
        }

        $errorsToData = ['warning', 'name', 'html'];

        foreach ($errorsToData as $value) {
            $data["error_$value"] = isset($this->error[$value]) ? $this->error[$value] : '';
        }

        $queryStringWithUserToken = http_build_query([
            'user_token' => $this->session->data['user_token']
        ]);

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $queryStringWithUserToken, true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', $queryStringWithUserTokenAndType, true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'extension/module/insert_html',
                $queryStringWithUserTokenAndModuleId = http_build_query([
                    'user_token' => $this->session->data['user_token'],
                    'module_id' => $this->request->get['module_id'] ?? null
                ]),
                true
            )
        ];

        $data['action'] = $this->url->link(
            'extension/module/insert_html',
            $queryStringWithUserTokenAndModuleId,
            true
        );

        $data['cancel'] = $this->url->link(
            'marketplace/extension',
            $queryStringWithUserTokenAndType,
            true
        );

        if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $moduleInfo = $this->model_setting_module->getModule($this->request->get['module_id']);
        }

        $forValidation = [
            ['name' => 'name', 'default' => ''],
            ['name' => 'status', 'default' => ''],
            ['name' => 'html', 'default' => '']
        ];

        foreach ($forValidation as $value) {
            if (isset($this->request->post[$value['name']])) {
                $data[$value['name']] = $this->request->post[$value['name']];
            } elseif (!empty($moduleInfo)) {
                $data[$value['name']] = $moduleInfo[$value['name']];
            } else {
                $data[$value['name']] = $value['default'];
            }
        }

        $data['current_theme'] = $this->request->cookie['current_theme'] ?? 'monokai';
        $data['themes'] = $this->getCodeMirrorThemes();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/insert_html', $data));
    }

    private function getCodeMirrorThemes() {
        $themes = [];

        foreach (glob(DIR_APPLICATION . 'view/javascript/codemirror/theme/*.css') as $file) {
            array_push($themes, basename($file, '.css'));
        }

        return $themes;
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/insert_html')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (($nameLength = utf8_strlen($this->request->post['name']) < 3) || ($nameLength > 64)) {
            $this->error['name'] = $this->language->get('error_name');
        }

        if (!$this->request->post['html']) {
            $this->error['html'] = $this->language->get('html');
        }

        return !$this->error;
    }
}