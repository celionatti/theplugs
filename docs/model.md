# Plugs Eloquent Model Documentation

## Table of Contents

1. [Basic Usage](#basic-usage)
2. [Model Definition](#model-definition)
3. [Creating Records](#creating-records)
4. [Retrieving Records](#retrieving-records)
5. [Updating Records](#updating-records)
6. [Deleting Records](#deleting-records)
7. [Soft Deletes](#soft-deletes)
8. [Attributes & Mutators](#attributes--mutators)
9. [Type Casting](#type-casting)
10. [Relationships](#relationships)
11. [Events & Hooks](#events--hooks)
12. [Query Building](#query-building)
13. [Serialization](#serialization)
14. [Mass Assignment](#mass-assignment)
15. [Timestamps](#timestamps)
16. [Advanced Features](#advanced-features)

## Basic Usage

### Creating a Model

```php
<?php

namespace App\Models;

use Plugs\Database\Eloquent\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'age'];
}
```

## Model Definition

### Configuration Properties

```php
class User extends Model
{
    // Table name (auto-generated if not specified)
    protected string $table = 'users';
    
    // Primary key column
    protected string $primaryKey = 'id';
    
    // Mass assignable attributes
    protected array $fillable = ['name', 'email', 'age'];
    
    // Mass assignment protection (blacklist)
    protected array $guarded = ['id', 'created_at', 'updated_at'];
    
    // Hidden attributes in JSON/Array output
    protected array $hidden = ['password', 'secret_key'];
    
    // Attribute type casting
    protected array $casts = [
        'age' => 'integer',
        'is_active' => 'boolean',
        'settings' => 'json',
        'birth_date' => 'date'
    ];
    
    // Date attributes for Carbon conversion
    protected array $dates = ['created_at', 'updated_at', 'birth_date'];
    
    // Enable/disable timestamps
    protected bool $timestamps = true;
    
    // Soft delete column (null = no soft deletes)
    protected ?string $deletedAt = 'deleted_at';
}
```

## Creating Records

### Using create() Method

```php
// Create and save in one step
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
]);
```

### Using new and save()

```php
// Create instance and save separately
$user = new User();
$user->name = 'Jane Doe';
$user->email = 'jane@example.com';
$user->save();
```

### Using make() Method

```php
// Create instance without saving
$user = User::make([
    'name' => 'Bob Smith',
    'email' => 'bob@example.com'
]);
$user->save(); // Save when ready
```

### Using fill() Method

```php
$user = new User();
$user->fill([
    'name' => 'Alice Johnson',
    'email' => 'alice@example.com'
])->save();
```

## Retrieving Records

### Find by Primary Key

```php
// Find by ID (returns null if not found)
$user = User::find(1);

// Find by ID (throws exception if not found)
$user = User::findOrFail(1);
```

### Query Methods

```php
// Get all records
$users = User::all();

// Where clauses
$users = User::where('age', '>', 18)->get();
$users = User::where('name', 'John Doe')->get();
$users = User::where('age', '>=', 21)->where('active', true)->get();

// Where In
$users = User::whereIn('id', [1, 2, 3])->get();

// Advanced queries
$users = User::query()
    ->where('age', '>', 18)
    ->where('active', true)
    ->orderBy('name')
    ->limit(10)
    ->get();
```

### Single Record Retrieval

```php
// Get first record
$user = User::where('email', 'john@example.com')->first();

// Get first or fail
$user = User::where('email', 'john@example.com')->firstOrFail();
```

## Updating Records

### Update Single Record

```php
$user = User::find(1);
$user->name = 'Updated Name';
$user->save();

// Or using update method
$user = User::find(1);
$user->update(['name' => 'Updated Name']);
```

### Mass Update

```php
User::where('active', false)
    ->update(['status' => 'inactive']);
```

### Check if Dirty

```php
$user = User::find(1);
$user->name = 'New Name';

// Check if specific attribute is dirty
if ($user->isDirty('name')) {
    echo 'Name has been changed';
}

// Check if any attribute is dirty
if ($user->isDirty()) {
    echo 'Model has changes';
}

// Get all changes
$changes = $user->getChanges();
```

## Deleting Records

### Soft Delete (if enabled)

```php
$user = User::find(1);
$user->delete(); // Soft delete

// Check if soft deleted
if ($user->trashed()) {
    echo 'User is soft deleted';
}
```

### Force Delete

```php
$user = User::find(1);
$user->forceDelete(); // Permanent delete
```

### Restore Soft Deleted

```php
$user = User::find(1);
$user->restore();
```

### Query with Soft Deleted

```php
// Include soft deleted records
$users = User::withTrashed()->get();

// Only soft deleted records
$users = User::onlyTrashed()->get();
```

## Soft Deletes

### Enable Soft Deletes

```php
class User extends Model
{
    protected ?string $deletedAt = 'deleted_at';
}
```

### Soft Delete Operations

```php
// Soft delete
$user->delete();

// Check if trashed
$user->trashed(); // returns bool

// Restore
$user->restore();

// Force delete
$user->forceDelete();
```

## Attributes & Mutators

### Accessors (Get Mutators)

```php
class User extends Model
{
    // Accessor for full_name attribute
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    // Accessor for formatted_email
    public function getFormattedEmailAttribute(): string
    {
        return strtolower($this->email);
    }
}

// Usage
$user = User::find(1);
echo $user->full_name; // Calls getFullNameAttribute()
echo $user->formatted_email; // Calls getFormattedEmailAttribute()
```

### Mutators (Set Mutators)

```php
class User extends Model
{
    // Mutator for password attribute
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = bcrypt($value);
    }
    
    // Mutator for email attribute
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = strtolower(trim($value));
    }
}

// Usage
$user = new User();
$user->password = 'plaintext'; // Automatically hashed
$user->email = '  JOHN@EXAMPLE.COM  '; // Becomes 'john@example.com'
```

## Type Casting

### Available Cast Types

```php
class User extends Model
{
    protected array $casts = [
        'age' => 'integer',
        'salary' => 'float',
        'is_active' => 'boolean',
        'settings' => 'json',
        'tags' => 'array',
        'profile' => 'object',
        'birth_date' => 'date',
        'last_login' => 'datetime',
        'created_timestamp' => 'timestamp',
        'bio' => 'string'
    ];
}
```

### Usage

```php
$user = User::find(1);

// These are automatically cast to correct types
$age = $user->age; // integer
$isActive = $user->is_active; // boolean
$settings = $user->settings; // array/object from JSON
$birthDate = $user->birth_date; // Carbon instance
```

## Relationships

### One-to-One (hasOne)

```php
class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class);
        // Custom foreign key: return $this->hasOne(Profile::class, 'user_id');
        // Custom local key: return $this->hasOne(Profile::class, 'user_id', 'id');
    }
}

// Usage
$user = User::find(1);
$profile = $user->profile;
```

### One-to-Many (hasMany)

```php
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class);
        // Custom keys: return $this->hasMany(Post::class, 'author_id', 'id');
    }
}

// Usage
$user = User::find(1);
$posts = $user->posts; // Collection of posts
```

### Belongs To (belongsTo)

```php
class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
        // Custom keys: return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

// Usage
$post = Post::find(1);
$author = $post->user;
```

### Many-to-Many (belongsToMany)

```php
class User extends Model
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
        // Custom table: return $this->belongsToMany(Role::class, 'user_roles');
        // Custom keys: return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }
}

// Usage
$user = User::find(1);
$roles = $user->roles; // Collection of roles
```

## Events & Hooks

### Available Events

```php
// Model events (in order of execution)
'creating'  // Before creating new record
'created'   // After creating new record
'updating'  // Before updating existing record
'updated'   // After updating existing record
'saving'    // Before creating or updating (fires before creating/updating)
'saved'     // After creating or updating (fires after created/updated)
'deleting'  // Before deleting record
'deleted'   // After deleting record
'restoring' // Before restoring soft-deleted record
'restored'  // After restoring soft-deleted record
```

### Registering Event Callbacks

```php
// Register event callbacks
User::creating(function ($user) {
    $user->uuid = Str::uuid();
    $user->created_by = auth()->id();
});

User::saving(function ($user) {
    // Validate before saving
    if (empty($user->email)) {
        return false; // Prevent saving
    }
});

User::created(function ($user) {
    // Send welcome email
    Mail::send('welcome', $user);
});

User::updating(function ($user) {
    $user->updated_by = auth()->id();
});

User::deleting(function ($user) {
    // Prevent deletion of admin users
    if ($user->role === 'admin') {
        return false;
    }
});
```

### Multiple Event Callbacks

```php
User::creating(function ($user) {
    // First callback
    $user->status = 'pending';
});

User::creating(function ($user) {
    // Second callback
    Log::info('Creating user: ' . $user->email);
});
```

## Query Building

### Basic Queries

```php
// Get query builder instance
$query = User::query();

// Chain methods
$users = User::query()
    ->where('active', true)
    ->where('age', '>', 18)
    ->orderBy('name')
    ->limit(10)
    ->offset(20)
    ->get();
```

### Advanced Query Methods

```php
// Join (if available in your QueryBuilder)
$users = User::query()
    ->join('profiles', 'users.id', '=', 'profiles.user_id')
    ->select('users.*', 'profiles.bio')
    ->get();

// Group by and having
$users = User::query()
    ->groupBy('department')
    ->having('count', '>', 5)
    ->get();

// Raw expressions (if supported)
$users = User::query()
    ->selectRaw('COUNT(*) as user_count')
    ->groupBy('department')
    ->get();
```

## Serialization

### Array Conversion

```php
$user = User::find(1);
$array = $user->toArray();

// With relationships
$user = User::with('posts')->find(1);
$array = $user->toArray(); // Includes posts
```

### JSON Conversion

```php
$user = User::find(1);
$json = $user->toJson();

// Or use json_encode (implements JsonSerializable)
$json = json_encode($user);
```

### Hiding Attributes

```php
class User extends Model
{
    protected array $hidden = ['password', 'remember_token'];
}

$user = User::find(1);
$array = $user->toArray(); // password and remember_token are excluded
```

### Custom Serialization

```php
class User extends Model
{
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['custom_field'] = 'custom_value';
        return $array;
    }
}
```

## Mass Assignment

### Fillable Attributes (Whitelist)

```php
class User extends Model
{
    protected array $fillable = ['name', 'email', 'age'];
}

// Only name, email, and age can be mass assigned
$user = User::create([
    'name' => 'John',
    'email' => 'john@example.com',
    'age' => 30,
    'admin' => true // This will be ignored
]);
```

### Guarded Attributes (Blacklist)

```php
class User extends Model
{
    protected array $guarded = ['id', 'admin', 'created_at'];
}

// All attributes except id, admin, and created_at can be mass assigned
$user = User::create([
    'name' => 'John',
    'email' => 'john@example.com',
    'admin' => true // This will be ignored
]);
```

### Guard All Attributes

```php
class User extends Model
{
    protected array $guarded = ['*']; // Guard everything
}

// No mass assignment allowed
$user = new User();
$user->name = 'John'; // Must set individually
$user->save();
```

## Timestamps

### Enable/Disable Timestamps

```php
class User extends Model
{
    protected bool $timestamps = true; // Default: true
}
```

### Custom Timestamp Handling

```php
class User extends Model
{
    protected array $dates = ['created_at', 'updated_at', 'deleted_at', 'last_login'];
}

$user = User::find(1);
$user->last_login = Carbon::now();
$user->save();

// Access as Carbon instances
$createdAt = $user->created_at; // Carbon instance
$formatted = $user->created_at->format('Y-m-d H:i:s');
```

## Advanced Features

### Original Attributes

```php
$user = User::find(1);
$user->name = 'New Name';

// Get original value
$originalName = $user->getOriginal('name');

// Get all original attributes
$original = $user->getOriginal();

// Check what changed
$changes = $user->getChanges();
```

### Model State Checking

```php
$user = User::create(['name' => 'John']);

// Check if model exists in database
$user->exists; // true

// Check if recently created
$user->wasRecentlyCreated; // true

// Check if model is dirty (has unsaved changes)
$user->name = 'Jane';
$user->isDirty(); // true
$user->isDirty('name'); // true
```

### Custom Table Names

```php
class User extends Model
{
    protected string $table = 'app_users'; // Custom table name
}

// If not specified, table name is auto-generated:
// User -> users
// BlogPost -> blogposts
```

### Custom Primary Keys

```php
class User extends Model
{
    protected string $primaryKey = 'user_id';
}

$user = User::find(123); // Uses user_id column
```

### Magic Methods

```php
$user = User::find(1);

// Get attribute
$name = $user->name; // Calls getAttribute()

// Set attribute
$user->name = 'John'; // Calls setAttribute()

// Check if attribute exists
isset($user->name); // Calls __isset()

// Unset attribute
unset($user->name); // Calls __unset()

// String conversion
echo $user; // Returns JSON representation
```

### Example Complete Model

```php
<?php

namespace App\Models;

use Plugs\Database\Eloquent\Model;
use Plugs\Illusion\Carbon\Carbon;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name', 'email', 'age', 'birth_date'
    ];
    
    protected array $hidden = [
        'password', 'remember_token'
    ];
    
    protected array $casts = [
        'age' => 'integer',
        'is_active' => 'boolean',
        'settings' => 'json',
        'birth_date' => 'date'
    ];
    
    protected array $dates = [
        'created_at', 'updated_at', 'last_login'
    ];
    
    protected bool $timestamps = true;
    protected ?string $deletedAt = 'deleted_at';
    
    // Mutators
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = bcrypt($value);
    }
    
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = strtolower(trim($value));
    }
    
    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    public function getAgeInYearsAttribute(): int
    {
        return $this->birth_date->diffInYears(Carbon::now());
    }
    
    // Relationships
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }
}

// Event registration (typically in a service provider or boot method)
User::creating(function ($user) {
    $user->uuid = Str::uuid();
});

User::saving(function ($user) {
    if (empty($user->email)) {
        return false;
    }
});
```

### Usage Examples

```php
// Create user
$user = User::create([
    'name' => 'John Doe',
    'email' => 'JOHN@EXAMPLE.COM', // Will be lowercased
    'password' => 'plaintext', // Will be hashed
    'age' => 30,
    'birth_date' => '1993-01-01',
    'settings' => ['theme' => 'dark'], // Will be JSON encoded
    'is_active' => 1 // Will be cast to boolean
]);

// Query users
$adults = User::where('age', '>=', 18)
    ->where('is_active', true)
    ->orderBy('name')
    ->get();

// Update user
$user = User::find(1);
$user->update(['name' => 'Jane Doe']);

// Soft delete
$user->delete();

// Restore
$user->restore();

// Access relationships
$userPosts = $user->posts;
$userProfile = $user->profile;

// Use accessors
$fullName = $user->full_name;
$ageInYears = $user->age_in_years;

// Convert to array/JSON
$array = $user->toArray();
$json = $user->toJson();
