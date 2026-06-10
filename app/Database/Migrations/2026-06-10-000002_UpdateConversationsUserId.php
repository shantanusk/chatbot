<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateConversationsUserId extends Migration
{
    public function up()
    {
        $this->forge->addColumn('conversations', [
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'id',
            ],
        ]);
        $this->forge->addKey('user_id');
    }

    public function down()
    {
        $this->forge->dropColumn('conversations', 'user_id');
    }
}
