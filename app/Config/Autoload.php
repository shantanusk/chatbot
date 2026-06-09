<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

class Autoload extends AutoloadConfig
{
    public $psr4 = [
        APP_NAMESPACE => APPPATH,
        'Modules\Chatbot' => APPPATH . 'Modules/Chatbot',
    ];

    public $classmap = [];

    public $files = [];

    public $helpers = [];
}
