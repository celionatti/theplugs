<div class="table-container">
    <div class="table-header">
        <div class="table-title">
            <h3>TV Packages</h3>
        </div>
        <div>
            <button class="btn-custom btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addPackageModal">
                <i class="fas fa-plus me-2"></i>Add Package
            </button>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Package</th>
                <th>Provider</th>
                <th>Channels</th>
                <th>Price/Month</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="packagesTable">
            <!-- Packages will be loaded here -->
        </tbody>
    </table>
</div>