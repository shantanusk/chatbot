<?php

namespace Modules\Chatbot\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ChatbotSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('conversations')->insert([
            'session_id' => 'seed-sample',
            'title'      => 'Welcome Chat',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $conversationId = $this->db->insertId();

        $messages = [
            ['conversation_id' => $conversationId, 'role' => 'user', 'message' => 'Hello!', 'created_at' => date('Y-m-d H:i:s')],
            ['conversation_id' => $conversationId, 'role' => 'bot', 'message' => 'Hi there! How can I help you?', 'created_at' => date('Y-m-d H:i:s')],
        ];

        $this->db->table('messages')->insertBatch($messages);
    }
}
