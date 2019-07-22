<?php
// Error handlers
error_reporting(E_ALL);
set_error_handler('exceptions_error_handler');

function exceptions_error_handler($severity, $message, $filename, $lineno) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
}

require_once __DIR__ . '/vendor/autoload.php';
require 'Todo.php';

$todo = new Todo();

// redirect to self to reset POST and GET data
function resetGetPost() {
    header('refresh:2;url=' . $_SERVER['PHP_SELF']);
}

// Create new task
if (isset($_POST['create']) && isset($_POST['description'])) {
    try {
        $taskId = $todo->createTask($_POST['description']);
        resetGetPost();
        die('<p>New task with ID ' . $taskId . ' was created. Redirecting...</p>');
    } catch (Exception $exception) {
        echo '<p class="error">Error occured while creating new task: ' . $exception->getMessage() . '</p>';
    }
}

// Update description of todo
if (isset($_POST['edit']) && is_numeric($_POST['edit']) && isset($_POST['description'])) {
    try {
        $taskId = intval($_POST['edit']);
        $todo->updateMyTask($taskId, $_POST['description']);
        resetGetPost();
        die('<p>Description of task ID ' . $taskId . ' was updated. Redirecting...</p>');
    } catch (Exception $exception) {
        echo '<p class="error">Error occured while updating description of task ID ' . $taskId . ': ' . $exception->getMessage() . '</p>';
    }
}

// Delete task
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $taskId = intval($_GET['delete']);
        $todo->deleteMyTask($taskId);
        resetGetPost();
        die('<p>Task ID ' . $taskId . ' was deleted. Redirecting...</p>');
    } catch (Exception $exception) {
        echo '<p class="error">Error occured while deleting task ID ' . $taskId . ': ' . $exception->getMessage() . '</p>';
    }
}

// Mark task as done
if (isset($_GET['done']) && is_numeric($_GET['done'])) {
    try {
        $taskId = intval($_GET['done']);
        $todo->doneMyTask($taskId);
        resetGetPost();
        die('<p>Task ID ' . $taskId . ' was marked as done.</p>');
    } catch (Exception $exception) {
        echo '<p class="error">Error occured while updating task ID ' . $taskId . ': ' . $exception->getMessage() . '</p>';
    }
}

// Mark task as active (undone)
if (isset($_GET['undone']) && is_numeric($_GET['undone'])) {
    try {
        $taskId = intval($_GET['undone']);
        $todo->undoneMyTask($taskId);
        resetGetPost();
        die('<p>Task ID ' . $taskId . ' was returned from done.</p>');
    } catch (Exception $exception) {
        echo '<p class="error">Error occured while updating task ID ' . $taskId . ': ' . $exception->getMessage() . '</p>';
    }
}

// Show form to update description
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $taskId = intval($_GET['edit']);
        $task = $todo->getMyTask($taskId);
        if (!$task) {
            throw new Exception('You don\'t have any task with ID ' . $taskId . '.');
        }
        echo '<form method="POST" id="todo-edit">';
        echo '<h2>Update task ID ' . $taskId . '</h2>';
        echo '<input type="hidden" name="edit" value="' . $taskId . '">';
        echo '<textarea name="description">' . htmlentities(isset($_POST['description']) ? $_POST['description'] : $task['todo_description']) . '</textarea>';
        echo '<button type="submit">Update description</button>';
        echo '</form>';
    } catch (Exception $exception) {
        echo '<p class="error">Error occured while loading task to edit: ' . $exception->getMessage() . '</p>';
    }
} else { // creating task show only if not editing some other task (otherwise it might be confusing to users)
    echo '<form method="POST" id="todo-create">';
    echo '<h2>Create new TODO task </h2>';
    echo '<textarea name="description">' . htmlentities(isset($_POST['create']) ? $_POST['description'] : '') . '</textarea>';
    echo '<button type="submit" name="create">Create new task</button>';
    echo '</form>';
}
?>
<h2>List of your TODO tasks</h2>
<?php
try {
    $myTasks = $todo->getMyTasks();
    if (count($myTasks) === 0) {
        echo '<p class="error">You don\'t have any tasks.</p>';
    } else {
        ?>
        <form method="GET">
            <table id="todo">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Description</th>
                        <th colspan="3">Update task</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($myTasks as $task) {
                        // workaround for sqlite, which can't properly return int and everything is string
                        $task['todo_status'] = intval($task['todo_status']);
                        echo '<tr>';
                        echo '<td>' . $task['todo_id'] . '</td>';
                        echo '<td>' . $task['todo_created_timestamp'] . '</td>';
                        echo '<td>' . Todo::convert($task['todo_status']) . '</td>';
                        echo '<td>' . htmlspecialchars($task['todo_description']) . '</textarea></td>';
                        if ($task['todo_status'] === Todo::DONE) {
                            echo '<td><button type="submit" name="undone" value="' . $task['todo_id'] . '">Undone</button></form></td>';
                        } else {
                            echo '<td><button type="submit" name="done" value="' . $task['todo_id'] . '">Done</button></form></td>';
                        }
                        echo '<td><button type="submit" name="edit" value="' . $task['todo_id'] . '">Edit</button></td>';
                        echo '<td><button type="submit" name="delete" value="' . $task['todo_id'] . '" onclick="return confirm(\'Are you sure you want to delete task ID ' . $task['todo_id'] . '? This action cannot be undone.\');">Delete</button></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </form>
        <?php
    }
} catch (Exception $exception) {
    echo '<p class="error">Error occured while loading list of your tasks: ' . $exception->getMessage() . '</p>';
}
?>
<style>
    table#todo {
        border-collapse: collapse;
    }
    table#todo tr td{
        border: 1px solid black;
        padding: 5px 10px;
    }
    #todo-edit textarea,
    #todo-edit button,
    #todo-create textarea,
    #todo-create button {
        width: 100%; 
    }
    .error {
        color: red;
        font-weight: bold;
    }
</style>


