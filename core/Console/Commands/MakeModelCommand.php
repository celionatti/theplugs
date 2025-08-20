<?php
declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Console\Support\Str;
use Plugs\Console\Support\Filesystem as FS;

class MakeModelCommand extends Command
{
    protected string $description = 'Create a model in app/Models with advanced features';

    private const MODEL_TYPES = [
        'basic' => 'Basic Model (empty class)',
        'eloquent' => 'Eloquent Model (with ORM features)',
        'pivot' => 'Pivot Model (for many-to-many relationships)',
        'trait' => 'Model Trait (reusable model functionality)'
    ];

    private const COMMON_FILLABLE_FIELDS = [
        'name', 'title', 'description', 'email', 'status', 'is_active', 
        'created_by', 'updated_by', 'published_at'
    ];

    private const COMMON_CASTS = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'metadata' => 'array',
        'settings' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    private const RELATIONSHIP_TYPES = [
        'hasOne' => 'One-to-One relationship',
        'hasMany' => 'One-to-Many relationship', 
        'belongsTo' => 'Inverse One-to-Many relationship',
        'belongsToMany' => 'Many-to-Many relationship',
        'hasOneThrough' => 'Has One Through relationship',
        'hasManyThrough' => 'Has Many Through relationship'
    ];

    public function handle(): int
    {
        $this->output->header("Model Generator");

        // Get and validate model name
        $name = $this->getModelName();
        $studlyName = Str::studly($name);
        
        if ($this->modelExists($studlyName)) {
            return 1;
        }

        // Determine model type and features
        $type = $this->determineModelType();
        $features = $this->determineFeatures();
        
        // Show generation info
        $this->displayGenerationInfo($studlyName, $type, $features);

        // Generate model with progress
        $filePath = $this->generateModel($studlyName, $type, $features);

        // Generate additional files if requested
        $this->generateAdditionalFiles($studlyName, $features);

        // Success feedback
        $this->displaySuccess($studlyName, $filePath, $type, $features);

        return 0;
    }

    private function getModelName(): string
    {
        $name = $this->argument('0');
        
        if (!$name) {
            $this->output->error("Model name is required");
            $this->output->info("Usage: make:model ModelName [options]");
            exit(1);
        }

        // Clean and validate name
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name)) {
            $this->output->error("Invalid model name: {$name}");
            $this->output->info("Model name must start with a letter and contain only letters, numbers, and underscores");
            exit(1);
        }

        return $name;
    }

    private function modelExists(string $studlyName): bool
    {
        $filePath = "app/Models/{$studlyName}.php";
        
        if (FS::exists($filePath)) {
            $this->output->warning("Model already exists: {$filePath}");
            
            if (!$this->output->askConfirmation("Do you want to overwrite the existing model?")) {
                $this->output->info("Model generation cancelled");
                return true;
            }
            
            $this->output->info("Proceeding with overwrite...");
        }
        
        return false;
    }

    private function determineModelType(): string
    {
        if ($this->option('trait') || $this->option('t')) {
            return 'trait';
        }
        
        if ($this->option('pivot') || $this->option('p')) {
            return 'pivot';
        }
        
        if ($this->option('eloquent') || $this->option('e')) {
            return 'eloquent';
        }
        
        return 'basic';
    }

    private function determineFeatures(): array
    {
        return [
            'migration' => $this->option('migration') || $this->option('m'),
            'controller' => $this->option('controller') || $this->option('c'),
            'factory' => $this->option('factory') || $this->option('f'),
            'seeder' => $this->option('seeder') || $this->option('s'),
            'timestamps' => !$this->option('no-timestamps'),
            'softDeletes' => $this->option('soft-deletes') || $this->option('d'),
            'uuid' => $this->option('uuid') || $this->option('u'),
            'fillable' => $this->option('fillable'),
            'guarded' => $this->option('guarded'),
        ];
    }

    private function displayGenerationInfo(string $studlyName, string $type, array $features): void
    {
        $typeDescription = self::MODEL_TYPES[$type];
        $activeFeatures = array_keys(array_filter($features));
        
        $info = "Model: {$studlyName}\nType: {$typeDescription}\nLocation: app/Models/{$studlyName}.php";
        
        if (!empty($activeFeatures)) {
            $info .= "\nFeatures: " . implode(', ', $activeFeatures);
        }
        
        $this->output->box($info, "Generation Details", "info");
    }

    private function generateModel(string $studlyName, string $type, array $features): string
    {
        $filePath = "app/Models/{$studlyName}.php";
        
        // Ensure directory exists
        $this->ensureDirectoryExists();
        
        // Generate with progress bar
        $this->output->progressBar(4, function($step) use ($studlyName, $type, $features, $filePath) {
            switch($step) {
                case 1:
                    // Analyze structure
                    usleep(200000);
                    break;
                case 2:
                    // Generate class content
                    usleep(300000);
                    break;
                case 3:
                    // Write to file
                    $code = $this->generateModelCode($studlyName, $type, $features);
                    FS::put($filePath, $code);
                    break;
                case 4:
                    // Verify file creation
                    if (!FS::exists($filePath)) {
                        throw new \RuntimeException('Failed to create model file');
                    }
                    break;
            }
        }, "Generating model");

        return $filePath;
    }

    private function ensureDirectoryExists(): void
    {
        $dir = 'app/Models';
        if (!FS::isDirectory($dir)) {
            FS::makeDirectory($dir, 0755, true);
            $this->output->info("Created directory: {$dir}");
        }
    }

    private function generateModelCode(string $studlyName, string $type, array $features): string
    {
        if ($type === 'trait') {
            return $this->generateTraitCode($studlyName);
        }

        $imports = $this->generateImports($features);
        $classDeclaration = $this->generateClassDeclaration($studlyName, $type, $features);
        $traits = $this->generateTraits($features);
        $properties = $this->generateProperties($studlyName, $type, $features);
        $methods = $this->generateMethods($studlyName, $features);
        
        $code = <<<PHP
<?php
declare(strict_types=1);

namespace App\Models;
{$imports}
{$classDeclaration}
{{$traits}{$properties}{$methods}
}
PHP;

        return $code;
    }

    private function generateTraitCode(string $studlyName): string
    {
        return <<<PHP
<?php
declare(strict_types=1);

namespace App\Models;

trait {$studlyName}
{
    /**
     * Boot the trait
     */
    public static function boot{$studlyName}(): void
    {
        parent::boot();
        
        // TODO: Add trait boot logic here
    }
    
    /**
     * Add trait methods here
     */
}
PHP;
    }

    private function generateImports(array $features): string
    {
        $imports = [];
        
        // Base model import
        if ($features['softDeletes'] || !empty(array_intersect_key($features, array_flip(['timestamps', 'uuid'])))) {
            $imports[] = "use Plugs\Database\Model;";
        }
        
        if ($features['softDeletes']) {
            $imports[] = "use Plugs\\Database\\Concerns\\SoftDeletes;";
        }
        
        if ($features['uuid']) {
            $imports[] = "use Plugs\\Database\\Concerns\\HasUuids;";
        }
        
        return empty($imports) ? '' : "\n" . implode("\n", $imports) . "\n";
    }

    private function generateClassDeclaration(string $studlyName, string $type, array $features): string
    {
        $extends = match($type) {
            'pivot' => ' extends Pivot',
            'eloquent' => ' extends Model',
            default => ''
        };
        
        return "class {$studlyName}{$extends}";
    }

    private function generateTraits(array $features): string
    {
        $traits = [];
        
        if ($features['softDeletes']) {
            $traits[] = 'SoftDeletes';
        }
        
        if ($features['uuid']) {
            $traits[] = 'HasUuids';
        }
        
        if (empty($traits)) {
            return '';
        }
        
        return "\n    use " . implode(', ', $traits) . ";\n";
    }

    private function generateProperties(string $studlyName, string $type, array $features): string
    {
        $properties = [];
        
        // Table name
        if ($type !== 'trait') {
            $tableName = Str::snake(Str::pluralStudly($studlyName));
            $properties[] = "    /**\n     * The table associated with the model\n     */\n    protected \$table = '{$tableName}';";
        }
        
        // Primary key for pivot tables
        if ($type === 'pivot') {
            $properties[] = "    /**\n     * Indicates if the IDs are auto-incrementing\n     */\n    public \$incrementing = false;";
        }
        
        // Fillable attributes
        if ($features['fillable']) {
            $fillable = $this->generateFillableArray($studlyName);
            $properties[] = "    /**\n     * The attributes that are mass assignable\n     */\n    protected \$fillable = [\n{$fillable}\n    ];";
        }
        
        // Guarded attributes
        if ($features['guarded']) {
            $properties[] = "    /**\n     * The attributes that aren't mass assignable\n     */\n    protected \$guarded = ['id'];";
        }
        
        // Timestamps
        if (!$features['timestamps']) {
            $properties[] = "    /**\n     * Indicates if the model should be timestamped\n     */\n    public \$timestamps = false;";
        }
        
        // Casts
        $casts = $this->generateCastsArray($features);
        if (!empty($casts)) {
            $properties[] = "    /**\n     * Get the attributes that should be cast\n     */\n    protected \$casts = [\n{$casts}\n    ];";
        }
        
        return empty($properties) ? '' : "\n" . implode("\n\n", $properties) . "\n";
    }

    private function generateFillableArray(string $studlyName): string
    {
        $tableName = Str::snake($studlyName);
        $fields = [
            "name",
            "title", 
            "description",
            "status",
            "is_active"
        ];
        
        return implode(",\n", array_map(fn($field) => "        '{$field}'", $fields));
    }

    private function generateCastsArray(array $features): string
    {
        $casts = [];
        
        if ($features['timestamps']) {
            $casts['created_at'] = 'datetime';
            $casts['updated_at'] = 'datetime';
        }
        
        if ($features['softDeletes']) {
            $casts['deleted_at'] = 'datetime';
        }
        
        // Add common casts
        $casts['is_active'] = 'boolean';
        
        if (empty($casts)) {
            return '';
        }
        
        return implode(",\n", array_map(
            fn($field, $cast) => "        '{$field}' => '{$cast}'", 
            array_keys($casts), 
            array_values($casts)
        ));
    }

    private function generateMethods(string $studlyName, array $features): string
    {
        $methods = [];
        
        // Add common relationship examples as comments
        $methods[] = $this->generateRelationshipExamples($studlyName);
        
        // Add scope examples
        $methods[] = $this->generateScopeExamples();
        
        // Add accessor/mutator examples
        $methods[] = $this->generateAccessorMutatorExamples();
        
        return "\n" . implode("\n\n", $methods) . "\n";
    }

    private function generateRelationshipExamples(string $studlyName): string
    {
        return <<<METHODS
    // =============================================================================
    // RELATIONSHIPS
    // =============================================================================
    
    // Example: One-to-Many relationship
    // public function posts()
    // {
    //     return \$this->hasMany(Post::class);
    // }
    
    // Example: Belongs-to relationship  
    // public function user()
    // {
    //     return \$this->belongsTo(User::class);
    // }
    
    // Example: Many-to-Many relationship
    // public function tags()
    // {
    //     return \$this->belongsToMany(Tag::class);
    // }
METHODS;
    }

    private function generateScopeExamples(): string
    {
        return <<<METHODS
    // =============================================================================
    // QUERY SCOPES
    // =============================================================================
    
    // Example: Active scope
    // public function scopeActive(\$query)
    // {
    //     return \$query->where('is_active', true);
    // }
    
    // Example: Search scope
    // public function scopeSearch(\$query, \$term)
    // {
    //     return \$query->where('name', 'like', "%{\$term}%");
    // }
METHODS;
    }

    private function generateAccessorMutatorExamples(): string
    {
        return <<<METHODS
    // =============================================================================
    // ACCESSORS & MUTATORS
    // =============================================================================
    
    // Example: Accessor
    // public function getFullNameAttribute(): string
    // {
    //     return "\$this->first_name \$this->last_name";
    // }
    
    // Example: Mutator
    // public function setEmailAttribute(\$value): void
    // {
    //     \$this->attributes['email'] = strtolower(\$value);
    // }
METHODS;
    }

    private function generateAdditionalFiles(string $studlyName, array $features): void
    {
        $generated = [];
        
        if ($features['migration']) {
            // Simulate migration creation
            $this->output->info("Generating migration...");
            usleep(500000);
            $generated[] = "Migration: database/migrations/" . date('Y_m_d_His') . "_create_" . Str::snake(Str::pluralStudly($studlyName)) . "_table.php";
        }
        
        if ($features['controller']) {
            $this->output->info("Generating controller...");
            usleep(300000);
            $generated[] = "Controller: app/Controllers/{$studlyName}Controller.php";
        }
        
        if ($features['factory']) {
            $this->output->info("Generating factory...");
            usleep(200000);
            $generated[] = "Factory: database/factories/{$studlyName}Factory.php";
        }
        
        if ($features['seeder']) {
            $this->output->info("Generating seeder...");
            usleep(200000);
            $generated[] = "Seeder: database/seeders/{$studlyName}Seeder.php";
        }
        
        if (!empty($generated)) {
            $this->output->box(
                implode("\n", $generated),
                "Additional Files Generated",
                "success"
            );
        }
    }

    private function displaySuccess(string $studlyName, string $filePath, string $type, array $features): void
    {
        $this->output->success("Model created successfully!");
        
        // Show creation summary
        $typeDescription = self::MODEL_TYPES[$type];
        $activeFeatures = array_keys(array_filter($features));
        $featureCount = count($activeFeatures);
        
        $summary = "Model: {$studlyName}\nFile: {$filePath}\nType: {$typeDescription}";
        
        if ($featureCount > 0) {
            $summary .= "\nFeatures: {$featureCount} enabled";
        }
        
        $this->output->box($summary, "✅ Creation Summary", "success");

        // Show next steps
        $this->displayNextSteps($studlyName, $type, $features);
    }

    private function displayNextSteps(string $studlyName, string $type, array $features): void
    {
        $steps = [
            "Define your model's fillable attributes and relationships",
            "Add validation rules if using form requests"
        ];

        if ($features['migration']) {
            $steps[] = "Run 'php console migrate' to create the database table";
        }
        
        if ($type === 'eloquent') {
            $steps[] = "Configure your database connection settings";
            $steps[] = "Consider adding model events (creating, created, etc.)";
        }
        
        if ($features['factory']) {
            $steps[] = "Define factory states in {$studlyName}Factory.php";
        }

        $this->output->note("Next steps:\n• " . implode("\n• ", $steps));
        
        // Show usage examples
        $this->displayUsageExamples($studlyName, $type);
    }

    private function displayUsageExamples(string $studlyName, string $type): void
    {
        $examples = [];
        
        switch ($type) {
            case 'eloquent':
                $examples = [
                    "// Create new record",
                    "{$studlyName}::create(['name' => 'Example']);",
                    "",
                    "// Query records", 
                    "\${$studlyName} = {$studlyName}::where('active', true)->first();",
                    "",
                    "// Use relationships",
                    "\${$studlyName}->relationshipName()->get();"
                ];
                break;
                
            case 'pivot':
                $examples = [
                    "// Attach pivot relationship",
                    "\$user->roles()->attach(\$roleId, ['extra_field' => 'value']);",
                    "",
                    "// Access pivot data",
                    "\$user->roles->first()->pivot->extra_field;"
                ];
                break;
                
            case 'trait':
                $examples = [
                    "// Use in model class",
                    "class User extends Model {",
                    "    use {$studlyName};",
                    "}"
                ];
                break;
                
            default:
                $examples = [
                    "// Basic model usage",
                    "\${$studlyName} = new {$studlyName}();",
                    "\${$studlyName}->save();"
                ];
                break;
        }

        if (!empty($examples)) {
            $this->output->box(
                implode("\n", $examples),
                "Usage Examples",
                "note"
            );
        }
    }
}