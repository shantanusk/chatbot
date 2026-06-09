<?php

namespace Modules\Chatbot\Models;

use CodeIgniter\Model;

class ConversationModel extends Model
{
    protected $table            = 'conversations';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['session_id', 'title'];
    protected $useTimestamps    = true;
    protected $returnType       = 'object';
}
