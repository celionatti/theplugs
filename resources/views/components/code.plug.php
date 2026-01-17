<!-- Code Preview -->
<section id="code" class="code-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4" data-aos="fade-right">
                <h2 class="fw-bold">Write Less, Do More</h2>
                <p>Expressive syntax that gets out of your way so you can focus on building amazing applications.</p>

                <div class="mt-4">
                    <h5 class="text-white">v3.0 Syntax Improvements:</h5>
                    <ul class="text-muted">
                        <li>Simplified routing with named parameters</li>
                        <li>Better error messages and debugging</li>
                        <li>Enhanced Blade-like template engine</li>
                        <li>Simplified database migrations</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-6" data-aos="fade-left">
                <pre class="code-box">
          <code>// New v3.0 Routing Syntax
          Route::get('/user/{id}', function($id) {
              return User::findOrFail($id);
          })->name('user.profile');

          // Simplified Database Operations
          $users = DB::table('users')
              ->where('active', true)
              ->orderBy('name')
              ->paginate(10);

          // New Middleware Syntax
          Route::middleware(['auth', 'verified'])
              ->group(function() {
                  Route::get('/dashboard', 'DashboardController@index');
              });

          // Built-in API Resources (New in v3.0)
          class UserResource extends JsonResource {
              public function toArray($request) {
                  return [
                      'id' => $this->id,
                      'name' => $this->name,
                      'email' => $this->email,
                      'created_at' => $this->created_at,
                  ];
              }
          }</code>
        </pre>
            </div>
        </div>
    </div>
</section>