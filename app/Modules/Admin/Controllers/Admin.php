<?php

namespace Modules\Admin\Controllers;

use CodeIgniter\Controller;
use Modules\Admin\Models\BrandingModel;

class Admin extends Controller
{
    public function index()
    {
        helper('url');
        $model = new BrandingModel();
        $settings = $model->getSettings();

        return view('Modules\Admin\Views\dashboard', [
            'settings' => $settings,
        ]);
    }

    public function settings()
    {
        helper('url');
        $model = new BrandingModel();
        $settings = $model->getSettings();

        return view('Modules\Admin\Views\settings', [
            'settings' => $settings,
        ]);
    }

    public function save()
    {
        helper('url');

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $model = new BrandingModel();
        $data = [];

        $fields = [
            'app_name', 'welcome_title', 'welcome_subtitle',
            'footer_text',
            'primary_color_start', 'primary_color_mid', 'primary_color_end',
        ];

        foreach ($fields as $field) {
            $val = $this->request->getPost($field);
            if ($val !== null) {
                $data[$field] = trim($val);
            }
        }

        // Handle logo upload
        $logo = $this->request->getFile('logo');
        if ($logo && $logo->isValid() && ! $logo->hasMoved()) {
            if ($logo->isValid() && in_array($logo->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])) {
                $newName = 'logo.' . $logo->getExtension();
                $logo->move('uploads/branding', $newName, true);
                $data['logo_path'] = 'uploads/branding/' . $newName;
            }
        }

        // Handle favicon upload
        $favicon = $this->request->getFile('favicon');
        if ($favicon && $favicon->isValid() && ! $favicon->hasMoved()) {
            if ($favicon->isValid() && in_array($favicon->getMimeType(), ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/jpeg', 'image/svg+xml'])) {
                $newName = 'favicon.' . $favicon->getExtension();
                $favicon->move('uploads/branding', $newName, true);
                $data['favicon_path'] = 'uploads/branding/' . $newName;
            }
        }

        if (! empty($data)) {
            $model->update(1, $data);
        }

        return $this->response->setJSON(['success' => true]);
    }
}
