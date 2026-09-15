<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Laravilt\Panel\Panel;
use Laravilt\Panel\PanelRegistry;
use Laravilt\Panel\Resources\Resource;
use Laravilt\Tables\Columns\CheckboxColumn;
use Laravilt\Tables\Columns\SelectColumn;
use Laravilt\Tables\Columns\TextColumn;
use Laravilt\Tables\Columns\TextInputColumn;
use Laravilt\Tables\Columns\ToggleColumn;
use Laravilt\Tables\Http\ColumnStateRoutes;
use Laravilt\Tables\Table;

class ColumnStateTask extends Model
{
    protected $table = 'column_state_tasks';

    protected $guarded = [];

    protected $casts = ['is_done' => 'boolean', 'is_pinned' => 'boolean', 'locked' => 'boolean'];
}

class ColumnStateUser extends AuthUser
{
    protected $table = 'column_state_users';

    protected $guarded = [];
}

class ColumnStateTaskResource extends Resource
{
    protected static string $model = ColumnStateTask::class;

    protected static ?string $slug = 'tasks';

    /** @var array<int, array{0: string, 1: mixed}> */
    public static array $afterUpdates = [];

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title'),
            TextInputColumn::make('notes')->rules(['max:10']),
            SelectColumn::make('status')
                ->options(['draft' => 'Draft', 'published' => 'Published'])
                ->selectablePlaceholder(false),
            ToggleColumn::make('is_done')
                ->afterStateUpdated(function ($record, $column, $value) {
                    static::$afterUpdates[] = [$column, $value];
                }),
            CheckboxColumn::make('is_pinned'),
            ToggleColumn::make('locked')->disabled(),
            TextInputColumn::make('author.name'),
        ]);
    }
}

class ColumnStatePolicyTaskResource extends ColumnStateTaskResource
{
    protected static ?string $slug = 'policy-tasks';

    protected static bool $usePolicies = true;
}

class ColumnStateTaskPolicy
{
    public function update(ColumnStateUser $user, ColumnStateTask $task): bool
    {
        return $task->title !== 'forbidden';
    }
}

beforeEach(function () {
    // The web middleware group encrypts cookies/sessions
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

    Schema::create('column_state_users', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->timestamps();
    });

    Schema::create('column_state_tasks', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->string('notes')->nullable();
        $table->string('status')->nullable();
        $table->boolean('is_done')->default(false);
        $table->boolean('is_pinned')->default(false);
        $table->boolean('locked')->default(false);
        $table->timestamps();
    });

    ColumnStateTaskResource::$afterUpdates = [];

    $this->app->singleton(PanelRegistry::class);
    app(PanelRegistry::class)->register(
        Panel::make('admin')
            ->path('admin')
            ->middleware(['web'])
            ->authMiddleware(['auth'])
            ->resources([ColumnStateTaskResource::class, ColumnStatePolicyTaskResource::class])
    );

    app('router')->aliasMiddleware('panel.auth', Authenticate::class);
    ColumnStateRoutes::register();
    app('router')->getRoutes()->refreshNameLookups();

    Gate::policy(ColumnStateTask::class, ColumnStateTaskPolicy::class);

    $this->user = ColumnStateUser::create(['name' => 'Admin']);
    $this->task = ColumnStateTask::create(['title' => 'Task', 'notes' => 'old', 'status' => 'draft']);
});

function columnStateUrl(string $resource, int|string $id): string
{
    return "/admin/_tables/{$resource}/{$id}/column";
}

it('serializes editable columns with their component, rules and disabled state', function () {
    $select = SelectColumn::make('status')->options(['a' => 'A'])->rules(['required'])->disabled()->toInertiaProps();
    $input = TextInputColumn::make('notes')->type('number')->rules(['min:1'])->toInertiaProps();
    $checkbox = CheckboxColumn::make('is_pinned')->toInertiaProps();
    $toggle = ToggleColumn::make('is_done')->toInertiaProps();

    expect($select)->toMatchArray(['component' => 'SelectColumn', 'options' => ['a' => 'A'], 'rules' => ['required'], 'disabled' => true, 'editable' => true])
        ->and($input)->toMatchArray(['component' => 'TextInputColumn', 'type' => 'number', 'rules' => ['min:1'], 'disabled' => false, 'editable' => true])
        ->and($checkbox)->toMatchArray(['component' => 'CheckboxColumn', 'editable' => true])
        ->and($toggle)->toMatchArray(['component' => 'ToggleColumn', 'editable' => true]);
});

it('exposes the column update route for resource tables only', function () {
    app(PanelRegistry::class)->setCurrent('admin');

    expect(Table::make()->resourceSlug('tasks')->getColumnUpdateRoute())
        ->toEndWith('/admin/_tables/tasks/__ID__/column')
        ->and(Table::make()->getColumnUpdateRoute())->toBeNull();
});

it('updates a text input column and only that attribute', function () {
    $this->actingAs($this->user)
        ->patchJson(columnStateUrl('tasks', $this->task->id), ['column' => 'notes', 'value' => 'new', 'title' => 'hacked'])
        ->assertOk()
        ->assertJson(['column' => 'notes', 'state' => 'new']);

    $this->task->refresh();
    expect($this->task->notes)->toBe('new')
        ->and($this->task->title)->toBe('Task');
});

it('validates using the column rules', function () {
    $this->actingAs($this->user)
        ->patchJson(columnStateUrl('tasks', $this->task->id), ['column' => 'notes', 'value' => 'way too long for ten'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('value');

    expect($this->task->refresh()->notes)->toBe('old');
});

it('only accepts declared select options', function () {
    $this->actingAs($this->user)
        ->patchJson(columnStateUrl('tasks', $this->task->id), ['column' => 'status', 'value' => 'archived'])
        ->assertUnprocessable();

    $this->actingAs($this->user)
        ->patchJson(columnStateUrl('tasks', $this->task->id), ['column' => 'status', 'value' => 'published'])
        ->assertOk();

    expect($this->task->refresh()->status)->toBe('published');
});

it('updates toggle and checkbox columns as booleans and runs callbacks', function () {
    $this->actingAs($this->user)
        ->patchJson(columnStateUrl('tasks', $this->task->id), ['column' => 'is_done', 'value' => true])
        ->assertOk()
        ->assertJson(['state' => true]);

    $this->actingAs($this->user)
        ->patchJson(columnStateUrl('tasks', $this->task->id), ['column' => 'is_pinned', 'value' => 1])
        ->assertOk();

    $this->actingAs($this->user)
        ->patchJson(columnStateUrl('tasks', $this->task->id), ['column' => 'is_pinned', 'value' => 'nope'])
        ->assertUnprocessable();

    $this->task->refresh();
    expect($this->task->is_done)->toBeTrue()
        ->and($this->task->is_pinned)->toBeTrue()
        ->and(ColumnStateTaskResource::$afterUpdates)->toBe([['is_done', true]]);
});

it('rejects columns that are not editable', function (string $column, int $status) {
    $this->actingAs($this->user)
        ->patchJson(columnStateUrl('tasks', $this->task->id), ['column' => $column, 'value' => 'x'])
        ->assertStatus($status);

    expect($this->task->refresh()->title)->toBe('Task');
})->with([
    'display column' => ['title', 403],
    'disabled column' => ['locked', 403],
    'relationship column' => ['author.name', 403],
    'unknown column' => ['password', 404],
]);

it('requires authentication', function () {
    $this->patchJson(columnStateUrl('tasks', $this->task->id), ['column' => 'notes', 'value' => 'new'])
        ->assertUnauthorized();

    expect($this->task->refresh()->notes)->toBe('old');
});

it('authorizes the record update through the resource policy', function () {
    $forbidden = ColumnStateTask::create(['title' => 'forbidden', 'notes' => 'old']);

    $this->actingAs($this->user)
        ->patchJson(columnStateUrl('policy-tasks', $forbidden->id), ['column' => 'notes', 'value' => 'new'])
        ->assertForbidden();

    $this->actingAs($this->user)
        ->patchJson(columnStateUrl('policy-tasks', $this->task->id), ['column' => 'notes', 'value' => 'new'])
        ->assertOk();

    expect($forbidden->refresh()->notes)->toBe('old')
        ->and($this->task->refresh()->notes)->toBe('new');
});

it('returns not found for unknown resources and records', function () {
    $this->actingAs($this->user)
        ->patchJson(columnStateUrl('missing', $this->task->id), ['column' => 'notes', 'value' => 'new'])
        ->assertNotFound();

    $this->actingAs($this->user)
        ->patchJson(columnStateUrl('tasks', 999), ['column' => 'notes', 'value' => 'new'])
        ->assertNotFound();
});
