<?php

class Database {

    private $db;
    const DB_FILE = 'data\todo-list.db';

    public function __construct() {
        $this->db = new PDO('sqlite:' . self::DB_FILE . '.sqlite3');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function install() {
        // create db file
        $db = new PDO('sqlite:' . self::DB_FILE . '.sqlite3');
        // create basic todo table
        $db->exec('CREATE TABLE IF NOT EXISTS todo (
            todo_id INTEGER PRIMARY KEY AUTOINCREMENT,
            todo_created_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            todo_owner INTEGER NOT NULL,
            todo_status INTEGER DEFAULT 1,
            todo_description TEXT DEFAULT \'\'
        );');
    }

    /**
     * Create new task
     * 
     * @param int $userId task creator ID
     * @param int $status task status
     * @param string $description
     * @return int assigned task ID
     * @throws Exception
     */
    public function createTask(int $userId, int $status, string $description) {
        $stmt = $this->db->prepare('INSERT INTO todo (todo_owner, todo_status, todo_description) VALUES (:userId, :status, :description)');
        if (!$description) { // description is empty
            throw new Exception('You have to provide description.');
        }
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, PDO::PARAM_INT);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception($stmt->errorInfo());
        }
        return $this->db->lastInsertId();
    }

    /**
     * Load tasks from user based on their statuses
     * 
     * @param int $userId
     * @param array $statuses
     * @return type
     * @throws Exception
     */
    public function getUserTasks(int $userId, array $statuses) {
        // @TODO - fix potencional sql injection (but since $statuses are not user-input but fixed enum, it is ok)
        $stmt = $this->db->prepare('SELECT * FROM todo WHERE todo_owner = :userId AND todo_status IN (' . join(', ', $statuses) . ')');
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception($stmt->errorInfo());
        }
        return $stmt->fetchAll();
    }

    /**
     * Load task
     * 
     * @param int $userId
     * @param int $taskId
     * @return boolean true on success, false on error
     * @throws Exception
     */
    public function getUserTask(int $userId, int $taskId) {
        $stmt = $this->db->prepare('SELECT * FROM todo WHERE todo_id = :taskId AND todo_owner = :userId');
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':taskId', $taskId, PDO::PARAM_INT);
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception($stmt->errorInfo());
        }
        return $stmt->fetch();
    }

    /**
     * Update status or description of task
     * 
     * @param int $userId
     * @param int $taskId
     * @param int|null $status
     * @param string|null $description
     * @return boolean true on success
     * @throws Exception
     */
    public function updateUserTask(int $userId, int $taskId, $status, $description) {
        $sqlA = [];
        if (is_int($status)) {
            $sqlA[] = 'todo_status = :status ';
        }
        if (is_string($description)) {
            $sqlA[] = 'todo_description = :description ';
        }
        // ...prepared to add more columns to edit
        // at least one of optional parameters must be filled
        if (!count($sqlA)) {
            throw new Exception('No fields to edit');
        }
        $stmt = $this->db->prepare('UPDATE todo SET ' . join(', ', $sqlA) . ' WHERE todo_id = :taskId AND todo_owner = :userId');
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':taskId', $taskId, PDO::PARAM_INT);
        if (is_int($status)) {
            $stmt->bindValue(':status', $status, PDO::PARAM_INT);
        }
        if (is_string($description)) {
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        }
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception($stmt->errorInfo());
        }
        return $result;
    }

}
