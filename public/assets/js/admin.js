// Admin Dashboard JavaScript
// Sample data for products
const sampleProducts = [
  {
    id: 1,
    name: "4K Smart Streaming Box",
    category: "Electronics",
    price: 69.99,
    oldPrice: 99.99,
    stock: 45,
    status: "active",
  },
  {
    id: 2,
    name: "Wireless Gaming Headset",
    category: "Gaming",
    price: 129.99,
    oldPrice: null,
    stock: 23,
    status: "active",
  },
  {
    id: 3,
    name: "Smart Home Hub",
    category: "Home",
    price: 89.99,
    oldPrice: null,
    stock: 8,
    status: "draft",
  },
  {
    id: 4,
    name: "Premium Soundbar",
    category: "Electronics",
    price: 149.99,
    oldPrice: 199.99,
    stock: 15,
    status: "active",
  },
  {
    id: 5,
    name: "Bluetooth Speaker",
    category: "Electronics",
    price: 49.99,
    oldPrice: 79.99,
    stock: 0,
    status: "inactive",
  },
];

// Sample data for packages
const samplePackages = [
  {
    id: 1,
    name: "DSTV Premium",
    provider: "DSTV",
    channels: 150,
    price: 99.99,
    status: "active",
  },
  {
    id: 2,
    name: "DSTV Compact Plus",
    provider: "DSTV",
    channels: 135,
    price: 79.99,
    status: "active",
  },
  {
    id: 3,
    name: "GOTV Supa",
    provider: "GOTV",
    channels: 80,
    price: 29.99,
    status: "active",
  },
  {
    id: 4,
    name: "GOTV Max",
    provider: "GOTV",
    channels: 75,
    price: 24.99,
    status: "draft",
  },
];

// Initialize data
let products =
  JSON.parse(localStorage.getItem("admin_products")) || sampleProducts;
let packages =
  JSON.parse(localStorage.getItem("admin_packages")) || samplePackages;

// Theme Toggle
const themeToggle = document.getElementById("themeToggle");
const html = document.documentElement;

themeToggle.addEventListener("click", () => {
  const currentTheme = html.getAttribute("data-theme");
  const newTheme = currentTheme === "dark" ? "light" : "dark";
  html.setAttribute("data-theme", newTheme);

  const icon = themeToggle.querySelector("i");
  icon.className = newTheme === "dark" ? "fas fa-moon" : "fas fa-sun";

  localStorage.setItem("admin_theme", newTheme);
});

// Load saved theme
const savedTheme = localStorage.getItem("admin_theme") || "dark";
html.setAttribute("data-theme", savedTheme);
const icon = themeToggle.querySelector("i");
icon.className = savedTheme === "dark" ? "fas fa-moon" : "fas fa-sun";

// Mobile sidebar toggle
const mobileToggle = document.getElementById("mobileToggle");
const sidebar = document.getElementById("sidebar");

mobileToggle.addEventListener("click", () => {
  sidebar.classList.toggle("show");
});

// Close sidebar when clicking outside on mobile
document.addEventListener("click", (e) => {
  if (
    window.innerWidth <= 991 &&
    !sidebar.contains(e.target) &&
    !mobileToggle.contains(e.target)
  ) {
    sidebar.classList.remove("show");
  }
});

// Render products table
function renderProductsTable() {
  const tbody = document.getElementById("productsTable");
  tbody.innerHTML = "";

  products.forEach((product) => {
    const row = document.createElement("tr");

    const statusClass = `status-${product.status}`;
    const statusText =
      product.status.charAt(0).toUpperCase() + product.status.slice(1);

    row.innerHTML = `
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="product-image">${product.name.charAt(
                              0
                            )}</div>
                            <div>
                                <div style="font-weight: 600;">${
                                  product.name
                                }</div>
                                <small style="color: var(--text-secondary);">${
                                  product.category
                                }</small>
                            </div>
                        </div>
                    </td>
                    <td>${product.category}</td>
                    <td>
                        <div style="font-weight: 600;">$${product.price.toFixed(
                          2
                        )}</div>
                        ${
                          product.oldPrice
                            ? `<small style="color: var(--text-secondary); text-decoration: line-through;">$${product.oldPrice.toFixed(
                                2
                              )}</small>`
                            : ""
                        }
                    </td>
                    <td>
                        <div style="font-weight: 600;">${product.stock}</div>
                        <small style="color: ${
                          product.stock < 10
                            ? "var(--danger)"
                            : "var(--success)"
                        };">${
      product.stock < 10 ? "Low Stock" : "In Stock"
    }</small>
                    </td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn" onclick="editProduct(${
                              product.id
                            })" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn" onclick="deleteProduct(${
                              product.id
                            })" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button class="action-btn" onclick="toggleStatus(${
                              product.id
                            })" title="Toggle Status">
                                <i class="fas fa-power-off"></i>
                            </button>
                        </div>
                    </td>
                `;

    tbody.appendChild(row);
  });
}

// Render packages table
function renderPackagesTable() {
  const tbody = document.getElementById("packagesTable");
  tbody.innerHTML = "";

  packages.forEach((pkg) => {
    const row = document.createElement("tr");

    const statusClass = `status-${pkg.status}`;
    const statusText = pkg.status.charAt(0).toUpperCase() + pkg.status.slice(1);
    const providerColor = pkg.provider === "DSTV" ? "#0088cc" : "#92c83e";

    row.innerHTML = `
                    <td style="font-weight: 600;">${pkg.name}</td>
                    <td>
                        <span style="padding: 0.25rem 0.75rem; border-radius: 0.5rem; background: ${providerColor}20; color: ${providerColor};">
                            ${pkg.provider}
                        </span>
                    </td>
                    <td>${pkg.channels}+</td>
                    <td style="font-weight: 600;">$${pkg.price.toFixed(2)}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn" onclick="editPackage(${
                              pkg.id
                            })" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn" onclick="deletePackage(${
                              pkg.id
                            })" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;

    tbody.appendChild(row);
  });
}

// Save product
function saveProduct() {
  const modal = bootstrap.Modal.getInstance(
    document.getElementById("addProductModal")
  );
  const form = document.getElementById("productForm");

  // In a real app, you would send this data to a server
  const newProduct = {
    id: products.length > 0 ? Math.max(...products.map((p) => p.id)) + 1 : 1,
    name: form.querySelector('input[type="text"]').value,
    category: form.querySelector("select").value,
    price: parseFloat(form.querySelector('input[type="number"]').value),
    oldPrice: form.querySelectorAll('input[type="number"]')[1].value
      ? parseFloat(form.querySelectorAll('input[type="number"]')[1].value)
      : null,
    stock: parseInt(form.querySelectorAll('input[type="number"]')[2].value),
    status: form.querySelectorAll("select")[1].value,
  };

  products.push(newProduct);
  localStorage.setItem("admin_products", JSON.stringify(products));
  renderProductsTable();

  // Reset form and close modal
  form.reset();
  modal.hide();

  // Show success message
  showAlert("Product added successfully!", "success");
}

// Save package
function savePackage() {
  const modal = bootstrap.Modal.getInstance(
    document.getElementById("addPackageModal")
  );
  const form = document.getElementById("packageForm");

  const newPackage = {
    id: packages.length > 0 ? Math.max(...packages.map((p) => p.id)) + 1 : 1,
    name: form.querySelector('input[type="text"]').value,
    provider: form.querySelector("select").value,
    channels: parseInt(form.querySelector('input[type="number"]').value),
    price: parseFloat(form.querySelectorAll('input[type="number"]')[1].value),
    status: form.querySelectorAll("select")[1].value,
  };

  packages.push(newPackage);
  localStorage.setItem("admin_packages", JSON.stringify(packages));
  renderPackagesTable();

  form.reset();
  modal.hide();
  showAlert("Package added successfully!", "success");
}

// Edit product
function editProduct(id) {
  const product = products.find((p) => p.id === id);
  if (product) {
    // In a real app, you would populate the modal with product data
    showAlert(`Edit product: ${product.name}`, "info");
  }
}

// Edit package
function editPackage(id) {
  const pkg = packages.find((p) => p.id === id);
  if (pkg) {
    showAlert(`Edit package: ${pkg.name}`, "info");
  }
}

// Delete product
function deleteProduct(id) {
  if (confirm("Are you sure you want to delete this product?")) {
    products = products.filter((p) => p.id !== id);
    localStorage.setItem("admin_products", JSON.stringify(products));
    renderProductsTable();
    showAlert("Product deleted successfully!", "success");
  }
}

// Delete package
function deletePackage(id) {
  if (confirm("Are you sure you want to delete this package?")) {
    packages = packages.filter((p) => p.id !== id);
    localStorage.setItem("admin_packages", JSON.stringify(packages));
    renderPackagesTable();
    showAlert("Package deleted successfully!", "success");
  }
}

// Toggle product status
function toggleStatus(id) {
  const product = products.find((p) => p.id === id);
  if (product) {
    const statuses = ["active", "draft", "inactive"];
    const currentIndex = statuses.indexOf(product.status);
    product.status = statuses[(currentIndex + 1) % statuses.length];

    localStorage.setItem("admin_products", JSON.stringify(products));
    renderProductsTable();
    showAlert(`Product status changed to ${product.status}`, "info");
  }
}

// Export products
function exportProducts() {
  const dataStr = JSON.stringify(products, null, 2);
  const dataUri =
    "data:application/json;charset=utf-8," + encodeURIComponent(dataStr);

  const exportFileDefaultName = "royalstream-products.json";

  const linkElement = document.createElement("a");
  linkElement.setAttribute("href", dataUri);
  linkElement.setAttribute("download", exportFileDefaultName);
  linkElement.click();

  showAlert("Products exported successfully!", "success");
}

// Show alert
function showAlert(message, type) {
  const alert = document.createElement("div");
  alert.className = `alert alert-${
    type === "success" ? "success" : type === "error" ? "danger" : "info"
  }`;
  alert.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                background: var(--bg-card);
                border: 1px solid var(--border);
                color: var(--text-primary);
                border-radius: 0.5rem;
                padding: 1rem;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                animation: slideIn 0.3s ease;
            `;

  alert.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-${
                      type === "success"
                        ? "check-circle"
                        : type === "error"
                        ? "exclamation-circle"
                        : "info-circle"
                    }"
                       style="color: ${
                         type === "success"
                           ? "var(--success)"
                           : type === "error"
                           ? "var(--danger)"
                           : "var(--primary)"
                       }"></i>
                    <span>${message}</span>
                </div>
            `;

  document.body.appendChild(alert);

  setTimeout(() => {
    alert.style.animation = "slideOut 0.3s ease";
    setTimeout(() => alert.remove(), 300);
  }, 3000);
}

// Add CSS for animations
const style = document.createElement("style");
style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
document.head.appendChild(style);

// Initialize tables
document.addEventListener("DOMContentLoaded", () => {
  renderProductsTable();
  renderPackagesTable();
});
