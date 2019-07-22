<?php
require 'Database.php';
/**
 * Todo is layer of handling all operation with task of logged user
 */
class Todo {
    /**
     * @var database object
     */
    private $db;
    /**
     * @var user object
     */
    private $user;

    /**
     * Available statuses of tasks
     */
    const ACTIVE = 1;
    const DELETED = 2;
    const DONE = 3;

    public function __construct() {
        $this->db = new Database('data\todo-list.db');
        $security = \Ubnt\UcrmPluginSdk\Service\UcrmSecurity::create();
        $this->user = $security->getUser();
    }

    /**
     * Create new task
     * 
     * @param string $description
     * @return int ID of new task
     * @throws Exception
     */
    public function createTask(string $description) {
        return $this->db->createTask($this->user->userId, Todo::ACTIVE, trim($description));
    }

    /**
     * Load all active or done tasks
     * 
     * @return array of tasks on success
     * @throws Exception
     */
    public function getMyTasks() {
        return $this->db->getUserTasks($this->user->userId, [self::ACTIVE, self::DONE]);
    }

    /**
     * Get info of one task
     * 
     * @param int $taskId
     * @return true on success
     * @throws Exception
     */
    public function getMyTask(int $taskId) {
        return $this->db->getUserTask($this->user->userId, $taskId);
    }

    /**
     * Update description of task
     * 
     * @param int $taskId
     * @return true on success
     * @throws Exception
     */
    public function updateMyTask(int $taskId, string $description) {
        return $this->db->updateUserTask($this->user->userId, $taskId, null, $description);
    }

    /**
     * Mark task as done
     * 
     * @param int $taskId
     * @return true on success
     * @throws Exception
     */
    public function doneMyTask(int $taskId) {
        return $this->db->updateUserTask($this->user->userId, $taskId, self::DONE, null);
    }
    
    /**
     * Mark task as active
     * 
     * @param int $taskId
     * @return true on success
     * @throws Exception
     */
    public function undoneMyTask(int $taskId) {
        return $this->db->updateUserTask($this->user->userId, $taskId, self::ACTIVE, null);
    }
    
    /**
     * Mark task as deleted 
     * 
     * @param int $taskId
     * @return true on success
     * @throws Exception
     */
    public function deleteMyTask(int $taskId) {
        return $this->db->updateUserTask($this->user->userId, $taskId, self::DELETED, null);
    }

    /**
     * Convert enum representation to string
     * Note: Input might be string
     * 
     * @param int $int
     * @return string
     * 
     * @throws Exception if out of bounds
     */
    public static function convert($int) {
        switch (intval($int)) {
            case self::DELETED:
                return 'DELETED';
            case self::ACTIVE:
                return 'ACTIVE';
            case self::DONE:
                return 'DONE';
            default:
                throw new Exception('Status int is out of bounds');
        }
    }

}
