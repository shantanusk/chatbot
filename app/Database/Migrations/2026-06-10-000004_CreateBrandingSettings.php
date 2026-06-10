<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBrandingSettings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'app_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'AI ChatBot',
            ],
            'welcome_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'default'    => "Hello! I'm {app_name}",
            ],
            'welcome_subtitle' => [
                'type'       => 'VARCHAR',
                'constraint' => 300,
                'default'    => 'Ask me anything — I\'m here to help!',
            ],
            'logo_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'favicon_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'footer_text' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'primary_color_start' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'default'    => '#667eea',
            ],
            'primary_color_mid' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'default'    => '#764ba2',
            ],
            'primary_color_end' => [
                'type'       => 'VARCHAR',
                'constraint' => 7,
                'default'    => '#f093fb',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('branding_settings');

        // Insert default row
        $this->db->table('branding_settings')->insert([
            'id' => 1,
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('branding_settings');
    }
}
