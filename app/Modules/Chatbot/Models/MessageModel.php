<?php

namespace Modules\Chatbot\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table            = 'messages';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['conversation_id', 'role', 'message'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = '';
    protected $returnType       = 'object';
}
