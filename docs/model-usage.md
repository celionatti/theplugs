# Usage of Model

<?php

// Assuming you have these models:

use Plugs\Database\Eloquent\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password'];
    protected array $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean'
    ];

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
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
                    ->withPivot(['assigned_at', 'assigned_by']);
    }
}

class Post extends Model
{
    protected string $table = 'posts';
    protected array $fillable = ['title', 'body', 'user_id', 'published_at'];
    protected array $casts = [
        'published_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }
}

class Profile extends Model
{
    protected string $table = 'profiles';
    protected array $fillable = ['user_id', 'bio', 'avatar'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class Comment extends Model
{
    protected string $table = 'comments';
    protected array $fillable = ['post_id', 'user_id', 'body'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class Role extends Model
{
    protected string $table = 'roles';
    protected array $fillable = ['name', 'slug'];
}

class Tag extends Model
{
    protected string $table = 'tags';
    protected array $fillable = ['name', 'slug'];
}

// ============================================
// USAGE EXAMPLES
// ============================================

// 1. BASIC CRUD OPERATIONS
// ============================================

// Create
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT)
]);

// Alternative create
$user = new User();
$user->name = 'Jane Doe';
$user->email = 'jane@example.com';
$user->password = password_hash('secret', PASSWORD_DEFAULT);
$user->save();

// Read - Find by ID
$user = User::find(1);
$user = User::findOrFail(1); // Throws exception if not found

// Read - Query
$users = User::where('is_active', true)->get();
$user = User::where('email', 'john@example.com')->first();

// Update
$user = User::find(1);
$user->name = 'John Updated';
$user->save();

// Alternative update
$user = User::find(1);
$user->update(['name' => 'John Updated']);

// Update with query
User::where('id', 1)->update(['name' => 'John Updated']);

// Delete
$user = User::find(1);
$user->delete();

// 2. SOFT DELETES
// ============================================

class Post extends Model
{
    protected ?string $deletedAt = 'deleted_at'; // Enable soft deletes

    // ... rest of model
}

$post = Post::find(1);
$post->delete(); // Soft delete

// Query only trashed
$trashedPosts = Post::onlyTrashed()->get();

// Query with trashed
$allPosts = Post::withTrashed()->get();

// Restore
$post = Post::onlyTrashed()->find(1);
$post->restore();

// Force delete (permanent)
$post->forceDelete();

// Check if trashed
if ($post->trashed()) {
    $post->restore();
}

// 3. QUERY BUILDER METHODS
// ============================================

// Where clauses
$users = User::where('is_active', true)
             ->where('created_at', '>', '2024-01-01')
             ->get();

// Or where
$users = User::where('role', 'admin')
             ->orWhere('role', 'moderator')
             ->get();

// Where In
$users = User::whereIn('id', [1, 2, 3, 4])->get();

// Where Null
$users = User::whereNull('email_verified_at')->get();

// Order by
$users = User::orderBy('created_at', 'desc')->get();

// Limit and offset
$users = User::limit(10)->offset(20)->get();

// Pagination
$result = User::where('is_active', true)
              ->orderBy('created_at', 'desc')
              ->paginate(15, 1); // 15 per page, page 1

// Access pagination data
$users = $result['data'];
$currentPage = $result['current_page'];
$totalPages = $result['last_page'];

// Aggregates
$count = User::where('is_active', true)->count();
$max = User::max('login_count');
$min = User::min('age');
$avg = User::avg('rating');
$sum = User::sum('points');

// 4. RELATIONSHIPS
// ============================================

// HasOne - Access
$user = User::find(1);
$profile = $user->profile; // Returns Profile model or null

// HasOne - Create
$profile = $user->profile()->create([
    'bio' => 'Software developer',
    'avatar' => 'avatar.jpg'
]);

// HasOne - Save
$profile = new Profile([
    'bio' => 'Software developer',
    'avatar' => 'avatar.jpg'
]);
$user->profile()->save($profile);

// HasMany - Access
$user = User::find(1);
$posts = $user->posts; // Returns array of Post models

// HasMany - Create
$post = $user->posts()->create([
    'title' => 'My First Post',
    'body' => 'This is the content'
]);

// HasMany - Save
$post = new Post([
    'title' => 'My Post',
    'body' => 'Content here'
]);
$user->posts()->save($post);

// HasMany - Save Many
$posts = [
    new Post(['title' => 'Post 1', 'body' => 'Content 1']),
    new Post(['title' => 'Post 2', 'body' => 'Content 2'])
];
$user->posts()->saveMany($posts);

// HasMany - Create Many
$user->posts()->createMany([
    ['title' => 'Post 1', 'body' => 'Content 1'],
    ['title' => 'Post 2', 'body' => 'Content 2']
]);

// BelongsTo - Access
$post = Post::find(1);
$user = $post->user; // Returns User model or null

// BelongsTo - Associate
$post = Post::find(1);
$user = User::find(2);
$post->user()->associate($user);
$post->save();

// BelongsTo - Dissociate
$post->user()->dissociate();
$post->save();

// BelongsToMany - Access
$user = User::find(1);
$roles = $user->roles; // Returns array of Role models

// BelongsToMany - Attach
$user->roles()->attach(1); // Attach role with ID 1
$user->roles()->attach(2, ['assigned_at' => now()]); // With pivot data

// BelongsToMany - Detach
$user->roles()->detach(1); // Detach specific role
$user->roles()->detach(); // Detach all roles

// BelongsToMany - Sync (replaces all relationships)
$user->roles()->sync([1, 2, 3]); // User will have only these roles
$user->roles()->sync([
    1 => ['assigned_at' => now()],
    2 => ['assigned_at' => now()]
]);

// BelongsToMany - Access pivot data
foreach ($user->roles as $role) {
    echo $role->pivot->assigned_at;
    echo $role->pivot->assigned_by;
}

// 5. EAGER LOADING (N+1 Problem Solution)
// ============================================

// Without eager loading (N+1 problem)
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->user->name; // Each iteration queries database
}

// With eager loading (2 queries total)
$posts = Post::with('user')->get();
foreach ($posts as $post) {
    echo $post->user->name; // No additional queries
}

// Multiple relations
$posts = Post::with(['user', 'comments', 'tags'])->get();

// Nested eager loading
$posts = Post::with(['user.profile', 'comments.user'])->get();

// Eager loading with constraints
$users = User::with(['posts' => function($query) {
    $query->where('published_at', '!=', null)
          ->orderBy('published_at', 'desc')
          ->limit(5);
}])->get();

// 6. CASTING & ACCESSORS/MUTATORS
// ============================================

// Casting (defined in model)
class User extends Model
{
    protected array $casts = [
        'is_active' => 'boolean',
        'age' => 'integer',
        'settings' => 'json',
        'created_at' => 'datetime'
    ];
}

$user = User::find(1);
$isActive = $user->is_active; // Returns boolean
$settings = $user->settings; // Returns array from JSON
$createdAt = $user->created_at; // Returns Carbon instance

// Accessors (get computed attributes)
class User extends Model
{
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}

$user = User::find(1);
echo $user->full_name; // Calls getFullNameAttribute

// Mutators (set attributes with transformation)
class User extends Model
{
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }
}

$user = new User();
$user->password = 'secret'; // Automatically hashed

// 7. MODEL EVENTS
// ============================================

// Register event listeners
User::creating(function($user) {
    // Before insert
    $user->uuid = generateUuid();
});

User::created(function($user) {
    // After insert
    // Send welcome email, etc.
});

User::updating(function($user) {
    // Before update
    if ($user->isDirty('email')) {
        // Email changed, verify again
        $user->email_verified_at = null;
    }
});

User::updated(function($user) {
    // After update
});

User::deleting(function($user) {
    // Before delete
    // Check if user can be deleted
    if ($user->posts()->count() > 0) {
        return false; // Prevent deletion
    }
});

User::deleted(function($user) {
    // After delete
    // Clean up related data
});

// 8. ADVANCED QUERIES
// ============================================

// Subqueries with relations
$users = User::with(['posts' => function($query) {
    $query->where('published_at', '>', now()->subDays(7))
          ->orderBy('views', 'desc');
}])->get();

// Check for existence
$hasUsers = User::where('is_active', true)->exists(); // Returns boolean

// Dynamic where methods
$user = User::whereEmail('john@example.com')->first();
$users = User::whereNameAndIsActive('John', true)->get();

// Get dirty attributes
$user = User::find(1);
$user->name = 'New Name';
$user->email = 'new@example.com';

if ($user->isDirty()) {
    $changes = $user->getDirty(); // ['name' => 'New Name', 'email' => 'new@example.com']
}

// Check specific attribute
if ($user->isDirty('email')) {
    // Email changed
}

// Get original values
$originalName = $user->getOriginal('name');

// 9. ARRAY/JSON CONVERSION
// ============================================

$user = User::find(1);

// To array
$array = $user->toArray();

// To JSON
$json = $user->toJson();
$json = json_encode($user); // Uses JsonSerializable

// With relations
$user = User::with('posts')->find(1);
$array = $user->toArray(); // Includes posts

// Hide attributes
class User extends Model
{
    protected array $hidden = ['password', 'remember_token'];
}

// 10. WORKING WITH TIMESTAMPS
// ============================================

// Disable timestamps
class Log extends Model
{
    protected bool $timestamps = false;
}

// Custom timestamp fields
class Post extends Model
{
    protected array $dates = ['created_at', 'updated_at', 'published_at'];
}

$post = Post::find(1);
$publishedAt = $post->published_at; // Carbon instance
echo $publishedAt->diffForHumans(); // "2 days ago"
echo $publishedAt->format('Y-m-d'); // "2024-10-01"

// 11. MASS ASSIGNMENT PROTECTION
// ============================================

// Fillable (whitelist)
class User extends Model
{
    protected array $fillable = ['name', 'email'];
}

$user = User::create($_POST); // Only name and email will be set

// Guarded (blacklist)
class User extends Model
{
    protected array $guarded = ['id', 'is_admin'];
}

// 12. DEBUGGING
// ============================================

// Get SQL query
$sql = User::where('is_active', true)->toSql();
$bindings = User::where('is_active', true)->getBindings();

// Debug model state
$user = User::find(1);
$user->name = 'New Name';
$debug = $user->debugAttributes();
/*
[
    'attributes' => [...],
    'original' => [...],
    'dirty' => ['name' => 'New Name'],
    'exists' => true,
    'key' => 1
]
*/
