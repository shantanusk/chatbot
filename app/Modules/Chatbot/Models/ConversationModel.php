<?php

namespace Modules\Chatbot\Models;

use CodeIgniter\Model;

class ConversationModel extends Model
{
    protected $table            = 'conversations';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['session_id', 'user_id', 'title', 'model', 'system_prompt'];
    protected $useTimestamps    = true;
    protected $useSoftDeletes   = true;
    protected $returnType       = 'object';
}
