<?php
if (!isAdmin()) {
    redirect('index.php?page=dashboard');
}
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Manage Services</h1>
    <p class="text-gray-600">Manage categories and products for your services.</p>
</div>

<?php $flash = getFlashMessage(); ?>
<?php if ($flash): ?>
    <div class="mb-6 p-4 rounded-lg <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>" role="alert">
        <?= $flash['message'] ?>
    </div>
<?php endif; ?>

<!-- Tabs -->
<div class="mb-6">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button onclick="switchTab('categories')"
                    class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 
                           whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm active"
                    data-tab="categories">
                Categories
            </button>
            <button onclick="switchTab('products')"
                    class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 
                           whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    data-tab="products">
                Products
            </button>
        </nav>
    </div>
</div>

<!-- Categories Tab -->
<div id="categoriesTab" class="tab-content">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Categories</h2>
            <button onclick="showAddCategoryModal()"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm 
                           font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 
                           focus:ring-offset-2 focus:ring-purple-500">
                <i class="fas fa-plus mr-2"></i>
                Add Category
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($category['name']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500">
                                    <?= htmlspecialchars($category['description'] ?? '-') ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?= $category['product_count'] ?> products
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $category['status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $category['status'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="showEditCategoryModal(<?= htmlspecialchars(json_encode($category)) ?>)"
                                        class="text-purple-600 hover:text-purple-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="toggleCategoryStatus(<?= $category['id'] ?>, <?= $category['status'] ?>)"
                                        class="text-gray-600 hover:text-gray-900">
                                    <i class="fas fa-power-off"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Products Tab -->
<div id="productsTab" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Products</h2>
            <button onclick="showAddProductModal()"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm 
                           font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 
                           focus:ring-offset-2 focus:ring-purple-500">
                <i class="fas fa-plus mr-2"></i>
                Add Product
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specs</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($product['name']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= htmlspecialchars($product['description'] ?? '-') ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?= htmlspecialchars($product['category_name']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500">
                                    <div>RAM: <?= htmlspecialchars($product['ram']) ?></div>
                                    <div>CPU: <?= htmlspecialchars($product['cpu']) ?></div>
                                    <div>Disk: <?= htmlspecialchars($product['disk']) ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    Rp <?= number_format($product['price'], 0, ',', '.') ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $product['status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $product['status'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="showEditProductModal(<?= htmlspecialchars(json_encode($product)) ?>)"
                                        class="text-purple-600 hover:text-purple-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="toggleProductStatus(<?= $product['id'] ?>, <?= $product['status'] ?>)"
                                        class="text-gray-600 hover:text-gray-900">
                                    <i class="fas fa-power-off"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div id="categoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden" style="z-index: 50;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-xl shadow-lg max-w-lg w-full mx-auto">
            <form id="categoryForm" method="POST" action="index.php?page=admin/services">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="add_category">
                <input type="hidden" name="id" id="categoryId">

                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800" id="categoryModalTitle">Add Category</h3>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label for="categoryName" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="categoryName" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 
                                      focus:border-purple-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="categoryDescription" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="categoryDescription" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 
                                         focus:border-purple-500 sm:text-sm"></textarea>
                    </div>

                    <div id="categoryStatusContainer" class="hidden">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <div class="mt-1">
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="1" class="form-radio text-purple-600" checked>
                                <span class="ml-2">Active</span>
                            </label>
                            <label class="inline-flex items-center ml-6">
                                <input type="radio" name="status" value="0" class="form-radio text-purple-600">
                                <span class="ml-2">Inactive</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-gray-50 rounded-b-xl border-t border-gray-100 flex justify-end space-x-3">
                    <button type="button" onclick="closeCategoryModal()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 
                                   bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 
                                   focus:ring-purple-500">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium 
                                   text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 
                                   focus:ring-offset-2 focus:ring-purple-500">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div id="productModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden" style="z-index: 50;">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white rounded-xl shadow-lg max-w-2xl w-full mx-auto">
            <form id="productForm" method="POST" action="index.php?page=admin/services">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="add_product">
                <input type="hidden" name="id" id="productId">

                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800" id="productModalTitle">Add Product</h3>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="productCategory" class="block text-sm font-medium text-gray-700">Category</label>
                            <select name="category_id" id="productCategory" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 
                                           focus:border-purple-500 sm:text-sm">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="productName" class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" id="productName" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 
                                          focus:border-purple-500 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="productDescription" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="productDescription" rows="2"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 
                                         focus:border-purple-500 sm:text-sm"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="productRam" class="block text-sm font-medium text-gray-700">RAM</label>
                            <input type="text" name="ram" id="productRam" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 
                                          focus:border-purple-500 sm:text-sm"
                                   placeholder="e.g., 4GB">
                        </div>

                        <div>
                            <label for="productCpu" class="block text-sm font-medium text-gray-700">CPU</label>
                            <input type="text" name="cpu" id="productCpu" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 
                                          focus:border-purple-500 sm:text-sm"
                                   placeholder="e.g., 2 vCPU">
                        </div>

                        <div>
                            <label for="productDisk" class="block text-sm font-medium text-gray-700">Disk</label>
                            <input type="text" name="disk" id="productDisk" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 
                                          focus:border-purple-500 sm:text-sm"
                                   placeholder="e.g., 50GB">
                        </div>
                    </div>

                    <div>
                        <label for="productPrice" class="block text-sm font-medium text-gray-700">Price (Rp)</label>
                        <input type="number" name="price" id="productPrice" required min="0" step="1000"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 
                                      focus:border-purple-500 sm:text-sm">
                    </div>

                    <div id="productStatusContainer" class="hidden">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <div class="mt-1">
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="1" class="form-radio text-purple-600" checked>
                                <span class="ml-2">Active</span>
                            </label>
                            <label class="inline-flex items-center ml-6">
                                <input type="radio" name="status" value="0" class="form-radio text-purple-600">
                                <span class="ml-2">Inactive</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-gray-50 rounded-b-xl border-t border-gray-100 flex justify-end space-x-3">
                    <button type="button" onclick="closeProductModal()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 
                                   bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 
                                   focus:ring-purple-500">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium 
                                   text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 
                                   focus:ring-offset-2 focus:ring-purple-500">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Tab switching
    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
        document.getElementById(tabName + 'Tab').classList.remove('hidden');
        
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('border-purple-500', 'text-purple-600');
            button.classList.add('border-transparent', 'text-gray-500');
        });
        
        const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
        activeButton.classList.remove('border-transparent', 'text-gray-500');
        activeButton.classList.add('border-purple-500', 'text-purple-600');
    }

    // Category Modal Functions
    function showAddCategoryModal() {
        document.getElementById('categoryForm').reset();
        document.getElementById('categoryModalTitle').textContent = 'Add Category';
        document.getElementById('categoryForm').action = 'index.php?page=admin/services';
        document.getElementById('categoryForm').elements['action'].value = 'add_category';
        document.getElementById('categoryStatusContainer').classList.add('hidden');
        document.getElementById('categoryModal').classList.remove('hidden');
    }

    function showEditCategoryModal(category) {
        document.getElementById('categoryModalTitle').textContent = 'Edit Category';
        document.getElementById('categoryForm').action = 'index.php?page=admin/services';
        document.getElementById('categoryForm').elements['action'].value = 'update_category';
        document.getElementById('categoryId').value = category.id;
        document.getElementById('categoryName').value = category.name;
        document.getElementById('categoryDescription').value = category.description || '';
        document.getElementById('categoryStatusContainer').classList.remove('hidden');
        document.querySelector(`input[name="status"][value="${category.status}"]`).checked = true;
        document.getElementById('categoryModal').classList.remove('hidden');
    }

    function closeCategoryModal() {
        document.getElementById('categoryModal').classList.add('hidden');
    }

    // Product Modal Functions
    function showAddProductModal() {
        document.getElementById('productForm').reset();
        document.getElementById('productModalTitle').textContent = 'Add Product';
        document.getElementById('productForm').action = 'index.php?page=admin/services';
        document.getElementById('productForm').elements['action'].value = 'add_product';
        document.getElementById('productStatusContainer').classList.add('hidden');
        document.getElementById('productModal').classList.remove('hidden');
    }

    function showEditProductModal(product) {
        document.getElementById('productModalTitle').textContent = 'Edit Product';
        document.getElementById('productForm').action = 'index.php?page=admin/services';
        document.getElementById('productForm').elements['action'].value = 'update_product';
        document.getElementById('productId').value = product.id;
        document.getElementById('productCategory').value = product.category_id;
        document.getElementById('productName').value = product.name;
        document.getElementById('productDescription').value = product.description || '';
        document.getElementById('productRam').value = product.ram;
        document.getElementById('productCpu').value = product.cpu;
        document.getElementById('productDisk').value = product.disk;
        document.getElementById('productPrice').value = product.price;
        document.getElementById('productStatusContainer').classList.remove('hidden');
        document.querySelector(`input[name="status"][value="${product.status}"]`).checked = true;
        document.getElementById('productModal').classList.remove('hidden');
    }

    function closeProductModal() {
        document.getElementById('productModal').classList.add('hidden');
    }

    // Status Toggle Functions
    function toggleCategoryStatus(id, currentStatus) {
        if (confirm('Are you sure you want to change this category\'s status?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'index.php?page=admin/services';
            
            const fields = {
                'action': 'update_category',
                'id': id,
                'status': currentStatus ? '0' : '1',
                'csrf_token': '<?= generateCSRFToken() ?>'
            };
            
            for (const [key, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
    }

    function toggleProductStatus(id, currentStatus) {
        if (confirm('Are you sure you want to change this product\'s status?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'index.php?page=admin/services';
            
            const fields = {
                'action': 'update_product',
                'id': id,
                'status': currentStatus ? '0' : '1',
                'csrf_token': '<?= generateCSRFToken() ?>'
            };
            
            for (const [key, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target === document.getElementById('categoryModal')) {
            closeCategoryModal();
        }
        if (event.target === document.getElementById('productModal')) {
            closeProductModal();
        }
    }

    // Add fade out effect to flash messages
    document.addEventListener('DOMContentLoaded', function() {
        const flashMessage = document.querySelector('[role="alert"]');
        if (flashMessage) {
            setTimeout(() => {
                flashMessage.style.transition = 'opacity 1s ease-in-out';
                flashMessage.style.opacity = '0';
                setTimeout(() => flashMessage.remove(), 1000);
            }, 3000);
        }
    });
</script>
