<?php

namespace Modules\Chatbot\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddChatbotFeatures extends Migration
{
    public function up()
    {
        $this->forge->addColumn('conversations', [
            'model' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'gemma:2b',
                'after'      => 'title',
            ],
            'system_prompt' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'model',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('conversations', 'model');
        $this->forge->dropColumn('conversations', 'system_prompt');
    }
}
