### Task Controller
```php
namespace App\Http\Controllers;

use App\Models\Task;
use App\Events\TaskUpdated;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TaskController extends Controller
{
    public function update(Request $request, Task $task)
    {
        $task->update($request->all());
        broadcast(new TaskUpdated($task))->toOthers();
        return response()->json(['success' => true, 'task' => $task]);
    }

    public function store(Request $request)
    {
        $task = Task::create($request->all());
        broadcast(new TaskCreated($task))->toOthers();
        return response()->json(['success' => true, 'task' => $task]);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        broadcast(new TaskDeleted($task->id))->toOthers();
        return response()->json(['success' => true]);
    }
}
```

### Task Events
```php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Task;

class TaskUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $task;
    public function __construct(Task $task)
    {
        $this->task = $task;
    }
    public function broadcastOn()
    {
        return new Channel('tasks.' . $this->task->id);
    }
    public function broadcastAs()
    {
        return 'task.updated';
    }
}

class TaskCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $task;
    public function __construct(Task $task)
    {
        $this->task = $task;
    }
    public function broadcastOn()
    {
        return new Channel('tasks');
    }
    public function broadcastAs()
    {
        return 'task.created';
    }
}

class TaskDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $taskId;
    public function __construct($taskId)
    {
        $this->taskId = $taskId;
    }
    public function broadcastOn()
    {
        return new Channel('tasks');
    }
    public function broadcastAs()
    {
        return 'task.deleted';
    }
}
```

### 3. Broadcasting Channels
```php
Broadcast::channel('tasks.{taskId}', function ($user, $taskId) {
    return $user->tasks()->where('id', $taskId)->exists();
});

Broadcast::channel('tasks', function ($user) {
    return $user !== null;
});
```

### .env Configuration
```ini
BROADCAST_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## **Frontend (JavaScript)**
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'your_app_key',
    cluster: 'mt1',
    encrypted: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
        }
    }
});

window.Echo.channel('tasks')
    .listen('.task.created', (event) => {
        console.log('Nueva tarea creada:', event.task);
    })
    .listen('.task.updated', (event) => {
        console.log('Tarea actualizada:', event.task);
    })
    .listen('.task.deleted', (event) => {
        console.log('Tarea eliminada:', event.taskId);
    });
```
