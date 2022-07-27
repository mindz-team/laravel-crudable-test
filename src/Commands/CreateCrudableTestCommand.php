<?php

namespace Mindz\LaravelCrudableTest\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateCrudableTestCommand extends Command
{
    const INDEX_ACTION = 'index';

    const SHOW_ACTION = 'show';

    const STORE_ACTION = 'store';

    const UPDATE_ACTION = 'update';

    const DESTROY_ACTION = 'destroy';

    const ACTIONS = [
        self::INDEX_ACTION,
        self::SHOW_ACTION,
        self::STORE_ACTION,
        self::UPDATE_ACTION,
        self::DESTROY_ACTION,
    ];

    protected $signature = 'make:crudable-test {name : Controller name if in standard directory or controller class including namespace } {--only= : comma separated action that should only be tested} {--except= : comma separated action that should not be tested}';
    protected $description = 'Create test class for crudable controller';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $controller = $this->getController();
        $model = $this->getModel($controller);

        $path = base_path() . '/tests/Feature';
        $client = Storage::createLocalDriver(['root' => $path]);

        $filename = Str::ucfirst(Str::camel(class_basename($model))) . 'ControllerCrudableTest.php';

        if ($client->exists($filename)) {
            $this->error('File ' . $path . '/ ' . $filename . ' already exists');
            return;
        }

        $client->put($filename, $this->crudableStub($model));

        $actions = collect(self::ACTIONS);

        if ($this->option('only')) {
            $allowedActions = explode(',', $this->option('only'));
            $actions = $actions->reject(fn($action) => !in_array($action, $allowedActions));
        }

        if ($this->option('except')) {
            $allowedActions = explode(',', $this->option('except'));
            $actions = $actions->reject(fn($action) => in_array($action, $allowedActions));
        }

        $actions->transform(fn($action) => $action . 'Stub')->each(fn($method) => $client->append($filename, $this->$method()));
        $client->append($filename, "}");
        $this->info("Crudable test created successfully.");
    }

    private function getController(): string
    {
        if (class_exists($this->argument('name'))) {
            return $this->argument('name');
        }

        $controllerClass = sprintf("\\App\\Http\\Controllers\\%s", $this->argument('name') . (Str::contains($this->argument('name'), 'Controller') ? '' : 'Controller'));

        if (class_exists($controllerClass)) {
            return $controllerClass;
        }

        throw new \Exception('Controller of ' . $this->argument('name') . ' cannot be found');
    }

    private function getModel(string $controller)
    {
        return sprintf("%s\\%s",
            'App\\Models',
            Str::replace('Controller', '', Str::ucfirst(class_basename($controller)))
        );
    }

    private function crudableStub(string $model)
    {
        $studlyModel = class_basename(Str::ucfirst(Str::camel($model)));
        $model = '\\' . $model;
        $prefix = Str::kebab(Str::plural(Str::ucfirst(class_basename($model))));
        return <<<EOT
<?php
namespace Tests\Feature;

use Illuminate\Support\Str;
use Mindz\LaravelCrudableTest\Blueprints\CrudableTest;

class {$studlyModel}ControllerCrudableTest extends CrudableTest
{
    protected function getPrefix()
    {
        return '$prefix';
    }

    protected function getModel()
    {
        return $model::class;
    }

EOT;
    }

    protected function indexStub()
    {
        return <<<EOT

    public function testIndexMethod()
    {
        \$objectsCount = 15;
        \$pagination = 10;

        \$model = \$this->getModel();
        \$objects = \$model::factory()->count(\$objectsCount)->create();

        \$response = \$this->actingAs(\$this->getUser())
            ->withHeaders(\$this->defaultHeaders())
            ->json('GET', route(\$this->getPrefix() . '.index'))
            ->assertOk()
            ->assertJsonCount(\$pagination ? \$pagination : \$objectsCount, "data")
            ->assertJsonStructure(\$this->getStructureFor(\$objects));

        if (\$pagination) {
            \$response->assertJson([
                'meta' => [
                    'total' => \$objectsCount
                ]
            ]);
        }
    }
EOT;
    }

    protected function showStub()
    {
        return <<<EOT

    public function testShowMethod()
    {
        \$model = \$this->getModel();
        \$object = \$model::factory()->create();

        \$response = \$this->actingAs(\$this->getUser())
            ->withHeaders(\$this->defaultHeaders())
            ->json('GET', route(\$this->getPrefix() . '.show', [Str::snake(class_basename(\$model)) => \$object->id]));

        \$response->assertOk()
            ->assertJson(
                !in_array('show', \$this->withoutDataWrap()) ? ['data' => [
                    'id' => \$object->id,
                ]] : ['id' => \$object->id],
            )->assertJsonStructure(\$this->getStructureFor(\$object));
    }
EOT;
    }

    protected function storeStub()
    {
        return <<<EOT

    public function testStoreMethod()
    {
        \$model = \$this->getModel();
        \$objectToInsert = \$model::factory()->make();

        \$response = \$this->actingAs(\$this->getUser())
            ->withHeaders(\$this->defaultHeaders())
            ->json('POST', route(\$this->getPrefix() . '.store'), \$objectToInsert->toArray());

        \$response->assertCreated()->assertJsonStructure(!in_array('store', \$this->withoutDataWrap()) ? ['data' => ['id']] : ['id'])
            ->assertJsonStructure(\$this->getStructureFor(\$objectToInsert));
    }
EOT;
    }

    protected function updateStub()
    {
        return <<<EOT

    public function testUpdateMethod()
    {
        \$model = \$this->getModel();
        \$object = \$model::factory()->create();
        \$response = \$this->actingAs(\$this->getUser())
            ->withHeaders(\$this->defaultHeaders())
            ->json('PATCH', route(\$this->getPrefix() . '.update', [Str::snake(class_basename(\$model)) => \$object->id]), \$model::factory()->make()->toArray());

        \$response->assertOk()->assertJsonStructure(!in_array('update', \$this->withoutDataWrap()) ? ['data' => ['id']] : ['id'])
            ->assertJsonStructure(\$this->getStructureFor(\$object));
    }
EOT;
    }

    protected function destroyStub()
    {
        return <<<EOT

    public function testDestroyMethod()
    {
        \$model = \$this->getModel();
        \$object = \$model::factory()->create();

        \$response = \$this->actingAs(\$this->getUser())
            ->withHeaders(\$this->defaultHeaders())
            ->json('DELETE', route(\$this->getPrefix() . '.destroy', [Str::snake(class_basename(\$model)) => \$object->id]));

        \$response->assertNoContent();

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses(\$model))) {
            \$this->assertTrue(\$object->refresh()->trashed());
            return;
        }

        \$this->assertNull(\$model::find(\$object->id));
    }
EOT;
    }
}
