<?php

namespace Modules\Chatbot\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSoftDeletes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('conversations', [
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'updated_at',
            ],
        ]);
        $this->forge->addColumn('messages', [
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'created_at',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('conversations', 'deleted_at');
        $this->forge->dropColumn('messages', 'deleted_at');
    }
}
