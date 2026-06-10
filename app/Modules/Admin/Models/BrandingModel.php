<?php

namespace Modules\Admin\Models;

use CodeIgniter\Model;

class BrandingModel extends Model
{
    protected $table            = 'branding_settings';
    protected $primaryKey       = 'id';
    protected $allowedFields    = [
        'app_name', 'welcome_title', 'welcome_subtitle',
        'logo_path', 'favicon_path', 'footer_text',
        'primary_color_start', 'primary_color_mid', 'primary_color_end',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = '';
    protected $returnType       = 'object';

    public function getSettings()
    {
        $settings = $this->find(1);
        if (! $settings) {
            $this->insert(['id' => 1]);
            $settings = $this->find(1);
        }
        return $settings;
    }
}
